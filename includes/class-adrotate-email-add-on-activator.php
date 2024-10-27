<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ad_Rotate_Email_Addon
 * @subpackage Ad_Rotate_Email_Addon/includes
 * @author     Dharmesh Patel <dharmesh@xylusinfo.com>
 */

class Ad_Rotate_Email_Addon_Activator{

    public static function table_install(){
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        if ($wpdb->has_cap('collation')) {

            if (!empty($wpdb->charset))
                $charset_collate = " DEFAULT CHARACTER SET $wpdb->charset";
            if (!empty($wpdb->collate))
                $charset_collate .= " COLLATE $wpdb->collate";
        }

        $engine = '';
        $found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '" . DB_NAME . "' AND `TABLE_NAME` = '" . $wpdb->prefix . "posts';");
        if (strtolower($found_engine) == 'innodb') {
            $engine = ' ENGINE=InnoDB';
        }

        dbDelta("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."adsmeta` (
		  	`meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  	`ad` bigint(20) unsigned NOT NULL DEFAULT '0',
		  	`meta_key` varchar(255) DEFAULT NULL,
            `meta_value` longtext,
        PRIMARY KEY (`meta_id`)
		) " . $charset_collate . $engine . ";");
    }


    public static function activate() {
         /**
          * Set Options for Email template and Subject
          */
         $mail = '<h1>Your ' . get_bloginfo( 'name' ) . ' Advertising Report</h1>';
         $mail .='<p>Dear {aduser},</p>';
         $mail .='<p>Thank you for advertising in the ' . get_bloginfo( 'name' ) . '. Last month, your ad received {adimpression} impressions and {adclick} clicks. That is an excellent result and a lot of exposure. I have attached a report that shows your daily views and clicks for the month.</p>';
         $mail .='{adreport}';
         $mail .='<p>Thanks and Regards,<br/>';
         $mail .= get_bloginfo( 'name' );

         $subject = get_bloginfo( 'name' ) . ' Ad Report';
         $email   = get_option( 'admin_email' );

         if( !get_option( 'dsp_email_template', false ) ){
            update_option( 'dsp_email_template', $mail);
         }
         if( !get_option( 'dsp_subject', false ) ){
            update_option( 'dsp_subject', $subject);
         }
         if( !get_option( 'dsp_from', false ) ){
            update_option( 'dsp_from', get_bloginfo( 'name' ) );
         }
         if( !get_option( 'dsp_from_email', false ) ){
            update_option( 'dsp_from_email', $email);
         }
         if( !get_option( 'dsp_cc', false ) ){
            update_option( 'dsp_cc', $email);
         }
         if( !get_option( 'dsp_reply', false ) ){
            update_option( 'dsp_reply', $email);
         }
        
         self::table_install();
	 }
}

