<?php
/**
 * affiliates-mailchimp.php
 *
 * Copyright (c) 2018 www.itthinx.com
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-mailchimp
 * @since 3.0.0
 *
 * Plugin Name: Affiliates MailChimp Integration
 * Description: Integrates the MailChimp service with Affiliates.
 * Version: 3.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_MAILCHIMP_PLUGIN_DOMAIN', 'affiliates-mailchimp' );
define( 'AFFILIATES_MAILCHIMP_FILE', __FILE__ );
define( 'AFFILIATES_MAILCHIMP_CORE_DIR', WP_PLUGIN_DIR . '/affiliates-mailchimp' );

require_once 'class-affiliates-mailchimp.php';
