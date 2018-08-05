<?php
/**
 * class-affiliates-mc.php
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
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Affiliates Mailchimp class
 */
class Affiliates_Mc {

	/**
	 * Initialize the Class
	 */
	public static function init() {
		if ( !class_exists( 'Mailchimp_Api' ) ) {
			require_once 'api/v3/class-mailchimp-api.php';
		}
		add_action( 'affiliates_added_affiliate', array( __CLASS__, 'affiliates_added_affiliate' ) );
		add_action( 'affiliates_updated_affiliate', array( __CLASS__, 'affiliates_updated_affiliate' ) );
		add_action( 'affiliates_deleted_affiliate', array( __CLASS__, 'affiliates_deleted_affiliate' ) );
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
		$user_id = null;
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
	 * Subscribe a new user to the mail list
	 * Update an existing user when name, surname change
	 *
	 * @param int $user_id
	 * @param array $user_info
	 */
	public static function manage_subscriber( $user_id, $user_info ) {
		$options = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$list_name          = $options['list_name'];
			$interests_category = $options['interests_category'];
			$interest           = $options['interest'];
			$need_confirm       = $options['need_confirm'];

			$api = new Mailchimp_Api( $mailchimp_options['api_key'] );
			$data = array(
				'fields' => 'lists.name,lists.id',
				'count'  => 'all'
			);

			$lists     = $api->get_lists( array(), $data );
			$list_id   = self::get_id( 'lists', $lists, $list_name );

			if ( $list_id ) {
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

					// Get Interest Categories
					$categories_fields = array(
						'fields' => 'categories.title, categories.id'
					);
					$list_parameters = array(
						'list_id' => $list_id
					);

					// For the set category, get Interests
					// If the Interest category doesn't exist
					// create it
					$list_categories = $api->get_lists( $list_parameters, $categories_fields );
					if ( isset( $list_categories[$interests_category] ) ) {
						if ( count( $list_categories[$interests_category] ) > 1 ) {
							$category_id = self::get_id( 'categories', $list_categories, $interests_category );
						}
					}

					if ( !isset( $category_id ) ) {
						// create the Interest Category
						// and get the ID
						$interests_cat_parameters = array(
							'title' => $interests_category,
							'type'  => 'checkboxes'
						);
						$result = $api->add_interest_category( $list_parameters, $interests_cat_parameters );
						$list_categories = $api->get_lists( $list_parameters, $categories_fields );
						$category_id     = self::get_id( 'categories', $list_categories, $interests_category );
					}
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
						$interest_params = array(
							'name' => $interest
						);
						$api->add_interest( $interest_parameters, $interest_params );
						$interests   = $api->get_lists( $interest_parameters, array() );
						$interest_id = self::get_id( 'interests', $interests, $interest );
					}
					$user_data = array(
						'email_address' => $user_info['email'],
						'status'        => 'subscribed',
						'merge_fields'  => array(
							'FNAME' => $user_info['first_name'],
							'LNAME' => $user_info['last_name']
						),
						'interests' => array( $interest_id => true )
					);

					// if user is unsubscribed, patch the list
					// else add him as new subscriber
					if ( isset( $check['status'] ) ) {
						if ( $check['status'] == ( 'unsubscribed' || 'subscribed' ) ) {
							// patch
							$api->update_subscriber( $interest_parameters, $user_data );
						}
					} else {
						// check the opt-in option
						if ( $need_confirm == '1' ) {
							$user_data['status'] = 'pending';
						}
						// add new subscriber
						$api->new_subscriber( $interest_parameters, $user_data );
					}
				}
			} // list_id
		} // apikey
	}

	/**
	 * Unsubscribe a user from the mail list
	 *
	 * @param int $user_id
	 * @param array $user_info
	 */
	public static function delete_subscriber( $user_id, $user_info ) {
		$options   = get_option( 'affiliates-mailchimp' );

		if ( $options['api_key'] ) {
			$options   = get_option( 'affiliates-mailchimp' );
			$list_name = $options['list_name'];

			$api = new Mailchimp_Api( $mailchimp_options['api_key'] );
			$data = array(
				'fields' => 'lists.name,lists.id',
				'count' => 'all'
			);

			$lists     = $api->get_lists( array(), $data );
			$list_id   = self::get_id( 'lists', $lists, $list_name );

			if ( $list_id ) {
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
							$user_data = array(
								'email_address' => $user_info['email'],
								'status'        => 'unsubscribed',
							);
							$api->update_subscriber( array( 'list_id' => $list_id ), $user_data );
						}
					}
				}
			}
		}
	}

	/**
	 * Add existing affiliates to mailchimp list
	 */
	public static function synchronize() {
		$affiliates = affiliates_get_affiliates();
		if ( count( $affiliates ) > 0 ) {
			foreach ( $affiliates as $affiliate ) {
				if ( $affiliate['affiliate_id'] != 1 ) {
					$user_data = array(
						'email'      => $affiliate['email'],
						'first_name' => $affiliate['name'],
						'last_name'  => $affiliate['name']
					);
					self::manage_subscriber( $affiliate['affiliate_id'], $user_data );
				}
			}
		}
	}

	/**
	 * Get the id from an array of results.
	 * Results can be lists, interest_categories, interests
	 *
	 * @param string $id_type
	 * @param array $results_list
	 * @param string $option_name
	 * @return boolean|string
	 */
	private static function get_id( $id_type = null, $results_list = array(), $option_name = '' ) {
		$result = false;
		if ( isset( $id_type ) ) {
			foreach ( $results_list[$id_type] as $list ) {
				if ( in_array( $option_name, $list ) ) {
					$result = $list['id'];
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
				'last_name'  => $user_data->last_name
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
			$result = array(
				'email'      => $aff_data['email'],
				'first_name' => $aff_data['name'],
				'last_name'  => $aff_data['name']
			);
		}
		return $result;
	}
} Affiliates_Mc::init();
