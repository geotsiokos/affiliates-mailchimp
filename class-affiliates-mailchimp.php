<?php
/**
 * class-affiliates-mailchimp.php
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
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Affiliates Mailchimp class
 */
class Affiliates_MailChimp {

	public static function init() {
		if ( !class_exists( 'MCAPI' ) ) {
			require_once 'api/v1/MCAPI.class.php';
		}
		add_action('user_register', array( __CLASS__, 'user_register' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'edit_user_profile_update' ) );
		add_action( 'delete_user', array( __CLASS__, 'delete_user' ) );
		add_action( 'set_user_role', array( __CLASS__, 'edit_user_profile_update' ) );
		// affiliates
		add_action('affiliates_added_affiliate', array( __CLASS__, 'affiliates_added_affiliate' ) );
		add_action('affiliates_updated_affiliate', array( __CLASS__, 'affiliates_updated_affiliate' ) );
		add_action('affiliates_deleted_affiliate', array( __CLASS__, 'affiliates_deleted_affiliate' ) );
	}

	public static function affiliates_added_affiliate ( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user($affiliate_id);
		if ($user_id != null)
			self::user_register($user_id);
		else { 
			self::affiliate_register($affiliate_id);
		}
	}

	public static function affiliates_updated_affiliate ( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user($affiliate_id);
		if ($user_id != null)
			self::edit_user_profile_update($user_id);
		else
			self::affiliate_updated($affiliate_id);
	}

