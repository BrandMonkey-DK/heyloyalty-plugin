<?php
/**
 * Settings screen for API credentials and defaults.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLNL_Settings {

	const OPTION = 'hlnl_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HLNL_FILE ), array( __CLASS__, 'plugin_action_links' ) );
	}

	public static function plugin_action_links( $links ) {
		$settings_url = admin_url( 'options-general.php?page=hlnl-settings' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Indstillinger', 'heyloyalty-newsletter' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * @return array List of lists (each with "id" and "name") or empty array on failure.
	 */
	public static function get_lists() {
		$cached = get_transient( 'hlnl_lists' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$api = HLNL_API::from_settings();
		if ( ! $api->has_credentials() ) {
			return array();
		}

		$response = $api->get_lists();
		$lists    = is_wp_error( $response ) ? array() : $response;

		set_transient( 'hlnl_lists', $lists, 5 * MINUTE_IN_SECONDS );

		return $lists;
	}

	public static function get_options() {
		$defaults = array(
			'api_key'         => '',
			'api_secret'      => '',
			'default_list_id' => '',
			'custom_css'      => '',
		);

		$options = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), $defaults );
	}

	public static function add_menu() {
		add_options_page(
			__( 'HeyLoyalty Newsletter', 'heyloyalty-newsletter' ),
			__( 'HeyLoyalty', 'heyloyalty-newsletter' ),
			'manage_options',
			'hlnl-settings',
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			'hlnl_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		delete_transient( 'hlnl_lists' );

		return array(
			'api_key'         => sanitize_text_field( $input['api_key'] ?? '' ),
			'api_secret'      => sanitize_text_field( $input['api_secret'] ?? '' ),
			'default_list_id' => sanitize_text_field( $input['default_list_id'] ?? '' ),
			'custom_css'      => wp_strip_all_tags( $input['custom_css'] ?? '' ),
		);
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = self::get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HeyLoyalty Newsletter', 'heyloyalty-newsletter' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'hlnl_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hlnl_api_key"><?php esc_html_e( 'API key', 'heyloyalty-newsletter' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="hlnl_api_key"
								name="<?php echo esc_attr( self::OPTION ); ?>[api_key]"
								value="<?php echo esc_attr( $options['api_key'] ); ?>" autocomplete="off">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hlnl_api_secret"><?php esc_html_e( 'API secret', 'heyloyalty-newsletter' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" id="hlnl_api_secret"
								name="<?php echo esc_attr( self::OPTION ); ?>[api_secret]"
								value="<?php echo esc_attr( $options['api_secret'] ); ?>" autocomplete="new-password">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hlnl_list_id"><?php esc_html_e( 'Default list ID', 'heyloyalty-newsletter' ); ?></label></th>
						<td>
							<?php $lists = self::get_lists(); ?>
							<?php if ( ! empty( $lists ) ) : ?>
								<select class="regular-text" id="hlnl_list_id" name="<?php echo esc_attr( self::OPTION ); ?>[default_list_id]">
									<option value=""><?php esc_html_e( '— None —', 'heyloyalty-newsletter' ); ?></option>
									<?php foreach ( $lists as $list ) : ?>
										<?php $list_id = isset( $list['id'] ) ? (string) $list['id'] : ''; ?>
										<?php if ( '' === $list_id ) : continue; endif; ?>
										<option value="<?php echo esc_attr( $list_id ); ?>" <?php selected( $options['default_list_id'], $list_id ); ?>>
											<?php echo esc_html( ( $list['name'] ?? '' ) . ' (#' . $list_id . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input type="text" class="regular-text" id="hlnl_list_id"
									name="<?php echo esc_attr( self::OPTION ); ?>[default_list_id]"
									value="<?php echo esc_attr( $options['default_list_id'] ); ?>">
								<p class="description"><?php esc_html_e( 'Enter your API key and secret and save to load the list of available lists.', 'heyloyalty-newsletter' ); ?></p>
							<?php endif; ?>
							<p class="description"><?php esc_html_e( 'Used when the shortcode has no list-id attribute.', 'heyloyalty-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hlnl_custom_css"><?php esc_html_e( 'Custom CSS', 'heyloyalty-newsletter' ); ?></label></th>
						<td>
							<textarea class="large-text code" id="hlnl_custom_css" rows="10"
								name="<?php echo esc_attr( self::OPTION ); ?>[custom_css]"><?php echo esc_textarea( $options['custom_css'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Extra CSS applied to the newsletter form on the frontend.', 'heyloyalty-newsletter' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<h2><?php esc_html_e( 'Shortcode', 'heyloyalty-newsletter' ); ?></h2>
			<p><code>[heyloyalty_form list-id="12345"]</code></p>
			<p class="description">
				<?php esc_html_e( 'Optional attributes: title, button, success, opt-in="yes|no", skip-opt-in="yes|no".', 'heyloyalty-newsletter' ); ?>
			</p>
		</div>
		<?php
	}
}
