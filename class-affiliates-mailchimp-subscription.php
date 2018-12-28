<?php
/**
 * class-affiliates-mailchimp-subscription.php
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
 * Class Affiliates_Mailchimp_Subscription
 */
class Affiliates_Mailchimp_Subscription {

	/**
	 * Init
	 */
	public static function init() {
		add_shortcode( 'affiliates_mailchimp_subscription', array( __CLASS__, 'affiliates_mailchimp_subscription_form' ) );
	}

	/**
	 * Shortcode method
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function affiliates_mailchimp_subscription_form( $atts ) {
		$output = '';
		if ( class_exists( 'Affiliates_Shortcodes' ) ) {
			$affiliate_id = Affiliates_Shortcodes::affiliates_id( array() );
			if ( $affiliate_id != '' ) {
				$user_id = affiliates_get_affiliate_user( intval( $affiliate_id ) );
				$aff_subscription_status = get_user_meta( $user_id, 'aff_mailchimp_subscription', true );
				$aff_status_description = '';
				if ( $aff_subscription_status == '1' ) {
					$aff_status_description = esc_html__( 'You are subscribed to the mailing list. If you wish to stop receiving newsletters, please select NO and click on the SAVE button.', 'affiliates-mailchimp' );
				} else {
					$aff_status_description = esc_html__( 'If you wish to subscribe to the mailing list, please select YES and click on the SAVE button.', 'affiliates-mailchimp' );
				}

				if ( isset( $_POST['aff_subscription'] ) ) {
					if ( wp_verify_nonce( $_POST['aff-mailchimp-subscribe-nonce'], 'aff-mailchimp-subscribe-setting' ) ) {
						update_user_meta( $user_id, 'aff_mailchimp_subscription', !empty( $_POST['subscription_option'] ) ? 1 : 0 );
						Affiliates_Mailchimp_Handler::affiliate_update_subscription( $user_id, false );
					}
				}
				$output .= '<form method="post" name="options" action="">';

				$output .= '<p>';
				$output .= $aff_status_description;
				$output .= '</p>';
				$output .= '<select name="subscription_option">';

				$output .= '<option value="1" ' . ( $aff_subscription_status == '1' ? 'SELECTED' : '' ) . '>';
				$output .= esc_html__( 'YES', 'affiliates-mailchimp' );
				$output .= '</option>';

				$output .= '<option value="0" ' . ( $aff_subscription_status == '0' ? 'SELECTED' : '' ) . '>';
				$output .= esc_html__( 'NO','affiliates-mailchimp' );
				$output .= '</option>';

				$output .= '</select>';

				$output .= wp_nonce_field( 'aff-mailchimp-subscribe-setting', 'aff-mailchimp-subscribe-nonce', true, false );
				$output .= '&nbsp;&nbsp;';
				$output .= '<input id="affiliate-subscription" class="button" type="submit" name="aff_subscription" value="' . esc_attr__( 'Save', 'affiliates-mailchimp' ) . '">';
				$output .= '</p>';

				$output .= '</form>';
			}
		}
		return $output;
	}
} Affiliates_Mailchimp_Subscription::init();