	public static function affiliates_deleted_affiliate ( $affiliate_id ) {
		$user_id = affiliates_get_affiliate_user($affiliate_id);
		if ($user_id != null)
			self::delete_user($user_id);
		else
			self::affiliate_deleted($affiliate_id);
	}

	
	// @todo start reviewing this method which applies after affiliates_added_affiliate
	//       action fires and the new affiliate has a user related
	public static function user_register ( $user_id ) {

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');

		// subgroup
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$user_info = get_userdata( $user_id );
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);
		$retval = $api->lists();
		if ( $api->errorCode ) {
			error_log($api->errorMessage);
		} else {

			$lists = $retval["data"];

			$myList = null;

			if ( count ( $lists ) > 0 ) {
				foreach ($lists as $list) {
					if ($list["name"] == $listname)
						$myList = $list;
				}
			}

			if ($myList !== null) {
// 				$groups = $api->listInterestGroupings($myList["id"]);

// 				if ($groups) {
// 					$groupingid = 0;
// 					$myGroup = null;
// 					foreach ($groups as $group) {
// 						if ( $group['name'] == $groupname ) {
// 							$groupingid = $group['id'];
// 							$myGroup = $group;
// 						}
// 					}

// 					if ($groupingid !== 0) { // if exist the grouping

						// if subgroups not already exist, then create
// 						$subgroupsmc = $myGroup['groups'];
// 						$testGroups = explode(",", $subgroupname);
// 						foreach ($testGroups as $test) {
// 							if ( !in_array($test, $subgroupsmc) ) {
// 								$api->listInterestGroupAdd($myList["id"], $test);
// 							}
// 						}

						$merge_vars = array(
								'FNAME'=>$user_info->user_firstname,
								'LNAME'=>$user_info->user_lastname
// 								,
// 								'GROUPINGS'=>array(
// 										array('name'=>$groupname, "groups"=>$subgroupname),
// 								)
						);

						// By default this sends a confirmation email - you will not see new members
						// until the link contained in it is clicked!
						$retval = $api->listSubscribe( $myList["id"], $user_info->user_email, $merge_vars, "html", $needconfirm );
error_log(__METHOD__. ' retval = ' . var_export($retval,true)); // @todo remove
						if ( $api->errorCode ) {
							error_log($api->errorMessage);
						}
// 					} 

// 				}
			}
		}
	}

	public static function affiliate_register ( $aff_id ) {

		$aff = Affiliates_Affiliate::get_affiliate($aff_id);

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');

		// subgroup
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);
		$retval = $api->lists();
		if ( $api->errorCode ) {
			error_log($api->errorMessage);
		} else {

			$lists = $retval["data"];

			$myList = null;

			if ( count ( $lists ) > 0 ) {
				foreach ($lists as $list) {
					if ($list["name"] == $listname)
						$myList = $list;
				}
			}

			if ($myList !== null) {
				$groups = $api->listInterestGroupings($myList["id"]);

				if ($groups) {
					$groupingid = 0;
					$myGroup = null;
					foreach ($groups as $group) {
						if ( $group['name'] == $groupname ) {
							$groupingid = $group['id'];
							$myGroup = $group;
						}
					}

					if ($groupingid !== 0) { // if exist the grouping

						// if subgroups not already exist, then create
						$subgroupsmc = $myGroup['groups'];
						$testGroups = explode(",", $subgroupname);
						foreach ($testGroups as $test) {
							if ( !in_array($test, $subgroupsmc) ) {
								$api->listInterestGroupAdd($myList["id"], $test);
							}
						}

						$merge_vars = array(
								'FNAME'=>$aff->name,
								'LNAME'=>"",
								'GROUPINGS'=>array(
										array('name'=>$groupname, "groups"=>$subgroupname),
								)
						);

						$retval = $api->listSubscribe( $myList["id"], $aff->email, $merge_vars, "html", $needconfirm );

						if ( $api->errorCode ) {
							error_log($api->errorMessage);
						}
					}

				}
			}
		}
	}


	public static function edit_user_profile_update ( $user_id ) {

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);
		$retval = $api->lists();
		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$groups = $api->listInterestGroupings($myList["id"]);

			if ($groups) {
				$groupingid = 0;
				$myGroup = null;
				foreach ($groups as $group) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
						$myGroup = $group;
					}
				}

				if ($groupingid !== 0) {

					$user_info = get_userdata( $user_id );

					// if subgroups not already exist, then create
					$subgroupsmc = $myGroup['groups'];
					$testGroups = explode(",", $subgroupname);
					foreach ($testGroups as $test) {
						if ( !in_array($test, $subgroupsmc) ) {
							$api->listInterestGroupAdd($myList["id"], $test);
						}
					}

					$merge_vars = array(
							'FNAME'=>$user_info->user_firstname,
							'LNAME'=>$user_info->user_lastname,
							'GROUPINGS'=>array(
									array('name'=>$groupname, "groups"=>$subgroupname),
							)
					);

					$advice = $api->listUpdateMember($myList["id"], $user_info->user_email, $merge_vars);

					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}

				}
			}
		}

	}

	public static function affiliate_updated ( $aff_id ) {

		$aff = Affiliates_Affiliate::get_affiliate($aff_id);

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);

		$retval = $api->lists();

		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$groups = $api->listInterestGroupings($myList["id"]);

			if ($groups) {
				$groupingid = 0;
				$myGroup = null;
				foreach ($groups as $group) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
						$myGroup = $group;
					}
				}

				if ($groupingid !== 0) {

					// if subgroups not already exist, then create
					$subgroupsmc = $myGroup['groups'];
					$testGroups = explode(",", $subgroupname);
					foreach ($testGroups as $test) {
						if ( !in_array($test, $subgroupsmc) ) {
							$api->listInterestGroupAdd($myList["id"], $test);
						}
					}

					$merge_vars = array(
							'FNAME'=>$aff->name,
							'LNAME'=>"",
							'GROUPINGS'=>array(
									array('name'=>$groupname, "groups"=>$subgroupname),
							)
					);

					$advice = $api->listUpdateMember($myList["id"], $aff->email, $merge_vars);

					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}

				}
			}
		}

	}

	public static function delete_user ( $user_id ) {


		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);

		$retval = $api->lists();

		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$groups = $api->listInterestGroupings($myList["id"]);

			if ($groups) {
				$groupingid = 0;
				foreach ($groups as $group) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
					}
				}

				if ($groupingid !== 0) {

					$user_info = get_userdata( $user_id );

					$retval = $api->listUnsubscribe( $myList["id"], $user_info->user_email );

					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}

				}
			}
		}

	}

	public static function affiliate_deleted ( $aff_id ) {

		$aff = Affiliates_Affiliate::get_affiliate($aff_id);

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);

		$retval = $api->lists();

		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$groups = $api->listInterestGroupings($myList["id"]);

			if ($groups) {
				$groupingid = 0;
				foreach ($groups as $group) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
					}
				}

				if ($groupingid !== 0) {

					$retval = $api->listUnsubscribe( $myList["id"], $aff->email );

					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}

				}
			}
		}

	}

	public static function synchronize() {

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);

		$retval = $api->lists();

		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$groups = $api->listInterestGroupings($myList["id"]);

			if ($groups) {
				$groupingid = 0;
				$myGroup = null;
				foreach ($groups as $group) {
					if ( $group['name'] == $groupname ) {
						$groupingid = $group['id'];
						$myGroup = $group;
					}
				}

				if ($groupingid !== 0) {

					$affiliates = affiliates_get_affiliates();

					foreach ($affiliates as $aff) {

						// if subgroups not already exist, then create
						$subgroupsmc = $myGroup['groups'];
						$testGroups = explode(",", $subgroupname);
						foreach ($testGroups as $test) {
							if ( !in_array($test, $subgroupsmc) ) {
								$api->listInterestGroupAdd($myList["id"], $test);
							}
						}

						$merge_vars = array(
								'FNAME'=>$aff['name'],
								'LNAME'=>"",
								'EMAIL'=>$aff['email'],
								'GROUPINGS'=>array(
										array('name'=>$groupname, "groups"=>$subgroupname),
								)
						);

						$users_data[] = $merge_vars;

					}
					$optin = $needconfirm; //yes, send optin emails
					$up_exist = true; // yes, update currently subscribed users
					$replace_int = true; // no, add interest, don't replace

					$api->listBatchSubscribe($myList['id'],$users_data,$optin, $up_exist, $replace_int);


					if ( $api->errorCode ) {
						error_log($api->errorMessage);
					}
				}
			}
		}
	}

	public static function toAffiliates() {

		$apikey = get_option('affiliates_mailchimp-api_key');
		$listname = get_option('affiliates_mailchimp-list');
		$groupname = get_option('affiliates_mailchimp-group');
		$subgroupname = get_option('affiliates_mailchimp-subgroup');
		$needconfirm = get_option('affiliates_mailchimp-needconfirm');

		$api = new MCAPI($apikey);

		$retval = $api->lists();

		$lists = $retval["data"];

		$myList = null;

		if ( count ( $lists ) > 0 ) {
			foreach ($lists as $list) {
				if ($list["name"] == $listname)
					$myList = $list;
			}
		}

		if ($myList !== null) {
			$members = $api->listMembers($myList["id"]);

			if ($members) {
				foreach ($members['data'] as $member) { 
					$datavalues['email'] = $member['email'];
					self::create_an_affiliate($datavalues);
				}
			}
		}
	}


	public function create_an_affiliate ($datavalues) {
		global $wpdb;
		$result = true;

		if ( !current_user_can( AFFILIATES_ADMINISTER_AFFILIATES ) ) {
			wp_die( __( 'Access denied.', AFFILIATES_PLUGIN_DOMAIN ) );
		}

		$affiliates_table = _affiliates_get_tablename( 'affiliates' );
		$affiliates_users_table = _affiliates_get_tablename( 'affiliates_users' );

		if (isset($datavalues['email'])) {
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
				foreach( $data as $key => $value ) { // (*)
					if ( $value ) {
						$data_[$key] = $value;
					}
				}
				foreach( $formats as $format ) { // (*)
					if ( $format != "NULL" ) {
						$formats_[] = $format;
					}
				}
				if ( $wpdb->insert( $affiliates_table, $data_, $formats_ ) ) {
					$affiliate_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
				}

				// hook
				if ( !empty( $affiliate_id ) ) {
					do_action( 'affiliates_added_affiliate', intval( $affiliate_id ) );
				}
			} else {
				$result = false;
			}
			return $result;
		}
	}
}
Affiliates_MailChimp::init();
