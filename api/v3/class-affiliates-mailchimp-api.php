<?php
/**
 * class-mailchimp-api.php
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
 * Mailchimp_api class
 */
class Affiliates_Mailchimp_Api {

	/**
	 * Mailchimp API key
	 *
	 * @var string
	 */
	public $apikey;

	/**
	 * Mailchimp API root URL
	 *
	 * @var string
	 */
	public $root  = 'https://api.mailchimp.com/3.0';

	/**
	 * Debug
	 *
	 * @var string
	 */
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
	 */
	public function __construct( $apikey = null, $opts = array() ) {
		// @todo this will be removed 
		if ( !function_exists( 'curl_init' ) || !function_exists( 'curl_setopt' ) ) {
			exit;
		}
		if ( !function_exists( 'wp_remote_get' ) ) {
			require_once 'wp-includes/http.php';
		}

		$this->apikey = $apikey;
		$dc           = 'us1';

		if ( strstr( $this->apikey, '-' ) ) {
			list( $key, $dc ) = explode( '-', $this->apikey, 2 );
			if ( !$dc ) {
				$dc = 'us1';
			}
		}

		$this->root = str_replace( 'https://api', 'https://' . $dc . '.api', $this->root );
		$this->root = rtrim( $this->root, '/' );

		if ( !isset( $opts['timeout'] ) || !is_int( $opts['timeout'] ) ) {
			$opts['timeout'] = 600;
		}
		if ( isset( $opts['debug'] ) ) {
			$this->debug = true;
		}
	}

	/**
	 * Sets the Request URL based on the request
	 *
	 * @param string $type
	 * @param array $params request parameters
	 * @param array $list_params
	 * @return boolean
	 */
	private function request_url( $type, $params = array(), $list_params = array() ) {
		$result = self::LISTS;
		switch ( $type ) {
			case 'get' :
				$result .= '?' . http_build_query( $params );
				break;
			case 'update' :
			case 'check' :
				$result .= '/' . $list_params['list_id'] . self::MEMBERS . '/' . md5( $params['email_address'] );
				break;
			case 'new' :
				$result .= '/' . $list_params['list_id'] . self::MEMBERS;
				break;
			case 'interest_categories' :
				$result .= '/' . $list_params['list_id'] . self::INTEREST_CATEGORIES;
				break;
			case 'interests' :
				$result .= '/' . $list_params['list_id'] . self::INTEREST_CATEGORIES . '/' . $list_params['interest_category_id'] . self::INTERESTS;
				break;
			default :
				$result = null;
		}
		return $result;
	}

	/**
	 * Get all the lists
	 *
	 * @param array $list_parameters for the URL
	 * @param array $parameters for the request
	 * @return boolean|mixed
	 */
	public function get_lists( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		$lists_url = $this->request_url( 'get', $parameters );

		if ( isset( $list_parameters['list_id'] ) ) {
			if ( isset( $list_parameters['interest_category_id'] ) ) {
				$lists_url = $this->request_url( 'interests', $parameters, $list_parameters );
			} else {
				$lists_url = $this->request_url( 'interest_categories', $parameters, $list_parameters );
			}
		}

		if ( $lists_url ) {
			$result = $this->make_request( 'lists', $parameters, $lists_url );
		}
		return $result;
	}

	/**
	 * Check list for existing subscriber
	 *
	 * @param array $list_parameters
	 * @param array $parameters
	 * @return NULL|boolean|mixed
	 */
	public function check_list( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		if ( isset( $list_parameters['list_id'] ) && isset( $parameters['email_address'] ) ) {
			$members_url = $this->request_url( 'check', $parameters, $list_parameters );
			if ( $members_url ) {
				$result = $this->make_request( 'check', $parameters, $members_url );
			}
		}
		return $result;
	}

	/**
	 * Add a new subscriber
	 *
	 * @param array $list_parameters
	 * @param array $parameters
	 * @return NULL|boolean|mixed
	 */
	public function new_subscriber( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		if ( isset( $list_parameters['list_id'] ) && isset( $parameters['email_address'] ) ) {
			$new_member_url = $this->request_url( 'new', $parameters, $list_parameters );
			if ( $new_member_url ) {
				$result = $this->make_request( 'add', $parameters, $new_member_url );
			}
		}
		return $result;
	}

