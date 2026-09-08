<?php
/**
 * REST endpoint used by the front-end form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLNL_Rest {

	const REST_NAMESPACE = 'heyloyalty/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/subscribe',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'subscribe' ),
				'args'                => array(
					'list_id'   => array( 'required' => true, 'type' => 'string' ),
					'firstname' => array( 'required' => true, 'type' => 'string' ),
					'lastname'  => array( 'required' => true, 'type' => 'string' ),
					'email'     => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);
	}

	public static function subscribe( WP_REST_Request $request ) {
		// Honeypot: silently accept without calling the API.
		if ( '' !== trim( (string) $request->get_param( 'hlnl_hp' ) ) ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		$list_id   = sanitize_text_field( (string) $request->get_param( 'list_id' ) );
		$firstname = sanitize_text_field( (string) $request->get_param( 'firstname' ) );
		$lastname  = sanitize_text_field( (string) $request->get_param( 'lastname' ) );
		$email     = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( ! in_array( $list_id, HLNL_Shortcode::get_known_list_ids(), true ) ) {
			return new WP_Error( 'hlnl_invalid_list', __( 'Unknown list.', 'heyloyalty-newsletter' ), array( 'status' => 400 ) );
		}

		if ( '' === $firstname || '' === $lastname ) {
			return new WP_Error( 'hlnl_invalid_name', __( 'Please enter your first and last name.', 'heyloyalty-newsletter' ), array( 'status' => 400 ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'hlnl_invalid_email', __( 'Please enter a valid email address.', 'heyloyalty-newsletter' ), array( 'status' => 400 ) );
		}

		if ( self::is_rate_limited( $email ) ) {
			return new WP_Error( 'hlnl_rate_limited', __( 'Too many attempts. Please try again later.', 'heyloyalty-newsletter' ), array( 'status' => 429 ) );
		}

		$member = array(
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'email'     => $email,
			'opt_in'    => (bool) $request->get_param( 'opt_in' ),
		);

		if ( $request->get_param( 'skip_opt_in' ) ) {
			$member['skipOptIn'] = 1;
		}

		/**
		 * Filter the member payload sent to HeyLoyalty.
		 */
		$member = apply_filters( 'hlnl_member_payload', $member, $list_id, $request );

		$result = HLNL_API::from_settings()->create_member( $list_id, $member );

		if ( is_wp_error( $result ) ) {
			$status = (int) ( $result->get_error_data()['status'] ?? 502 );

			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => ( $status >= 400 && $status < 500 ) ? $status : 502 )
			);
		}

		do_action( 'hlnl_member_created', $result, $list_id, $member );

		return new WP_REST_Response( array( 'success' => true ), 201 );
	}

	private static function is_rate_limited( $email ) {
		$key = 'hlnl_rl_' . md5( strtolower( $email ) . '|' . self::client_ip() );

		$hits = (int) get_transient( $key );
		if ( $hits >= 5 ) {
			return true;
		}

		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		return false;
	}

	private static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}
