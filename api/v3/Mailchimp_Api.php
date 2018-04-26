<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/*require_once 'Mailchimp/Folders.php';
require_once 'Mailchimp/Templates.php';
require_once 'Mailchimp/Users.php';
require_once 'Mailchimp/Helper.php';
require_once 'Mailchimp/Mobile.php';
require_once 'Mailchimp/Conversations.php';
require_once 'Mailchimp/Ecomm.php';
require_once 'Mailchimp/Neapolitan.php';
require_once 'Mailchimp/Lists.php';
require_once 'Mailchimp/Campaigns.php';
require_once 'Mailchimp/Vip.php';
require_once 'Mailchimp/Reports.php';
require_once 'Mailchimp/Gallery.php';
require_once 'Mailchimp/Goal.php';
require_once 'Mailchimp/Exceptions.php';*/

class Mailchimp_Api {
    
    public $apikey;
    public $ch;
    public $root  = 'https://api.mailchimp.com/3.0';
    public $debug = false;

    /*public static $error_map = array(
        "ValidationError" => "Mailchimp_ValidationError",
        "ServerError_MethodUnknown" => "Mailchimp_ServerError_MethodUnknown",
        "ServerError_InvalidParameters" => "Mailchimp_ServerError_InvalidParameters",
        "Unknown_Exception" => "Mailchimp_Unknown_Exception",
        "Request_TimedOut" => "Mailchimp_Request_TimedOut",
        "Zend_Uri_Exception" => "Mailchimp_Zend_Uri_Exception",
        "PDOException" => "Mailchimp_PDOException",
        "Avesta_Db_Exception" => "Mailchimp_Avesta_Db_Exception",
        "XML_RPC2_Exception" => "Mailchimp_XML_RPC2_Exception",
        "XML_RPC2_FaultException" => "Mailchimp_XML_RPC2_FaultException",
        "Too_Many_Connections" => "Mailchimp_Too_Many_Connections",
        "Parse_Exception" => "Mailchimp_Parse_Exception",
        "User_Unknown" => "Mailchimp_User_Unknown",
        "User_Disabled" => "Mailchimp_User_Disabled",
        "User_DoesNotExist" => "Mailchimp_User_DoesNotExist",
        "User_NotApproved" => "Mailchimp_User_NotApproved",
        "Invalid_ApiKey" => "Mailchimp_Invalid_ApiKey",
        "User_UnderMaintenance" => "Mailchimp_User_UnderMaintenance",
        "Invalid_AppKey" => "Mailchimp_Invalid_AppKey",
        "Invalid_IP" => "Mailchimp_Invalid_IP",
        "User_DoesExist" => "Mailchimp_User_DoesExist",
        "User_InvalidRole" => "Mailchimp_User_InvalidRole",
        "User_InvalidAction" => "Mailchimp_User_InvalidAction",
        "User_MissingEmail" => "Mailchimp_User_MissingEmail",
        "User_CannotSendCampaign" => "Mailchimp_User_CannotSendCampaign",
        "User_MissingModuleOutbox" => "Mailchimp_User_MissingModuleOutbox",
        "User_ModuleAlreadyPurchased" => "Mailchimp_User_ModuleAlreadyPurchased",
        "User_ModuleNotPurchased" => "Mailchimp_User_ModuleNotPurchased",
        "User_NotEnoughCredit" => "Mailchimp_User_NotEnoughCredit",
        "MC_InvalidPayment" => "Mailchimp_MC_InvalidPayment",
        "List_DoesNotExist" => "Mailchimp_List_DoesNotExist",
        "List_InvalidInterestFieldType" => "Mailchimp_List_InvalidInterestFieldType",
        "List_InvalidOption" => "Mailchimp_List_InvalidOption",
        "List_InvalidUnsubMember" => "Mailchimp_List_InvalidUnsubMember",
        "List_InvalidBounceMember" => "Mailchimp_List_InvalidBounceMember",
        "List_AlreadySubscribed" => "Mailchimp_List_AlreadySubscribed",
        "List_NotSubscribed" => "Mailchimp_List_NotSubscribed",
        "List_InvalidImport" => "Mailchimp_List_InvalidImport",
        "MC_PastedList_Duplicate" => "Mailchimp_MC_PastedList_Duplicate",
        "MC_PastedList_InvalidImport" => "Mailchimp_MC_PastedList_InvalidImport",
        "Email_AlreadySubscribed" => "Mailchimp_Email_AlreadySubscribed",
        "Email_AlreadyUnsubscribed" => "Mailchimp_Email_AlreadyUnsubscribed",
        "Email_NotExists" => "Mailchimp_Email_NotExists",
        "Email_NotSubscribed" => "Mailchimp_Email_NotSubscribed",
        "List_MergeFieldRequired" => "Mailchimp_List_MergeFieldRequired",
        "List_CannotRemoveEmailMerge" => "Mailchimp_List_CannotRemoveEmailMerge",
        "List_Merge_InvalidMergeID" => "Mailchimp_List_Merge_InvalidMergeID",
        "List_TooManyMergeFields" => "Mailchimp_List_TooManyMergeFields",
        "List_InvalidMergeField" => "Mailchimp_List_InvalidMergeField",
        "List_InvalidInterestGroup" => "Mailchimp_List_InvalidInterestGroup",
        "List_TooManyInterestGroups" => "Mailchimp_List_TooManyInterestGroups",
        "Campaign_DoesNotExist" => "Mailchimp_Campaign_DoesNotExist",
        "Campaign_StatsNotAvailable" => "Mailchimp_Campaign_StatsNotAvailable",
        "Campaign_InvalidAbsplit" => "Mailchimp_Campaign_InvalidAbsplit",
        "Campaign_InvalidContent" => "Mailchimp_Campaign_InvalidContent",
        "Campaign_InvalidOption" => "Mailchimp_Campaign_InvalidOption",
        "Campaign_InvalidStatus" => "Mailchimp_Campaign_InvalidStatus",
        "Campaign_NotSaved" => "Mailchimp_Campaign_NotSaved",
        "Campaign_InvalidSegment" => "Mailchimp_Campaign_InvalidSegment",
        "Campaign_InvalidRss" => "Mailchimp_Campaign_InvalidRss",
        "Campaign_InvalidAuto" => "Mailchimp_Campaign_InvalidAuto",
        "MC_ContentImport_InvalidArchive" => "Mailchimp_MC_ContentImport_InvalidArchive",
        "Campaign_BounceMissing" => "Mailchimp_Campaign_BounceMissing",
        "Campaign_InvalidTemplate" => "Mailchimp_Campaign_InvalidTemplate",
        "Invalid_EcommOrder" => "Mailchimp_Invalid_EcommOrder",
        "Absplit_UnknownError" => "Mailchimp_Absplit_UnknownError",
        "Absplit_UnknownSplitTest" => "Mailchimp_Absplit_UnknownSplitTest",
        "Absplit_UnknownTestType" => "Mailchimp_Absplit_UnknownTestType",
        "Absplit_UnknownWaitUnit" => "Mailchimp_Absplit_UnknownWaitUnit",
        "Absplit_UnknownWinnerType" => "Mailchimp_Absplit_UnknownWinnerType",
        "Absplit_WinnerNotSelected" => "Mailchimp_Absplit_WinnerNotSelected",
        "Invalid_Analytics" => "Mailchimp_Invalid_Analytics",
        "Invalid_DateTime" => "Mailchimp_Invalid_DateTime",
        "Invalid_Email" => "Mailchimp_Invalid_Email",
        "Invalid_SendType" => "Mailchimp_Invalid_SendType",
        "Invalid_Template" => "Mailchimp_Invalid_Template",
        "Invalid_TrackingOptions" => "Mailchimp_Invalid_TrackingOptions",
        "Invalid_Options" => "Mailchimp_Invalid_Options",
        "Invalid_Folder" => "Mailchimp_Invalid_Folder",
        "Invalid_URL" => "Mailchimp_Invalid_URL",
        "Module_Unknown" => "Mailchimp_Module_Unknown",
        "MonthlyPlan_Unknown" => "Mailchimp_MonthlyPlan_Unknown",
        "Order_TypeUnknown" => "Mailchimp_Order_TypeUnknown",
        "Invalid_PagingLimit" => "Mailchimp_Invalid_PagingLimit",
        "Invalid_PagingStart" => "Mailchimp_Invalid_PagingStart",
        "Max_Size_Reached" => "Mailchimp_Max_Size_Reached",
        "MC_SearchException" => "Mailchimp_MC_SearchException",
        "Goal_SaveFailed" => "Mailchimp_Goal_SaveFailed",
        "Conversation_DoesNotExist" => "Mailchimp_Conversation_DoesNotExist",
        "Conversation_ReplySaveFailed" => "Mailchimp_Conversation_ReplySaveFailed",
        "File_Not_Found_Exception" => "Mailchimp_File_Not_Found_Exception",
        "Folder_Not_Found_Exception" => "Mailchimp_Folder_Not_Found_Exception",
        "Folder_Exists_Exception" => "Mailchimp_Folder_Exists_Exception"
    );*/

