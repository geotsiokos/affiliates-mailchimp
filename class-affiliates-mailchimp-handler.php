<?php
use Monolog\Handler\NullHandler;

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
		}//self::check_requests();
		add_action( 'affiliates_added_affiliate', array( __CLASS__, 'affiliates_added_affiliate' ) );
		add_action( 'affiliates_updated_affiliate', array( __CLASS__, 'affiliates_updated_affiliate' ) );
		add_action( 'affiliates_deleted_affiliate', array( __CLASS__, 'affiliates_deleted_affiliate' ) );
		add_action( 'affiliates_updated_affiliate_status', array( __CLASS__, 'affiliates_updated_affiliate_status' ), 10, 3 );//intval( $affiliate_id ), $old_status, $status );
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
			self::manage_subscriber( $user_id, $user_data );
		} else {
			$affiliate_data = self::get_affiliate_data( $affiliate_id );
			self::manage_subscriber( $affiliate_id, $affiliate_data );
		}
	}

	/**
	 * Updated Affiliate
	 *
	 * @param int $affiliate_id
	 */
	public static function affiliates_updated_affiliate( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user( $affiliate_id );
		if ( $user_id != null ) {
			$user_data = self::get_user_data( $user_id );
			self::manage_subscriber( $user_id, $user_data );
		} else {
			$affiliate_data = self::get_affiliate_data( $affiliate_id );
			self::manage_subscriber( $affiliate_id, $affiliate_data );
		}
	}

	/**
	 * Deleted Affiliate
	 *
	 * @param int $affiliate_id
	 */
	public static function affiliates_deleted_affiliate( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user( $affiliate_id );
		if ( $user_id != null ) {
			$user_data = self::get_user_data( $user_id );
			self::delete_subscriber( $user_id, $user_data );
		} else {
			$affiliate_data = self::get_affiliate_data( $affiliate_id );
			self::delete_subscriber( $affiliate_id, $affiliate_data );
		}
	}

	/**
	 * Affiliate status updated
	 *
	 * @param string $affiliate_id
	 * @param string $old_status
	 * @param string $status
	 */
	public static function affiliates_updated_affiliate_status( $affiliate_id, $old_status, $status ) {
		$affiliate_data = self::get_affiliate_data( $affiliate_id );
		self::manage_subscriber( $affiliate_id, $affiliate_data );
	}

	/**
	 * Subscribe a new user to the mail list
	 * Update an existing user when name, surname or status change
	 *
	 * @param int $user_id
	 * @param array $user_info
	 */
	public static function manage_subscriber( $user_id, $user_info ) {
		$options = array();
		$options = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$need_confirm       = $options['need_confirm'];
			$list_id            = isset( $options['list_id'] ) ? $options['list_id'] : null;
			$category_id        = isset( $options['category_id'] ) ? $options['category_id'] : null;
			$interest_id        = isset( $options['interest_id'] ) ? $options['interest_id'] : null;

			$status = 'subscribed';
			if ( 
				( isset( $user_info['status'] ) && $user_info['status'] == 'pending' ) ||
				( $need_confirm )
			) {
				$status = 'pending'; //subscribed unsubscribed cleaned pending //affiliate status active pending
			}

			$api = new Affiliates_Mailchimp_Api( $options['api_key'] );
			$data = array(
				'fields' => 'lists.name,lists.id',
				'count'  => 'all'
			);

			if ( isset( $list_id ) ) {
				if ( $user_info ) {
					// Check if user belongs to list
					$user_data = array(
						'email_address' => $user_info['email'],
						'status'        => $status,
						'merge_fields'  => array(
							'FNAME' => $user_info['first_name'],
							'LNAME' => $user_info['last_name']
						)
					);
					$check = $api->check_list( array( 'list_id' => $list_id ), $user_data );
					
					// Prepare request URL path parameters
					$interest_parameters = array(
						'list_id'              => $list_id,
						'interest_category_id' => $category_id
					);

					// Patch user data array with interest_id
					$user_data['interests'] = array( $interest_id => true );

					// if user is unsubscribed, patch the list
					// else add him as new subscriber
					if ( isset( $check['status'] ) ) {
						if ( $check['status'] == ( 'unsubscribed' || 'pending' ) ) {
							// update existing subscriber
							$user_data['status'] = 'subscribed';
							$api->update_subscriber( $interest_parameters, $user_data );
						}
					} else {
						// add new subscriber
						$api->new_subscriber( $interest_parameters, $user_data );
					}
				}
			}
		}
	}

	/**
	 * Unsubscribe a user from the mail list
	 *
	 * @param int $user_id
	 * @param array $user_info
	 */
	public static function delete_subscriber( $user_id, $user_info ) {
		$options = array();
		$options = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$list_id   = isset( $options['list_id'] ) ? $options['list_id'] : null;

			$api = new Affiliates_Mailchimp_Api( $options['api_key'] );

			if ( isset( $list_id ) ) {
				if ( $user_info ) {
					// Check if user belongs to list
					$user_data = array(
						'email_address' => $user_info['email'],
						'status'        => 'subscribed',
						'merge_fields'  => array(
							'FNAME' => $user_info['first_name'],
							'LNAME' => $user_info['last_name']
						)
					);
					$check = $api->check_list( array( 'list_id' => $list_id ), $user_data );
					if ( isset( $check['status'] ) ) {
						if ( $check['status'] == 'subscribed' ) {
							$api->delete_subscriber( array( 'list_id' => $list_id ), $user_data );
						}
					}
				}
			}
		}
	}

	/**
	 * Add existing affiliates to mailchimp list
	 * @todo check syncing
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
					self::manage_subscriber( $affiliate['affiliate_id'], $user_data );
				}
			}
		}
	}

	/**
	 * Get the id from an array of results.
	 * Results can be lists, interest_categories, interests
	 *
	 * @param string $id_type one of lists, categories, interests
	 * @param array $results_list
	 * @param string $option_name name of id_type
	 * @return NULL|string
	 */
	private static function get_id( $id_type = null, $results_list = array(), $option_name = '' ) {
		$result = null;
		if ( isset( $id_type ) ) {
			if ( is_array( $results_list ) ) {
				foreach ( $results_list[$id_type] as $list ) {
					if ( in_array( $option_name, $list ) ) {
						$result = $list['id'];
					}
				}
			}
		}
		return $result;
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
		if ( affiliates_get_affiliate( $aff_id ) ) {
			$aff_data = affiliates_get_affiliate( $aff_id );
			if ( isset( $aff_data ) ) {
				$result = array(
					'email'      => $aff_data['email'],
					'first_name' => $aff_data['name'],
					'last_name'  => $aff_data['name'],
					'status'     => $aff_data['status']
				);
			}
		}
		return $result;
	}

	/**
	 * Get id, wrapper for lists.
	 *
	 * @return NULL|string
	 */
	private static function get_list_id() {
		$list_id = null;
		$options = get_option( 'affiliates-mailchimp' );
		
		if ( $options['api_key'] ) {
			$api = new Affiliates_Mailchimp_Api( $options['api_key'] );

			$list_name = $options['list_name'];
			$list_data = array(
				'fields' => 'lists.name,lists.id',
				'count'  => 'all'
			);
			$lists = $api->get_lists( array(), $list_data );
			if ( is_array ( $lists ) ) {
				$list_id = self::get_id( 'lists', $lists, $list_name );
			}
		}
		return $list_id;
	}

	/**
	 * Get id, wrapper for list category
	 *
	 * @param string $list_id
	 * @return NULL|string
	 */
	private static function get_category_id( $list_id ) {
		$list_category_id = null;
		if ( isset( $list_id ) ) {
			$options = get_option( 'affiliates-mailchimp' );
			
			if ( $options['api_key'] ) {
				$category_name = $options['interests_category'];
	
				$api = new Affiliates_Mailchimp_Api( $options['api_key'] );
	
				$categories_fields = array( 'fields' => 'categories.title, categories.id' );
	
				$list_categories = $api->get_lists( array( 'list_id' => $list_id ), $categories_fields );
				if ( is_array ( $list_categories ) ) {
					$list_category_id = self::get_id( 'categories', $list_categories, $category_name );
				}
				if ( !isset( $list_category_id ) ) {
					// create the Interest Category
					// and get the ID
					$interests_cat_parameters = array(
						'title' => $category_name,
						'type'  => 'checkboxes'
					);
					$result           = $api->add_interest_category( array( 'list_id' => $list_id ), $interests_cat_parameters );
					$list_categories  = $api->get_lists( array( 'list_id' => $list_id ), $categories_fields );
					$list_category_id = self::get_id( 'categories', $list_categories, $category_name );
				}
			}
		}
		return $list_category_id;
	}

	/**
	 * Get id, wrapper for interest id
	 */
	private static function get_interest_id( $list_id = null, $category_id = null ) {
		$interest_id = null;
		$options = get_option( 'affiliates-mailchimp' );

		if ( isset( $list_id ) && isset( $category_id ) ) {
			if ( $options['api_key'] ) {
				$interest    = $options['interest'];
	
				$api = new Affiliates_Mailchimp_Api( $options['api_key'] );
	
				$interest_parameters = array(
					'list_id'              => $list_id,
					'interest_category_id' => $category_id
				);
	
				// For the set category, get Interests
				// If the Interest category doesn't exist
				// create it
				$interests = $api->get_lists( $interest_parameters, array() );
				if ( isset( $interests[$interest] ) ) {
					if ( count( $interests[$interest] ) > 1 ) {
						$interest_id = self::get_id( 'interests', $interests, $interest );
					}
				}
	
				if ( !isset( $interest_id ) ) {
					$interest_params = array( 'name' => $interest );
					$api->add_interest( $interest_parameters, $interest_params );
					$interests   = $api->get_lists( $interest_parameters, array() );
					$interest_id = self::get_id( 'interests', $interests, $interest );
				}
			}
		}
		return $interest_id;
	}

	/**
	 * Stores List, Category and Interest IDs
	 *
	 * @param string $list_name
	 * @return NULL|array $options with ids
	 */
	public static function set_ids( $list_name ){
		$options = null;
		if ( isset( $list_name ) ) {
			$list_id = self::get_list_id();
			$options['list_id'] = isset( $list_id ) ? $list_id : null;
			if ( isset( $list_id ) ) {
				$category_id = self::get_category_id( $list_id );
				$options['category_id'] = isset( $category_id ) ? $category_id : null;
				if ( isset( $category_id ) ) {
					$interest_id = self::get_interest_id( $list_id, $category_id );
					$options['interest_id'] = isset( $interest_id ) ? $interest_id : null;
				}
			}
		}
		return $options;
	}
} Affiliates_Mailchimp_Handler::init();
