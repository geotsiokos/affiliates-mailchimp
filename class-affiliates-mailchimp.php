<?php
/**
 * class-affiliates-mailchimp.php
 *
 * Copyright (c) 2018 www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-mailchimp
 * @since 3.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Affiliates MailChimp
 */
class Affiliates_MailChimp {

	/**
	 * Error notices
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Init Class
	 */
	public static function init() {
		load_plugin_textdomain( 'affiliates-mailchimp', false, 'affiliates-mailchimp/languages' );
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Plugin dependencies
	 */
	public static function wp_init() {
		if ( !defined( 'AFFILIATES_PLUGIN_DOMAIN' ) ) {
			self::$notices[] = "<div class='error'>" . __( '<strong>Affiliates Mailchimp</strong> plugin requires <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', 'affiliates-mailchimp' ) . '</div>';
		} else {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
		}
	}

	/**
	 * Prints admin notices
	 */
	public static function admin_notices() {
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo wp_kses(
					$notice,
					array(
						'strong' => array(),
						'div' => array( 'class' ),
						'a' => array(
							'href'   => array(),
							'target' => array( '_blank' )
						),
						'div' => array(
							'class' => array()
						),
					)
				);
			}
		}
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
			'affiliates-admin',
			__( 'MailChimp' , 'affiliates-mailchimp' ),
			__( 'MailChimp' , 'affiliates-mailchimp' ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-mailchimp',
			array( __CLASS__, 'affiliates_mailchimp_settings' )
		);
	}

	/**
	 * Show Groups MailChimp setting page.
	 */
	public static function affiliates_mailchimp_settings() {
		$output = '';
		$output_sync = '';
		$options = array();
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'affiliates-mailchimp' ) );
		}
		$options = get_option( 'affiliates-mailchimp' );

		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST['aff-mailchimp-nonce'], 'aff-mc-set-admin-options' ) ) {
				$options['api_key']            = isset( $_POST['api_key'] ) ? trim( sanitize_text_field( $_POST['api_key'] ) ) : '';
				$options['list_name']          = isset( $_POST['list_name'] ) ? trim( sanitize_text_field( $_POST['list_name'] ) ) : '';
				$options['interests_category'] = isset( $_POST['interests_category'] ) ? trim( sanitize_text_field( $_POST['interests_category'] ) ) : '';
				$options['interest']           = isset( $_POST['interest'] ) ? trim( sanitize_text_field( $_POST['interest'] ) ) : '';
				$options['need_confirm']       = !empty( $_POST['need_confirm'] ) ? 1 : 0;
				$options['delete_settings']    = !empty( $_POST['delete_settings'] ) ? 1 : 0;
			}
			update_option( 'affiliates-mailchimp', $options );
		} else {
			if ( isset( $_POST['generate'] ) ) {
				if ( wp_verify_nonce( $_POST['aff-mailchimp-nonce'], 'synchronize' ) ) {
					Affiliates_MC::synchronize();
				}
			}
		}

		$api_key            = isset( $options['api_key'] ) ? $options['api_key'] : null;
		$list_name          = isset( $options['list_name'] ) ? $options['list_name'] : '';
		$interests_category = isset( $options['interests_category'] ) ? $options['interests_category'] : '';
		$interest           = isset( $options['interest'] ) ? $options['interest'] : '';
		$need_confirm       = isset( $options['need_confirm'] ) ? $options['need_confirm'] : 0;
		$delete_settings    = isset( $options['delete_settings'] ) ? $options['delete_settings'] : null;

		$description = '';
		if ( !$api_key ) {
			$description = __( 'Affiliates Mailchimp needs a valid API key to connect with MailChimp servers.', 'affiliates-mailchimp' );
		}

		$output .= '<h1>';
		$output .= esc_html__( 'Affiliates MailChimp', 'affiliates-mailchimp' );
		$output .= '</h1>';

		$output .= '<div class="wrap">';
		$output .= '<h2>';
		$output .= esc_html__( 'Settings', 'affiliates-mailchimp' );
		$output .= '</h2>';
		$output .= '<form method="post" name="options" action="">';
		$output .= '<table class="form-table">';

		$output .= '<tr valign="top">';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'API Key:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<input type="text" name="api_key" class="widefat" value="' . esc_attr( $api_key ) . '" />';
		$output .= '<p class="description">';
		$output .= esc_html( $description );
		$output .= '</p>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr valign="top">';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'List name:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<input id="mailchimp-list-name" type="text" name="list_name" class="widefat"  value="' . esc_attr( $list_name ) . '" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr valign="top">';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'Interest Category:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<input type="text" name="interests_category" class="widefat" value="' . esc_attr( $interests_category ) . '" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr valign="top">';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'Interest:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<input type="text" name="interest" class="widefat"  value="' . esc_attr( $interest ) . '" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr valign="top">';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'Confirm Subscription:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<select name="need_confirm">';

		$output .= '<option value="1" ' . ( $need_confirm == '1' ? 'SELECTED' : '' ) . '>';
		$output .= esc_html__( 'YES', 'affiliates-mailchimp' );
		$output .= '</option>';

		$output .= '<option value="0" ' . ( $need_confirm == '0' ? 'SELECTED' : '' ) . '>';
		$output .= esc_html__( 'NO','affiliates-mailchimp' );
		$output .= '</option>';

		$output .= '</select>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'Whether a double opt-in confirmation message is sent.' , 'affiliates-mailchimp' );
		$output .= '</p>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<th scope="row">';
		$output .= esc_html__( 'Delete Settings:', 'affiliates-mailchimp' );
		$output .= '</th>';
		$output .= '<td>';
		$output .= '<input type="checkbox" name="delete_settings" ' . ( esc_attr( $delete_settings ) == 1 ? ' checked="checked" ' : '' ) . '/>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'CAUTION: If this option is enabled while the plugin is deactivated, the above settings will be DELETED. If you want to keep these settings and are going to deactivate it, make sure to keep a note or backup or do not enable this option.' , 'affiliates-mailchimp' );
		$output .= '</p>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '</table>';
		$output .= '<p>';

		$output .= wp_nonce_field( 'aff-mc-set-admin-options', 'aff-mailchimp-nonce', true, false );
		$output .= '<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'affiliates-mailchimp' ) . '"/>';

		$output .= '</p>';
		$output .= '</form>';

		$output .= '</div>';

		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd

		$output_sync .= '<div class="wrap">';
		$output_sync .= '<h2>';
		$output_sync .= esc_html__( 'Synchronize', 'affiliates-mailchimp' );
		$output_sync .= '</h2>';

		$output_sync .= '<p>';
		$output_sync .= esc_html__( 'You can add existing affiliates here by clicking the button.', 'affiliates-mailchimp' );
		$output_sync .= ' ';
		$output_sync .= esc_html__( 'This process can be slow and result in timeouts if you have many affiliates.', 'affiliates-mailchimp' );
		$output_sync .= ' ';
		$output_sync .= '</p>';

		$output_sync .= '<form method="POST" action="">';

		$output_sync .= wp_nonce_field( 'synchronize', 'aff-mailchimp-nonce', true, false );

		$output_sync .= '<table class="form-table">';

		$output_sync .= '<tr>';
		$output_sync .= '<th scope="row">';
		$output_sync .= '<input id="synchronize-mailchimp-list" class="button" type="submit" name="generate" value="' . esc_attr__( 'Synchronize', 'affiliates-mailchimp' ) . '">';
		$output_sync .= '</th>';
		$output_sync .= '<td>';
		$output_sync .= '<p class="description">';
		$output_sync .= esc_html__( 'Click this button to add existing affiliates to the indicated MailChimp list.', 'affiliates-mailchimp' );
		$output_sync .= '</p>';
		$output_sync .= '</td>';
		$output_sync .= '</tr>';

		$output_sync .= '</table>';
		$output_sync .= '</form>';
		$output_sync .= '</div>';

		$output_sync .= '<script type="text/javascript">';
		$output_sync .= 'if ( typeof jQuery !== "undefined" ) {';
			$output_sync .= 'jQuery(document).ready(function(){';
				$output_sync .= 'jQuery("#synchronize-mailchimp-list").click(function(e){';
					$output_sync .= 'e.stopPropagation();';
					$output_sync .= 'var mc_list_name = jQuery("input#mailchimp-list-name").val().trim();';
					$output_sync .= sprintf( 'if ( mc_list_name.length > 0 && mc_list_name === "%s" ) {', esc_attr( $list_name ) );
						$output_sync .= sprintf(
							'if ( confirm("%s") ) {',
							esc_html__( 'Add all affiliates to the indicated list?', 'affiliates-mailchimp' )
						);
						$output_sync .= '} else {'; // if confirm
							$output_sync .= 'e.preventDefault();'; // stop form submission
						$output_sync .= '}';
					$output_sync .= '} else {'; // list is empty or not saved
						$output_sync .= 'e.preventDefault();'; // stop form submission
						$output_sync .= 'if ( mc_list_name.length === 0 ) {';
						$output_sync .= sprintf( 'alert("%s");', esc_attr__( 'Please input the name of the list to which the affiliates should be added.', 'affiliates-mailchimp' ) );
						$output_sync .= '}';
						$output_sync .= sprintf( 'if ( mc_list_name !== "%s" ) {', esc_attr( $list_name ) );
						$output_sync .= sprintf( 'alert("%s");', esc_attr__( 'Please save your changes first.', 'affiliates-mailchimp' ) );
						$output_sync .= '}';
					$output_sync .= '}';
				$output_sync .= '});'; // #synchronize-mailchimp-list click
			$output_sync .= '});'; // ready
		$output_sync .= '}';
		$output_sync .= '</script>';

		$output_sync .= '<h3>';
		$output_sync .= esc_html__( 'Synchronizing many Affiliates', 'affiliates-mailchimp' );
		$output_sync .= '</h3>';
		$output_sync .= '<p>';
		$output_sync .= wp_kses(
			sprintf(
				__( 'To add a large number of affiliates to a list, we recommend to use the Export features available with <a href="%s">Affiliates Pro</a> and <a href="%s">Affiliates Enterprise</a> instead.', 'affiliates-mailchimp' ),
				esc_attr( 'https://www.itthinx.com/shop/affiliates-pro/' ),
				esc_attr( 'https://www.itthinx.com/shop/affiliates-enterprise/' )
			),
			array(
				'a' => array( 'href' => array() )
			)
		);
		$output_sync .= ' ';
		$output_sync .= '<ol>';
		$output_sync .= '<li>';
		$output_sync .= wp_kses(
			sprintf(
				__( '<strong>Export</strong> your affiliates as described <a href="%s">here</a> for Affiliates Pro or <a href="%s">here</a> for Affiliates Enterprise.', 'affiliates-mailchimp' ),
				esc_attr( 'http://docs.itthinx.com/document/affiliates-pro/affiliate-program-management/managing-affiliates/listing-searching-and-exporting/' ),
				esc_attr( 'http://docs.itthinx.com/document/affiliates-enterprise/affiliate-program-management/managing-affiliates/listing-searching-and-exporting/' )
			),
			array(
				'a' => array( 'href' => array() ),
				'strong' => array()
			)
		);
		$output_sync .= '</li>';
		$output_sync .= '<li>';
		$output_sync .= wp_kses(
			__( 'Use the file obtained to import the affiliates from your MailChimp account under <strong>Lists > Add contacts > Import contacts</strong>.', 'affiliates-mailchimp' ),
			array(
				'strong' => array()
			)
		);
		$output_sync .= '</li>';
		$output_sync .= '</ol>';
		$output_sync .= '</p>';

		// @codingStandardsIgnoreStart
		echo $output_sync;
		// @codingStandardsIgnoreEnd
	}
}
Affiliates_MailChimp::init();
