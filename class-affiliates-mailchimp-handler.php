<?php
/**
 * class-affiliates-mailchimp-handler.php
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
 * Affiliates Mailchimp class
 */
class Affiliates_Mailchimp_Handler {

	/**
	 * Initialize the Class
	 */
	public static function init() {
		if ( !class_exists( 'Affiliates_Mailchimp_Api' ) ) {
			require_once 'api/v3/class-affiliates-mailchimp-api.php';
		}
		if ( !class_exists( 'Affiliates_Mailchimp_Exception' ) ) {
			require_once 'api/v3/class-affiliates-mailchimp-exception.php';
		}
		add_action( 'affiliates_added_affiliate', array( __CLASS__, 'affiliates_added_affiliate' ) );
		add_action( 'affiliates_updated_affiliate', array( __CLASS__, 'affiliates_updated_affiliate' ) );
		add_action( 'affiliates_deleted_affiliate', array( __CLASS__, 'affiliates_deleted_affiliate' ) );
		add_action( 'affiliates_updated_affiliate_status', array( __CLASS__, 'affiliates_updated_affiliate_status' ), 10, 3 );
	}

	/**
	 * New Affiliate
	 *
	 * @param int $affiliate_id
	 */
	public static function affiliates_added_affiliate( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user( $affiliate_id );
		if ( $user_id != null ) {
			$user_data = self::get_user_data( $user_id );
			self::manage_subscriber( $user_id, $user_data, true );
		} else {
			$affiliate_data = self::get_affiliate_data( $affiliate_id );
			self::manage_subscriber( $affiliate_id, $affiliate_data, true );
		}
	}

	/**
	 * Updated Affiliate, this is triggered only when affiliate data are updated.
	 *
	 * @param int $affiliate_id
	 */
	public static function affiliates_updated_affiliate( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user( $affiliate_id );
		if ( $user_id != null ) {
			$user_data = self::get_user_data( $user_id );
			self::manage_subscriber( $user_id, $user_data, false );
		} else {
			$affiliate_data = self::get_affiliate_data( $affiliate_id );
			self::manage_subscriber( $affiliate_id, $affiliate_data, false );
		}
	}

	/**
	 * Deleted Affiliate
	 *
	 * @param int $affiliate_id
	 */
	public static function affiliates_deleted_affiliate( $affiliate_id ) {
		self::affiliate_update_subscription_status( $affiliate_id, true );
	}

	/**
	 * Affiliate status updated
	 *
	 * @param string $affiliate_id
	 * @param string $old_status
	 * @param string $status
	 */
	public static function affiliates_updated_affiliate_status( $affiliate_id, $old_status, $status ) {
		switch ( $status ) {
			case 'active' :
			case 'pending' :
				$user_id = affiliates_get_affiliate_user( $affiliate_id );
				self::affiliate_update_subscription_status( $user_id, false );
				break;
			case 'deleted' :
				self::affiliate_update_subscription_status( $affiliate_id, true );
				break;
			default :
				$user_id = affiliates_get_affiliate_user( $affiliate_id );
				self::affiliate_update_subscription_status( $user_id, false );
				break;
		}
	}

