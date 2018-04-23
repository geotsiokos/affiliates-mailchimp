<?php
/**
 * affiliates-mailchimp.php
 *
 * Copyright (c) 2015 www.itthinx.com
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-mailchimp
 * @since 2.0.0
 *
 * Plugin Name: Affiliates MailChimp Integration
 * Description: Integrates the MailChimp service with Affiliates.
 * Version: 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// @todo itthinx updates

define( 'AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN', 'affiliates-mailchimp' );
define( 'AFFILIATES_MAILCHIMP_FILE', __FILE__ );
define( 'AFFILIATES_MAILCHIMP_CORE_DIR', WP_PLUGIN_DIR . '/affiliates-mailchimp' );

require_once 'class-affiliates-mailchimp.php';

class Affiliates_MailChimp_Plugin {

	private static $notices = array();

	public static function init() {
		load_plugin_textdomain( AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN, null, 'affiliates-mailchimp/languages' );
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	public static function wp_init() {
		if ( !defined ( 'AFFILIATES_PLUGIN_DOMAIN' ) )  {
			self::$notices[] = "<div class='error'>" . __( '<strong>Affiliates Mailchimp</strong> plugin requires <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ) . "</div>";
		} else {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
			//call register settings function
			add_action( 'admin_init', array( __CLASS__, 'register_affiliates_mailchimp_settings' ) );
		}
	}

	/**
	 * Register settings as groups-mailchimp-settings
	 */
	public static function register_affiliates_mailchimp_settings() {
		//register our settings
		register_setting( 'affiliates-mailchimp-settings', 'affiliates_mailchimp-api_key' );
		register_setting( 'affiliates-mailchimp-settings', 'affiliates_mailchimp-list' );
		register_setting( 'affiliates-mailchimp-settings', 'affiliates_mailchimp-group' );
		register_setting( 'affiliates-mailchimp-settings', 'affiliates_mailchimp-subgroup' );
		register_setting( 'affiliates-mailchimp-settings', 'affiliates_mailchimp-needconfirm' );
	}

	public static function admin_notices() { 
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
			'affiliates-admin',
			__( 'MailChimp' , AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN),
			__( 'MailChimp' , AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-mailchimp',
			array( __CLASS__, 'affiliates_mailchimp' )
		);
	}

	/**
	 * Show Groups MailChimp setting page.
	 */
	public static function affiliates_mailchimp () {
	?>
	<div class="wrap">
	<h2><?php echo __( 'MailChimp', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></h2>
	<?php 
	if ( isset( $_POST['submit'] ) ) {

		add_option( 'affiliates_mailchimp-api_key', $_POST['api_key'], '', 'no' ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'affiliates_mailchimp-api_key', $_POST['api_key'] );

		add_option( 'affiliates_mailchimp-list', $_POST['list'], '', 'no' ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'affiliates_mailchimp-list', $_POST['list'] );

		add_option( 'affiliates_mailchimp-group', $_POST['group'], '', 'no' ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'affiliates_mailchimp-group', $_POST['group'] );

		add_option( 'affiliates_mailchimp-subgroup', $_POST['subgroup'], '', 'no' ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'affiliates_mailchimp-subgroup', $_POST['subgroup'] );

		add_option( 'affiliates_mailchimp-needconfirm', $_POST['needconfirm'], '', 'no' ); // WP 3.3.1 : update alone wouldn't create the option when value is false
		update_option( 'affiliates_mailchimp-needconfirm', $_POST['needconfirm'] );

	} elseif ( isset( $_POST['generate'] ) ) {

		Affiliates_MailChimp::synchronize();

	} elseif ( isset( $_POST['import'] ) ) {

		Affiliates_MailChimp::toAffiliates();

	}

	?>
	<form method="post" action="">
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'API Key:', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></th>
	        <td>
	        	<input type="text" name="api_key" value="<?php echo get_option('affiliates_mailchimp-api_key'); ?>" />
	        	<p class="description"><?php echo __( 'MailChimp API KEY. You can get it in MailChimp: Account -> API Keys & Authorized Apps ', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></p>
	        </td>
	        </tr>
	         
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'List name:', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></th>
	        <td><input type="text" name="list" value="<?php echo get_option('affiliates_mailchimp-list'); ?>" /></td>
	        </tr>
	    
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'Group name:', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></th>
	        <td><input type="text" name="group" value="<?php echo get_option('affiliates_mailchimp-group'); ?>" /></td>
	        </tr>
	  
	        <tr valign="top">
	        <th scope="row"><?php echo __( 'Subgroup name:', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></th>
	        <td><input type="text" name="subgroup" value="<?php echo get_option('affiliates_mailchimp-subgroup'); ?>" /></td>
	        </tr>
	  
	  		<tr valign="top">
	        <th scope="row"><?php echo __( 'Need confirm:', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></th>
	        <td>
	        	<select name="needconfirm">
	        	<?php 
				if (get_option('affiliates_mailchimp-needconfirm') == "1") {
	        	?>
  					<option value="1" SELECTED><?php echo __('YES',AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN);?></option>
  				<?php 
  				} else {
  				?>
  					<option value="1"><?php echo __('YES',AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN);?></option>
  				<?php 
  				}
  				if (get_option('affiliates_mailchimp-needconfirm') == "0") {
	        	?>
  					<option value="0" SELECTED><?php echo __('NO',AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN);?></option>
  				<?php 
  				} else {
  				?>
  					<option value="0"><?php echo __('NO',AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN);?></option>
  				<?php 
  				}
	        	?>
  				</select> 
	        	
	        	<p class="description"><?php echo __( 'Control whether a double opt-in confirmation message is sent. Abusing this may cause your mailchimp account to be suspended.' , AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN); ?></p>
  				
	        </tr>
	  
	    </table>
	    
	    <?php submit_button(); ?>
	    <?php settings_fields( 'affiliates-mailchimp-settings' ); ?>
	    
	</form>

	</div>

	<div class="wrap">
	<h3><?php echo __( 'Synchronize', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></h3>

	<form method="POST" action="">
	<table class="form-table">
		<tr>
	    	<th scope="row">
	    		<?php submit_button(__("Syncronize", AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN), "secondary", "generate");?>
	    	</th>
	        <td>
				<p class="description"><?php echo __('Use this for synchronize existing users in website with mailchimp.', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<div class="wrap">
	<h3><?php echo __( 'Import', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></h3>

	<form method="POST" action="">
	<table class="form-table">
		<tr>
	    	<th scope="row">
	    		<?php submit_button(__("Import to Affiliates", AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN), "secondary", "import");?>
	    	</th>
	        <td>
				<p class="description"><?php echo __('Creates many affiliates as MailChimp users you have. It ignores the group and subgroup, all users will be imported from the list.', AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN ); ?></p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<?php 
	}

}
Affiliates_MailChimp_Plugin::init();
