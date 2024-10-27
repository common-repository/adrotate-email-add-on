<?php
/**
 * The class responsible for sending Emails to user
 * admin side of the site.
 */

if (isset( $_POST['adrotate_email_advertiser_nonce'] )&& wp_verify_nonce( $_POST['adrotate_email_advertiser_nonce'], 'adrotate_email_advertiser' )){
    if(isset($_POST['dsp_advertiser']) && $_POST['dsp_advertiser'] != ''){
        global $wpdb;
        $advertiser_id = $_POST['dsp_advertiser'];
        if($advertiser_id == '-1'){
            ?>
            <script> alert("Please Select Advertiser !!!");</script>
        <?php

        }else{
            $ad_id = $_POST['dsp_ad_id'];
            $edit_id = $wpdb->get_var("SELECT `meta_id` FROM `".$wpdb->prefix."adsmeta` WHERE `ad` = $ad_id AND `meta_key` = 'advertiser';");

            if(isset($edit_id) && $edit_id != ''){
                $wpdb->update($wpdb->prefix."adsmeta", array('meta_value' => $advertiser_id),array( 'meta_id' => $edit_id ));

            }else{
                $wpdb->insert($wpdb->prefix."adsmeta", array('ad' => $ad_id, 'meta_key' => 'advertiser', 'meta_value' => $advertiser_id));
                //$edit_id = $wpdb->insert_id;
            }
        }
    }
}

if (isset($_GET['message']) && $_GET['message'] == 'error'){
    ?>
    <div class="error">
        <p><?php _e('Please set Email Template before mail Sent.','adrotate-email-add-on'); ?></p>
    </div>
    <?php
}
/*
 * Send mail to user request condition check
 */
if (isset( $_POST['adrotate_email_nonce_inner'] )&& wp_verify_nonce( $_POST['adrotate_email_nonce_inner'], 'adrotate_email_ad_active_inner' )) {
    if (isset($_POST['inner_sub']) && $_POST['inner_sub'] != '' && isset($_POST['ad_id']) && $_POST['ad_id'] != '' && isset($_POST['dsp_report_month']) && $_POST['dsp_report_month'] != '') {
        $month = $_POST['dsp_report_month'];
        $dsp_email1 = new Ad_Rotate_Email_Addon_Send_Email();
        $ad_id= $_POST['ad_id'];
        $dsp_email1->send_email($ad_id,$month);
    }
}
/*
 *  Send Bulk mails to user request condition check
 */

if (isset( $_POST['adrotate_email_nonce'] )&& wp_verify_nonce( $_POST['adrotate_email_nonce'], 'adrotate_email_ad_active' )) {
    if (isset($_POST['adrotate_action']) && $_POST['adrotate_action'] != '' && isset($_POST['bannercheck']) && isset($_POST['dsp_report_gen_month']) && $_POST['dsp_report_gen_month'] != '') {
        $month = $_POST['dsp_report_gen_month'];
        $dsp_email1 = new Ad_Rotate_Email_Addon_Send_Email();
        foreach ($_POST['bannercheck'] as $ad_id) {
            $dsp_email1->send_email($ad_id,$month);
        }
    }
}


	global $wpdb, $current_user, $userdata, $adrotate_config, $adrotate_debug;

	$now 			= adrotate_now();
	$today 			= adrotate_date_start('day');
	$in2days 		= $now + 172800;
	$in7days 		= $now + 604800;
	$in84days 		= $now + 7257600;

	if(isset($_GET['month']) AND isset($_GET['year'])) {
	$month = esc_attr($_GET['month']);
	$year = esc_attr($_GET['year']);
	} else {
	$month = date("m");
	$year = date("Y");
	}
	$monthstart = mktime(0, 0, 0, $month, 1, $year);
	$monthend = mktime(0, 0, 0, $month+1, 0, $year);
