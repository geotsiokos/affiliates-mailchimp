<?php
/**
 * affiliates-mailchimp.php
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
 *
 * Plugin Name: Affiliates MailChimp
 * Plugin URI: http://www.itthinx.com/plugins/affiliates-mailchimp/
 * Description: Integrates <a href="https://wordpress.org/plugins/affiliates/">Affiliates</a>, <a href="https://www.itthinx.com/shop/affiliates-pro/">Affiliates Pro</a> and <a href="https://www.itthinx.com/shop/affiliates-enterprise/">Affiliates Enterprise</a> with <a href="https://mailchimp.com">MailChimp</a>.
 * Author: itthinx, proaktion, gtsiokos
 * Author URI: http://www.itthinx.com/
 * Donate-Link: http://www.itthinx.com/shop/
 * License: GPLv3
 * Version: 3.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_MAILCHIMP_PLUGIN_VERSION', '3.1.0' );
define( 'AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN', 'affiliates-mailchimp' );
define( 'AFFILIATES_MAILCHIMP_FILE', __FILE__ );
define( 'AFFILIATES_MAILCHIMP_CORE_DIR', WP_PLUGIN_DIR . '/affiliates-mailchimp' );

register_deactivation_hook( __FILE__, 'affiliates_mailchimp_deactivate' );

/**
 * Option to delete plugin setting upon deactivation
 */
function affiliates_mailchimp_deactivate() {
	$options = get_option( 'affiliates-mailchimp' );
	if ( isset( $options['delete_settings'] ) && $options['delete_settings'] == 1 ) {
		delete_option( 'affiliates-mailchimp' );
	}
}

require_once 'class-affiliates-mailchimp.php';
require_once 'class-affiliates-mailchimp-handler.php';
require_once 'class-affiliates-mailchimp-subscription.php';
