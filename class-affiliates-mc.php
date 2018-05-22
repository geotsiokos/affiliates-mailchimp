<?php
//use Mailchimp\MailchimpLists;

/**
 * class-affiliates-mailchimp.php
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
		//add_action( 'user_register', array( __CLASS__, 'user_register' ) );
		//add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
		//add_action( 'personal_options_update', array( __CLASS__, 'edit_user_profile_update' ) );
		//add_action( 'delete_user', array( __CLASS__, 'delete_user' ) );
		//add_action( 'set_user_role', array( __CLASS__, 'edit_user_profile_update' ) );
		// affiliates
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
		$user_id = null; //affiliates_get_affiliate_user( $affiliate_id );
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

	// @todo start reviewing this method which applies after affiliates_added_affiliate
	// action fires and the new affiliate has a user related
	/**
	 * Subscribe a new user to the mail list
	 * Update an existing user when name, surname change
	 *
	 * @param int $user_id
	 * @param array $user_info
	 */
	public static function manage_subscriber( $user_id, $user_info ) {
		// get the apikey directly from mc4wp
		$mailchimp_options = get_option( 'mc4wp', array() );

		if ( $mailchimp_options['api_key'] ) {
			$options = get_option( 'affiliates-mailchimp' );
			$list_name          = $options['list_name'];
			$interests_category = $options['interests_category'];
			$interest           = $options['interest'];

			$api = new Mailchimp_Api( $mailchimp_options['api_key'] );
			$data = array(
				'fields' => 'lists.name,lists.id',
				'count' => 'all'
			);

			$lists     = $api->get_lists( array(), $data );
			$list_id   = self::get_id( 'lists', $lists, $list_name );
			//$user_info = self::get_user_data( $user_id );

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
						'list_id' => $list_id,
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
						// add
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
		// get the apikey directly from mc4wp
		$mailchimp_options = get_option( 'mc4wp', array() );

		if ( $mailchimp_options['api_key'] ) {
			$options   = get_option( 'affiliates-mailchimp' );
			$list_name = $options['list_name'];

			$api = new Mailchimp_Api( $mailchimp_options['api_key'] );
			$data = array(
				'fields' => 'lists.name,lists.id',
				'count' => 'all'
			);

			$lists     = $api->get_lists( array(), $data );
			$list_id   = self::get_id( 'lists', $lists, $list_name );
			//$user_info = self::get_user_data( $user_id );

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


	public static function synchronize() {
/*
		$apikey = get_option( 'affiliates_mailchimp-api_key' );
		$listname = get_option( 'affiliates_mailchimp-list' );
		$groupname = get_option( 'affiliates_mailchimp-group' );
		$subgroupname = get_option( 'affiliates_mailchimp-subgroup' );
		$needconfirm = get_option( 'affiliates_mailchimp-needconfirm' );

		$api = new MCAPI( $apikey );

		$retval = $api->lists();

		$lists = $retval['data'];

		$myList = null;

		if ( count( $lists ) > 0 ) {
			foreach ( $lists as $list ) {
				if ( $list['name'] == $listname ) {
					$myList = $list;
				}
			}
		}

		if ( $myList !== null ) {
			$groups = $api->listInterestGroupings( $myList['id'] );

			if ( $groups ) {
				$groupingid = 0;
				$myGroup = null;
				foreach ( $groups as $group ) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
						$myGroup = $group;
					}
				}

				if ( $groupingid !== 0 ) {

					$affiliates = affiliates_get_affiliates();

					foreach ( $affiliates as $aff ) {

						// if subgroups not already exist, then create
						$subgroupsmc = $myGroup['groups'];
						$testGroups = explode( ',', $subgroupname );
						foreach ( $testGroups as $test ) {
							if ( !in_array( $test, $subgroupsmc ) ) {
								$api->listInterestGroupAdd( $myList['id'], $test );
							}
						}

						$merge_vars = array(
							'FNAME' => $aff['name'],
							'LNAME' => '',
							'EMAIL' => $aff['email'],
							'GROUPINGS' => array(
								array(
									'name' => $groupname,
									'groups' => $subgroupname
								),
							)
						);

						$users_data[] = $merge_vars;

					}
					$optin = $needconfirm; //yes, send optin emails
					$up_exist = true; // yes, update currently subscribed users
					$replace_int = true; // no, add interest, don't replace

					$api->listBatchSubscribe( $myList['id'],$users_data,$optin, $up_exist, $replace_int );

					if ( $api->errorCode ) {
						error_log( $api->errorMessage );
					}
				}
			}
		}*/
	}

	public static function toAffiliates() {
/*
		$apikey = get_option( 'affiliates_mailchimp-api_key' );
		$listname = get_option( 'affiliates_mailchimp-list' );
		$groupname = get_option( 'affiliates_mailchimp-group' );
		$subgroupname = get_option( 'affiliates_mailchimp-subgroup' );
		$needconfirm = get_option( 'affiliates_mailchimp-needconfirm' );

		$api = new MCAPI( $apikey );

		$retval = $api->lists();

		$lists = $retval['data'];

		$myList = null;

		if ( count( $lists ) > 0 ) {
			foreach ( $lists as $list ) {
				if ( $list['name'] == $listname ) {
					$myList = $list;
				}
			}
		}

		if ( $myList !== null ) {
			$members = $api->listMembers( $myList['id'] );

			if ( $members ) {
				foreach ( $members['data'] as $member ) {
					$datavalues['email'] = $member['email'];
					self::create_an_affiliate( $datavalues );
				}
			}
		}*/
	}


	public function create_an_affiliate( $datavalues ) {
/*		global $wpdb;
		$result = true;

		if ( !current_user_can( AFFILIATES_ADMINISTER_AFFILIATES ) ) {
			wp_die( __( 'Access denied.', AFFILIATES_PLUGIN_DOMAIN ) );
		}

		$affiliates_table = _affiliates_get_tablename( 'affiliates' );
		$affiliates_users_table = _affiliates_get_tablename( 'affiliates_users' );

		if ( isset( $datavalues['email'] ) ) {
			$name = isset( $datavalues['name'] ) ? $datavalues['name'] : $datavalues['email'];

			if ( !empty( $name ) ) {

				$data = array(
					'name' => $name
				);
				$formats = array( '%s' );

				$email = trim( $datavalues['email'] );
				if ( is_email( $email ) ) {
					$data['email'] = $email;
					$formats[] = '%s';
				} else {
					$data['email'] = null; // (*)
					$formats[] = 'NULL'; // (*)
				}

				$data['from_date'] = date( 'Y-m-d', time() );
				$formats[] = '%s';

				$data['thru_date'] = null; // (*)
				$formats[] = 'NULL'; // (*)

				$data_ = array();
				$formats_ = array();
				foreach ( $data as $key => $value ) { // (*)
					if ( $value ) {
						$data_[$key] = $value;
					}
				}
				foreach ( $formats as $format ) { // (*)
					if ( $format != 'NULL' ) {
						$formats_[] = $format;
					}
				}
				if ( $wpdb->insert( $affiliates_table, $data_, $formats_ ) ) {
					$affiliate_id = $wpdb->get_var( 'SELECT LAST_INSERT_ID()' );
				}

				// hook
				if ( !empty( $affiliate_id ) ) {
					do_action( 'affiliates_added_affiliate', intval( $affiliate_id ) );
				}
			} else {
				$result = false;
			}
			return $result;
		}*/
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