?>
	<div class="wrap">
		<h2><?php _e('Email Ad Reports', 'adrotate-email-add-on'); ?></h2>
		<?php
		// Fetch all Ad Banners
		$allbanners = $wpdb->get_results("SELECT `id`, `title`, `type`, `tracker`, `weight`, `budget`, `crate`, `irate` FROM `".$wpdb->prefix."adrotate` WHERE `type` = 'active' OR `type` = 'error' OR `type` = 'expired' OR `type` = '2days' OR `type` = '7days' OR `type` = 'disabled' ORDER BY `id` ASC;");

		$activebanners1 = $errorbanners = $disabledbanners = false;
		foreach($allbanners as $singlebanner) {
			$advertiser = '';
			$starttime = $stoptime = 0;

            $starttime = $wpdb->get_var("SELECT `starttime` FROM `".$wpdb->prefix."adrotate_schedule`, `".$wpdb->prefix."adrotate_linkmeta` WHERE `ad` = '".$singlebanner->id."' AND `schedule` = `".$wpdb->prefix."adrotate_schedule`.`id` ORDER BY `starttime` ASC LIMIT 1;");
            $stoptime = $wpdb->get_var("SELECT `stoptime` FROM `".$wpdb->prefix."adrotate_schedule`, `".$wpdb->prefix."adrotate_linkmeta` WHERE `ad` = '".$singlebanner->id."' AND `schedule` = `".$wpdb->prefix."adrotate_schedule`.`id` ORDER BY `stoptime` DESC LIMIT 1;");
			if($adrotate_config['enable_advertisers'] == 'Y') {
				$user = $wpdb->get_var("SELECT `user` FROM `".$wpdb->prefix."adrotate_linkmeta` WHERE `ad` = '".$singlebanner->id."' AND `group` = '0' AND `block` = '0' LIMIT 1;");
				$advertiser = $wpdb->get_var("SELECT `user_login` FROM `".$wpdb->prefix."users` WHERE `id` = '".$user."' LIMIT 1;");
			}

			$type = $singlebanner->type;
			if($type == 'active' AND $stoptime <= $in7days) $type = '7days';
			if($type == 'active' AND $stoptime <= $in2days) $type = '2days';
			if($type == 'active' AND $stoptime <= $now) $type = 'expired';
			if(($singlebanner->crate > 0 AND $singlebanner->budget < 1) OR ($singlebanner->irate > 0 AND $singlebanner->budget < 1)) $type = 'expired';

			//Active ads
			if($type == 'active' OR $type == '7days') {
				$activebanners1[$singlebanner->id] = array(
					'id' => $singlebanner->id,
					'title' => $singlebanner->title,
					'advertiser' => $advertiser,
					'type' => $type,
					'tracker' => $singlebanner->tracker,
					'weight' => $singlebanner->weight,
					'firstactive' => $starttime,
					'lastactive' => $stoptime
				);
			}
		}
		?>
		<h3><?php _e('Active Ads', 'adrotate-email-add-on'); ?></h3>

	    <form name="emails" id="post" method="post" action="admin.php?page=adrotate_email">

	        <?php wp_nonce_field('adrotate_email_ad_active','adrotate_email_nonce'); ?>

	        <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="dsp_report_gen_month" id="cat" class="postform" required="required">
                        <option value=""><?php _e('Select Month', 'adrotate-email-add-on'); ?></option>
                        <option value="1" <?php if($month == "1") { echo 'selected'; } ?>><?php _e('January', 'adrotate-email-add-on'); ?></option>
                        <option value="2" <?php if($month == "2") { echo 'selected'; } ?>><?php _e('February', 'adrotate-email-add-on'); ?></option>
                        <option value="3" <?php if($month == "3") { echo 'selected'; } ?>><?php _e('March', 'adrotate-email-add-on'); ?></option>
                        <option value="4" <?php if($month == "4") { echo 'selected'; } ?>><?php _e('April', 'adrotate-email-add-on'); ?></option>
                        <option value="5" <?php if($month == "5") { echo 'selected'; } ?>><?php _e('May', 'adrotate-email-add-on'); ?></option>
                        <option value="6" <?php if($month == "6") { echo 'selected'; } ?>><?php _e('June', 'adrotate-email-add-on'); ?></option>
                        <option value="7" <?php if($month == "7") { echo 'selected'; } ?>><?php _e('July', 'adrotate-email-add-on'); ?></option>
                        <option value="8" <?php if($month == "8") { echo 'selected'; } ?>><?php _e('August', 'adrotate-email-add-on'); ?></option>
                        <option value="9" <?php if($month == "9") { echo 'selected'; } ?>><?php _e('September', 'adrotate-email-add-on'); ?></option>
                        <option value="10" <?php if($month == "10") { echo 'selected'; } ?>><?php _e('October', 'adrotate-email-add-on'); ?></option>
                        <option value="11" <?php if($month == "11") { echo 'selected'; } ?>><?php _e('November', 'adrotate-email-add-on'); ?></option>
                        <option value="12" <?php if($month == "12") { echo 'selected'; } ?>><?php _e('December', 'adrotate-email-add-on'); ?></option>
                    </select>
	                <select name="adrotate_action" id="cat" class="postform" required="required">
	                    <option value=""><?php _e('Bulk Actions', 'adrotate-email-add-on'); ?></option>
	                    <option value="email"><?php _e('Email to User', 'adrotate-email-add-on'); ?></option>
	                </select> <input type="submit" id="addon-post-action-submit" name="adrotateaddon_action_submit" value="Go" class="button-secondary" />
	            </div>
	            <br class="clear" />
	        </div>

	    <table class="widefat" style="margin-top: .5em">
	        <thead>
	        <tr>
	            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
	            <th width="2%"><?php _e('ID', 'adrotate-email-add-on'); ?></th>
		        <th width="10%"><?php _e('Show from', 'adrotate'); ?></th>
		        <th width="10%"><?php _e('Show until', 'adrotate'); ?></th>
	            <th width="10%"><?php _e('Advertiser', 'adrotate-email-add-on'); ?></th>
	            <th><?php _e('Title', 'adrotate'); ?></th>
	            <th width="5%"><?php _e('Impressions', 'adrotate-email-add-on'); ?></th>
	            <th width="5%"><?php _e('Today', 'adrotate-email-add-on'); ?></th>
	            <th width="5%"><?php _e('Clicks', 'adrotate-email-add-on'); ?></th>
	            <th width="5%"><?php _e('Today', 'adrotate-email-add-on'); ?></th>
	            <th width="5%"><?php _e('CTR', 'adrotate-email-add-on'); ?></th>
                <th width="5%"><?php _e('Month', 'adrotate-email-add-on'); ?></th>
	            <th width="5%"><?php _e('Action', 'adrotate-email-add-on'); ?></th>
	        </tr>
	        </thead>
	        <tbody>
	        <?php

	        if ($activebanners1) {

	        foreach($activebanners1 as $banner) {
		        $stats = adrotate_stats($banner['id']);
		        $stats_today = adrotate_stats($banner['id'], $today);
		        $ctr = adrotate_ctr($stats['clicks'], $stats['impressions']);

	            $id = $banner['id'];
	            $ad_title =  $banner['title'];
	            $tracker = $banner['tracker'];
                $ad_rotate_email = new Ad_Rotate_Email_Addon();
                $ad_rotate_email_admin = new Ad_Rotate_Email_Addon_Admin($ad_rotate_email->get_ad_rotate_email_addon(),$ad_rotate_email->get_version() );
                $users = $ad_rotate_email_admin->find_user($id);

	            foreach($users as $user){
	                $user_name  = $user['user_login'];
	                $display_name = $user['display_name'];

	            }


	        ?></form>
	                <tr>

		                <th class="check-column"><input type="checkbox" name="bannercheck[]" value="<?php echo $id; ?>" /></th>
                        <td><?php echo $id; ?></td>
		                <td><?php echo date_i18n("F d, Y", $banner['firstactive']);?></td>
		                <td><span style="color: <?php echo adrotate_prepare_color($banner['lastactive']);?>;"><?php echo date_i18n("F d, Y", $banner['lastactive']);?></span></td>
                        <form name="advertiser_form" method="post" action="admin.php?page=adrotate_email">
                            <?php wp_nonce_field('adrotate_email_advertiser','adrotate_email_advertiser_nonce'); ?>
                            <td>

                                <?php
                                global $wpdb;
                                $select_id = (int)$wpdb->get_var("SELECT `meta_value` FROM `".$wpdb->prefix."adsmeta` WHERE `ad` = $id AND `meta_key` = 'advertiser';");
                                $args = array(
                                    'name'                    => 'dsp_advertiser',
                                    'show_option_none'        => 'Select Advertiser'
                                );
                                if(isset($select_id) && $select_id != ''){
                                    $args['selected'] = $select_id;
                                    $button_text = __('Change', 'adrotate-email-add-on');
                                }else{
                                    $button_text = __('Add', 'adrotate-email-add-on');
                                }
                                ?>
                                <input type="hidden" name="dsp_ad_id" value="<?php echo $id; ?>">
                                <?php wp_dropdown_users( $args ); ?>
                                <input type="submit" class="button-primary" value="<?php echo $button_text; ?>" />


                            </td>
                        </form>
	                    <td>
		                    <strong>
                                <a class="row-title" href="<?php echo admin_url('/admin.php?page=adrotate-ads&view=edit&ad='.$banner['id']);?>" title="<?php _e('Edit', 'adrotate'); ?>">
                                    <?php echo $ad_title; ?>
                                </a>
                            </strong>
                            - <a href="<?php echo admin_url('/admin.php?page=adrotate-ads&view=report&ad='.$banner['id']);?>" title="<?php _e('Stats', 'adrotate-email-add-on'); ?>">
                                <?php _e('Stats', 'adrotate-email-add-on'); ?>
                            </a>
                            - <a href="admin.php?page=adrotate-schedules&ad=<?php echo $banner['id']; ?>">
                                <?php _e('Schedules', 'adrotate-email-add-on'); ?>
                            </a>
                            <span style="color:#999;">
                                <?php if(strlen($banner['advertiser']) > 0 AND $adrotate_config['enable_advertisers'] == 'Y') echo '<br /><span style="font-weight:bold;">Advertiser:</span> '.$banner['advertiser']; ?>
                            </span>
	                    </td>
	                    <td><?php if($tracker=='Y'){ echo $stats['impressions'];}?></td>
		                <td><?php if($tracker=='Y'){ echo $stats_today['impressions'];}?></td>
	                    <td><?php if($tracker=='Y'){ echo $stats['clicks'];}?></td>
		                <td><?php if($tracker=='Y'){ echo $stats_today['clicks'];}?></td>
		                <td><?php if($tracker=='Y'){ echo $stats_today['clicks'];}?></td>

                        <form name="inner_emails" id="post" method="post" action="admin.php?page=adrotate_email">
                            <?php wp_nonce_field('adrotate_email_ad_active_inner','adrotate_email_nonce_inner'); ?>
                            <input type="hidden" name="ad_id" value="<?php echo $id; ?>" >
                        <td>
                            <select name="dsp_report_month" id="cat" class="postform" required="required">
                                <option value=""><?php _e('Select Month', 'adrotate-email-add-on'); ?></option>
                                <option value="1" <?php if($month == "1") { echo 'selected'; } ?>><?php _e('January', 'adrotate-email-add-on'); ?></option>
                                <option value="2" <?php if($month == "2") { echo 'selected'; } ?>><?php _e('February', 'adrotate-email-add-on'); ?></option>
                                <option value="3" <?php if($month == "3") { echo 'selected'; } ?>><?php _e('March', 'adrotate-email-add-on'); ?></option>
                                <option value="4" <?php if($month == "4") { echo 'selected'; } ?>><?php _e('April', 'adrotate-email-add-on'); ?></option>
                                <option value="5" <?php if($month == "5") { echo 'selected'; } ?>><?php _e('May', 'adrotate-email-add-on'); ?></option>
                                <option value="6" <?php if($month == "6") { echo 'selected'; } ?>><?php _e('June', 'adrotate-email-add-on'); ?></option>
                                <option value="7" <?php if($month == "7") { echo 'selected'; } ?>><?php _e('July', 'adrotate-email-add-on'); ?></option>
                                <option value="8" <?php if($month == "8") { echo 'selected'; } ?>><?php _e('August', 'adrotate-email-add-on'); ?></option>
                                <option value="9" <?php if($month == "9") { echo 'selected'; } ?>><?php _e('September', 'adrotate-email-add-on'); ?></option>
                                <option value="10" <?php if($month == "10") { echo 'selected'; } ?>><?php _e('October', 'adrotate-email-add-on'); ?></option>
                                <option value="11" <?php if($month == "11") { echo 'selected'; } ?>><?php _e('November', 'adrotate-email-add-on'); ?></option>
                                <option value="12" <?php if($month == "12") { echo 'selected'; } ?>><?php _e('December', 'adrotate-email-add-on'); ?></option>
                            </select>
                        </td>
                        <td> <input type="submit" class="button-primary" id="sub" name="inner_sub" value="Email"> </td>
                        </form>
	                </tr>
	       <?php } ?>
	        <?php } else { ?>
		        <tr id='no-groups'>
			        <th class="check-column">&nbsp;</th>
			        <td colspan="12"><em><?php _e('No ads created yet!', 'adrotate-email-add-on'); ?></em></td>
		        </tr>
	        <?php } ?>
	        </tbody>
	    </table>



        <table class="widefat" style="margin-top: 2em">
            <form method="post" action="options.php">
                <?php settings_fields('dsp_option_group');?>
                <?php do_settings_sections('dsp_option_group')?>
                <?php if(get_option('dsp_email_template')){  $email_temp = get_option('dsp_email_template'); } else { $email_temp = ""; } ?>
                <thead>
	                <tr>
	                    <th colspan="2" bgcolor="#DDD"><?php _e('Email Template options', 'adrotate-email-add-on'); ?></th>
	                </tr>
                </thead>
                <tbody>
                    <tr>
                    <th width="10%">From Name : </th>
                    <td><input type="text" name="dsp_from" placeholder="From Name.." class="widefat" <?php if(get_option('dsp_from')){ echo 'value="'.get_option('dsp_from').'"'; }?>> </td>

                    </tr>
                    <tr>
                        <th width="10%">From Email : </th>
                        <td><input type="text" name="dsp_from_email" placeholder="From Email.." class="widefat" <?php if(get_option('dsp_from_email')){ echo 'value="'.get_option('dsp_from_email').'"'; }?>> </td>

                    </tr>
                    <tr>
                        <th width="10%">Cc : </th>
                        <td><input type="text" name="dsp_cc" placeholder="Enter Cc Email Address" class="widefat" <?php if(get_option('dsp_cc')){ echo 'value="'.get_option('dsp_cc').'"'; }?>> </td>

                    </tr>
                    <tr>
                        <th width="10%">Reply To : </th>
                        <td><input type="text" name="dsp_reply" placeholder="Enter Reply Email Address" class="widefat" <?php if(get_option('dsp_reply')){ echo 'value="'.get_option('dsp_reply').'"'; }?>> </td>

                    </tr>
	                <tr>
	                    <th width="10%">Subject : </th>
	                    <td><input type="text" name="dsp_subject" placeholder="Enter Email Subject" class="widefat" <?php if(get_option('dsp_subject')){ echo 'value="'.get_option('dsp_subject').'"'; }?>> </td>

	                </tr>
	                <tr>
	                    <th width="10%" valign="top">Email Template : </th>
	                    <td >
		                    <?php $settings = array( 'wpautop'=>false ); ?>
	                        <?php wp_editor($email_temp,'dsp_email_template',$settings); ?>
                            <?php _e('<em>Note: Here <code>{aduser}</code> is Advertiser Name, <code>{adimpression}</code> is Ad Impression, <code>{adclick}</code> is Ad Clicks and <code>{adreport}</code> is daily report of the Ad.</em>', 'adrotate-email-add-on'); ?>
		                    
	                    </td>
	                </tr>
	                <tr>

	                    <td colspan="2">
	                        <?php submit_button(); ?>
	                    </td>
	                </tr>
                </tbody>
            </form>
        </table>

		<div class="clear"></div>
	</div>
