<?php
/**
 * Thin HeyLoyalty REST client.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLNL_API {

	const BASE_URL = 'https://api.heyloyalty.com';

	/** @var string */
	private $api_key;

	/** @var string */
	private $api_secret;

	public function __construct( $api_key, $api_secret ) {
		$this->api_key    = (string) $api_key;
		$this->api_secret = (string) $api_secret;
	}

	public static function from_settings() {
		$options = HLNL_Settings::get_options();

		return new self( $options['api_key'], $options['api_secret'] );
	}

	public function has_credentials() {
		return '' !== $this->api_key && '' !== $this->api_secret;
	}

	/**
	 * Create a member on a list.
	 *
	 * @param string|int $list_id
	 * @param array      $member
	 *
	 * @return array|WP_Error Decoded response body.
	 */
	public function create_member( $list_id, array $member ) {
		return $this->request(
			'POST',
			sprintf( '/loyalty/v1/lists/%s/members', rawurlencode( (string) $list_id ) ),
			$member
		);
	}

	/**
	 * @return array|WP_Error
	 */
	private function request( $method, $path, array $body = null ) {
		if ( ! $this->has_credentials() ) {
			return new WP_Error( 'hlnl_missing_credentials', __( 'API credentials are not configured.', 'heyloyalty-newsletter' ) );
		}

		// RFC 1123 date in GMT, e.g. "Mon, 08 Sep 2026 10:11:12 GMT".
		$timestamp = gmdate( 'D, d M Y H:i:s' ) . ' GMT';
		$signature = base64_encode( hash_hmac( 'sha256', $timestamp, $this->api_secret ) );

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization'       => 'Basic ' . base64_encode( $this->api_key . ':' . $signature ),
				'X-Request-Timestamp' => $timestamp,
				'Accept'              => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE_URL . $path, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = '';
			if ( is_array( $data ) ) {
				if ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
					$message = $data['message'];
				} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
					$message = $data['error'];
				}
			}

			return new WP_Error(
				'hlnl_api_error',
				'' !== $message ? $message : sprintf( __( 'HeyLoyalty returned HTTP %d.', 'heyloyalty-newsletter' ), $code ),
				array(
					'status' => $code,
					'body'   => $data,
				)
			);
		}

		return is_array( $data ) ? $data : array();
	}
}