	/**
	 * Updates subscription status according to the affiliate's choice in the frontend,<br>
	 * the affiliate's status( active, pending, deleted ), or when the affiliate is deleted.
	 * The affiliate can update subscription status in the front-end through <br>
	 * [affiliates_mailchimp_subscription] form.
	 *
	 * @param int $user_id
	 * @param bool $deleted whether this is a subscription update(false), or a deleted user/affiliate(true)
	 */
	public static function affiliate_update_subscription_status( $user_id, $deleted = false ) {
		$options = array();
		$options = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$list_id     = isset( $options['list_id'] ) ? $options['list_id'] : null;
			$interest_id = isset( $options['interest_id'] ) ? $options['interest_id'] : null;

			// When a list member is deleted, can be re-added only through the
			// supported forms offered by MC in the list dashboard, under
			// <i>Signup forms</i>.
			// Here we choose to unsubscribe an affiliate whenever is deleted
			// to avoid such inconsistencies.
			// In this case the user_id is actually the affiliate_id
			// because of the $deleted flag and is treated as such
			if ( $deleted ) {
				$status = 'unsubscribed';
				$user_data = self::get_affiliate_data( $user_id );
			} else {
				$user_data = self::get_user_data( $user_id );
				if ( count( $user_data ) > 0 ) {
					$subscription_status = get_user_meta( $user_id, 'aff_mailchimp_subscription', true );
					if ( $subscription_status == '1' ) {
						$status = 'subscribed';
					} else {
						$status = 'unsubscribed';
					}
				}
			}

			if ( count( $user_data ) > 0 ) {
				$api = new Affiliates_Mailchimp_Api( $options['api_key'] );
				if ( isset( $list_id ) ) {
					$check = $api->member( $list_id, $user_data['email'] );
					if ( isset( $check ) ) {
						$api->update(
							$list_id,
							$user_data['email'],
							$status,
							array(
								'FNAME' => $user_data['first_name'],
								'LNAME' => $user_data['last_name']
							),
							array( $interest_id => true )
						);
					}
				}
			}
		}
	}

	/**
	 * Subscribe a new user to the mail list.
	 * Update an existing user when name, surname or status change
	 *
	 * @param int $user_id
	 * @param array $user_info
	 * @param bool $status_update true for a new affiliate, false for updating personal data
	 */
	public static function manage_subscriber( $user_id, $user_info, $status_update ) {
		$options = array();
		$options = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$need_confirm       = $options['need_confirm'];
			$list_id            = isset( $options['list_id'] ) ? $options['list_id'] : null;
			$interest_id        = isset( $options['interest_id'] ) ? $options['interest_id'] : null;

			$status = true;
			if (
				( $need_confirm ) ||
				( isset( $user_info['status'] ) && $user_info['status'] == 'pending' )
			) {
				$status = false;
			}

			$api = new Affiliates_Mailchimp_Api( $options['api_key'] );

			if ( isset( $list_id ) ) {
				// check if the user belongst to the list
				if ( $user_info ) {
					$check = $api->member( $list_id, $user_info['email'] );
					if ( isset( $check ) ) {
						// update existing subscriber
						// it will only update personal user data
						if ( is_array( $check ) ) {
							if ( isset( $check['status'] ) ) {
								switch ( $check['status'] ) {
									case 'subscribed' :
										$status = true;
										break;
									case 'unsubscribed' :
										$status = false;
										break;
									case 'cleaned' :
										$status = null;
										break;
									default :
										$status = true;
								}
								// affiliate exists in the list
								// but is unsubscribed
								if ( $status_update ) {
									$status = true;
								}
								$api->update(
									$list_id,
									$user_info['email'],
									$status,
									array(
										'FNAME' => $user_info['first_name'],
										'LNAME' => $user_info['last_name']
									),
									array( $interest_id => true )
								);
							}
						}
					} else {
						// add new subscriber
						$api->subscribe(
							$list_id,
							$user_info['email'],
							$status, // true => subscribed, false => pending
							array(
								'FNAME' => $user_info['first_name'],
								'LNAME' => $user_info['last_name']
							),
							array( $interest_id => true )
						);
					}
				}
			} // list id
		}
	}

	/**
	 * Add existing affiliates to mailchimp list
	 */
	public static function synchronize() {
		$affiliates = affiliates_get_affiliates();
		if ( count( $affiliates ) > 0 ) {
			error_log( 'Affiliates MailChimp will try to add ' . count( $affiliates ) . ' affiliates.' );
			foreach ( $affiliates as $affiliate ) {
				if ( $affiliate['affiliate_id'] != 1 ) {
					$user_data = array(
						'email'      => $affiliate['email'],
						'first_name' => $affiliate['name'],
						'last_name'  => $affiliate['name'],
					);
					error_log( 'Affiliates MailChimp is adding affiliate with ID ' . esc_attr( $affiliate['affiliate_id'] ) );
					self::manage_subscriber( $affiliate['affiliate_id'], $user_data, true );
				}
			}
		}
	}

	/**
	 * Get User Data by user id
	 *
	 * @param int $user_id
	 * @return NULL|array
	 */
	private static function get_user_data( $user_id ) {
		$result = null;
		if ( get_userdata( $user_id ) ) {
			$user_data = get_userdata( $user_id );
			$result = array(
				'email'      => $user_data->user_email,
				'first_name' => $user_data->first_name,
				'last_name'  => $user_data->last_name,
				'status'     => get_option( 'aff_status', 'active' )
			);
		}
		return $result;
	}

	/**
	 * Get Affiliate Data by affiliate id
	 *
	 * @param int $aff_id
	 * @return NULL|array
	 */
	private static function get_affiliate_data( $aff_id ) {
		$result = null;

		$aff_data = affiliates_get_affiliate( $aff_id );
		if ( isset( $aff_data ) ) {
			$result = array(
				'email'      => $aff_data['email'],
				'first_name' => $aff_data['name'],
				'last_name'  => $aff_data['name'],
				'status'     => $aff_data['status']
			);
		}
		return $result;
	}

} Affiliates_Mailchimp_Handler::init();