    public function __construct( $apikey=null, $opts=array() ) {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }
    	if ( !$apikey ) {
            $apikey = getenv('MAILCHIMP_APIKEY');
        }

        if (!$apikey) {
            $apikey = $this->readConfigs();
        }

        if (!$apikey) {
            throw new Mailchimp_Error( 'You must provide a MailChimp API key' );
        }

        $this->apikey = $apikey;
        $dc           = "us1";

        if ( strstr( $this->apikey, "-" ) ) {
            list( $key, $dc ) = explode( "-", $this->apikey, 2 );
            if ( !$dc ) {
                $dc = "us1";
            }
        }

        $this->root = str_replace( 'https://api', 'https://' . $dc . '.api', $this->root );
        $this->root = rtrim( $this->root, '/' ) . '/';

        if ( !isset($opts['timeout'] ) || !is_int( $opts['timeout'] ) ) {
            $opts['timeout'] = 600;
        }
        if ( isset($opts['debug'] ) ) {
            $this->debug = true;
        }
    }

    public function __destruct() {
        if ( is_resource( $this->ch ) ) {
            curl_close( $this->ch );
        }
    }	

	public function make_request( $http_request, $parameters = array(), $list_id = null ) {
		$result = '';
		$url = $this->root . '/lists';
		$headers = array(
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode( 'user:'. $this->apikey )
		);

		switch( $http_request ) {
			case 'GET':
				$ch = curl_init();
				if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
					$url .= '/' . $list_id . '/members/' . md5( $parameters['email_address'] );
				} else {
					$url .= '?' . http_build_query( $parameters );
				}
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
				break;
			case 'POST':
				if ( isset( $list_id ) ) {
					$url .= '/' . $list_id . '/members';
					$ch = curl_init( $url );
					curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->apikey );
					//curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
					curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
					break;
				} else {
					break;
				}
			case 'PATCH':
				if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
					$url .= '/' . $list_id . '/members/' . md5( $parameters['email_address'] );
					$ch = curl_init( $url );
					curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PATCH' );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
				}
				break;
		}

		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		$result = curl_exec( $ch );
		$httpd_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	    curl_close( $ch );
	    if ( $httpd_code != '200' ) {
	    	$result = false;
	    } else {
	    	$result = json_decode( $result, true );
	    }

	    return $result;
	}

	public function readConfigs() {
		$paths = array( '~/.mailchimp.key', '/etc/mailchimp.key' );
		foreach( $paths as $path ) {
			if( file_exists( $path ) ) {
				$apikey = trim( file_get_contents( $path ) );
				if ( $apikey ) {
					return $apikey;
				}
			}
		}
		return false;
	}

	public function castError( $result ) {
		if ( $result['status'] !== 'error' || !$result['name'] ) {
			throw new Mailchimp_Error( 'We received an unexpected error: ' . json_encode( $result ) );
		}

		$class = ( isset( self::$error_map[$result['name']] ) ) ? self::$error_map[$result['name']] : 'Mailchimp_Error';
		return new $class( $result['error'], $result['code'] );
	}

	public function log( $msg ) {
		if ( $this->debug ) {
			error_log( $msg );
		}
	}
}
