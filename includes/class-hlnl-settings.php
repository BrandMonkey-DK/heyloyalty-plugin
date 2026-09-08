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

	public static function get_options() {
		$defaults = array(
			'api_key'         => '',
			'api_secret'      => '',
			'default_list_id' => '',
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

		return array(
			'api_key'         => sanitize_text_field( $input['api_key'] ?? '' ),
			'api_secret'      => sanitize_text_field( $input['api_secret'] ?? '' ),
			'default_list_id' => sanitize_text_field( $input['default_list_id'] ?? '' ),
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
							<input type="text" class="regular-text" id="hlnl_list_id"
								name="<?php echo esc_attr( self::OPTION ); ?>[default_list_id]"
								value="<?php echo esc_attr( $options['default_list_id'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used when the shortcode has no list-id attribute.', 'heyloyalty-newsletter' ); ?></p>
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
