<?php
/**
 * class-mailchimp-api.php
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
 * Mailchimp_api class
 */
class Mailchimp_Api {

	public $apikey;
	public $ch;
	public $root  = 'https://api.mailchimp.com/3.0';
	public $debug = false;

	const LISTS = '/lists';
	const MEMBERS = '/members';
	const INTEREST_CATEGORIES = '/interest-categories';
	const INTERESTS = '/interests';

	/**
	 * Class constructor
	 *
	 * @param string $apikey
	 * @param array $opts
	 * @throws \Exception
	 * @throws Mailchimp_Error
	 */
	public function __construct( $apikey = null, $opts = array() ) {
		if ( !function_exists( 'curl_init' ) || !function_exists( 'curl_setopt' ) ) {
			throw new \Exception( "cURL support is required, but can't be found.");
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

	/**
	 * Get all the lists
	 *
	 * @param array $parameters
	 * @return boolean|mixed
	 */
    public function get_lists( $parameters = array() ) {
    	$lists_url = self::LISTS . '?' . http_build_query( $parameters );
    	return $this->make_request( 'lists', $parameters, $lists_url );
    }

    /**
     * Check list for a given email
     *
     * @param string $list_id
     * @param array $parameters
     * @return NULL|boolean|mixed
     */
    public function check_list( $list_id = null, $parameters = array() ) {
    	$result = null;
    	if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
    		$members_url = self::LISTS . '/' . $list_id . self::MEMBERS . '/' . md5( $parameters['email_address'] );
    		$result = $this->make_request( 'check', $parameters, $members_url );
    	}
    	return $result;
    }

    /**
     * Add a new subscriber
     *
     * @param string $list_id
     * @param array $parameters
     * @return NULL|boolean
     */
    public function new_subscriber( $list_id = null, $parameters = array() ) {
    	$result = null;
    	if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
    		$new_member_url = self::LISTS . '/' . $list_id . self::MEMBERS;
    		$result = $this>make_request( 'add', $parameters, $new_member_url );
    	}
    	return $result;
    }

    /**
     * Update an existing email - subscribe or unsubscribe
     *
     * @param string $list_id
     * @param array $parameters
     * @return NULL|boolean
     */
    public function update_subscriber( $list_id = null, $parameters = array() ) {
    	$result = null;
    	if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
    		$update_member_url = self::LISTS . '/' . $list_id . self::MEMBERS . md5( $parameters['email_address'] );
    		$result = $this>make_request( 'update', $parameters, $update_member_url );
    	}
    	return $result;
    }

    // /lists/{list_id}/interest-categories/{interest_category_id}/interests
    // URL parameters example
    /* $url_parameters = array(
     	'list_id'              => $list_id,
     	'interest_category_id' => $interest_category_id,
     	'interest_id'          => $interest_id
     */

    /**
     * Make a request to Mailchimp API
     *
     * @param string $request
     * @param array $parameters
     * @param string $api_path
     * @return boolean|mixed
     */
	private function make_request( $request, $parameters = array(), $api_path = null ) {
		$result = '';
		$url = $this->root;
		$headers = array(
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode( 'user:'. $this->apikey )
		);

		switch( $request ) {
			case 'lists' :
			case 'check' :
				$url .= $api_path;
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
				break;			
			case 'add':
				////if ( isset( $list_id ) ) {
					$url .= $api_path;
					$ch = curl_init( $url );
					curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->apikey );
					//curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
					curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
					break;
				//} else {
				//	break;
				//}
			case 'update':
				//if ( isset( $list_id ) && isset( $parameters['email_address'] ) ) {
					$url .= $api_path;
					$ch = curl_init( $url );
					curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PATCH' );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
				//}
				break;
		}

		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		$result     = curl_exec( $ch );
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