	/**
	 * Update an existing email
	 *
	 * @param array $list_parameters
	 * @param array $parameters
	 * @return NULL|boolean|mixed
	 */
	public function update_subscriber( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		if ( isset( $list_parameters['list_id'] ) && isset( $parameters['email_address'] ) ) {
			$update_member_url = $this->request_url( 'update', $parameters, $list_parameters );
			if ( $update_member_url ) {
				$result = $this->make_request( 'update', $parameters, $update_member_url );
			}
		}
		return $result;
	}

	/**
	 * Add a new Interest Category
	 *
	 * @param array $list_parameters
	 * @param array $parameters
	 * @return NULL|boolean|mixed
	 */
	public function add_interest_category( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		if ( isset( $list_parameters['list_id'] ) ) {
			if ( isset( $parameters['title'] ) && isset( $parameters['type'] ) ) {
				$interest_category_url = $this->request_url( 'interest_categories', $parameters, $list_parameters );
				if ( $interest_category_url ) {
					$result = $this->make_request( 'add', $parameters, $interest_category_url );
				}
			}
		}
		return $result;
	}

	/**
	 * Add a new Interest
	 *
	 * @param array $list_parameters
	 * @param array $parameters
	 * @return NULL|boolean|mixed
	 */
	public function add_interest( $list_parameters = array(), $parameters = array() ) {
		$result = null;
		if ( isset( $list_parameters['list_id'] ) ) {
			if ( isset( $list_parameters['interest_category_id'] ) && isset( $parameters['name'] ) ) {
				$interest_url = $this->request_url( 'interests', $parameters, $list_parameters );
				if ( $interest_url ) {
					$result = $this->make_request( 'add', $parameters, $interest_url );
				}
			}
		}
		return $result;
	}

	/**
	 * Make a request to Mailchimp API
	 *
	 * @param string $request
	 * @param array $parameters
	 * @param string $api_path
	 * @return boolean|mixed
	 */
	private function make_request( $request, $parameters = array(), $api_path = null ) {
		global $wp_version; //@todo check if this is really needed
		$result = null;
		$response = null;
		
		$args = array(
			'timeout'     => 10,
			'redirection' => 5,
			'httpversion' => '1.1',
			//'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $this->apikey )
			), // @todo remove array(),
			'cookies'     => array(),
			'body'        => null,
			'method'      => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true, // @todo remove true,
			'stream'      => false,
			'filename'    => null
		); 
		$url = $this->root . $api_path;
		//$headers = array(
		//	'Content-Type: application/json',
		//	'Authorization: Basic ' . base64_encode( 'user:' . $this->apikey )
		//);

		switch ( $request ) {
			case 'lists' :
			case 'check' :
				$response = wp_remote_get( $url, $args );
				//self::write_log( 'lists check' );
				
				//$ch = curl_init();
				//curl_setopt( $ch, CURLOPT_URL, $url );
				//curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
				break;
			case 'add' :
				//$args['method'] = 'PUT';
				self::write_log( $url );
				$args['body'] = json_encode( $parameters );self::write_log( $parameters);
				$response = wp_remote_request( $url, $args );
				self::write_log( $args );
				//$ch = curl_init( $url );
				//curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->apikey );
				//curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
				//curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
				break;
			//case 'update' :
			//	$ch = curl_init( $url );
			//	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PATCH' );
			//	curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
			//	break;
		}

		

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			self::write_log( 'Something went wrong with the request: ' );
			self::write_log( wp_remote_retrieve_response_code( $response ) );
		} else {
			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			self::write_log( $result );
		}
		
		//curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		//curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		
		//$result     = curl_exec( $ch );
		//$httpd_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		//curl_close( $ch );
		//if ( $httpd_code == '200' ) {
		//	$result = json_decode( $result, true );
		//}

		return $result;
	}

	/**
	 * Helper for debugging
	 * writes to debug.log
	 * 
	 * @param mixed $log
	 */
	private static function write_log ( $log )  {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}
