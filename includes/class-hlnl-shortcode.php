<?php
/**
 * [heyloyalty_form] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLNL_Shortcode {

	const KNOWN_LISTS_OPTION = 'hlnl_known_lists';

	private static $instances = 0;

	public static function init() {
		add_shortcode( 'heyloyalty_form', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'hlnl-form', HLNL_URL . 'assets/css/form.css', array(), HLNL_VERSION );
		wp_register_script( 'hlnl-form', HLNL_URL . 'assets/js/form.js', array(), HLNL_VERSION, true );
		wp_localize_script(
			'hlnl-form',
			'HLNL_Config',
			array(
				'endpoint' => esc_url_raw( rest_url( 'heyloyalty/v1/subscribe' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'i18n'     => array(
					'error'   => __( 'Something went wrong. Please try again.', 'heyloyalty-newsletter' ),
					'sending' => __( 'Sending…', 'heyloyalty-newsletter' ),
				),
			)
		);
	}

	public static function render( $atts ) {
		$defaults = HLNL_Settings::get_options();

		$atts = shortcode_atts(
			array(
				'list-id'      => $defaults['default_list_id'],
				'title'        => '',
				'button'       => __( 'Subscribe', 'heyloyalty-newsletter' ),
				'success'      => __( 'Thanks! Please check your inbox to confirm your subscription.', 'heyloyalty-newsletter' ),
				'opt-in'       => 'yes',
				'skip-opt-in'  => 'no',
			),
			$atts,
			'heyloyalty_form'
		);

		$list_id = sanitize_text_field( $atts['list-id'] );

		if ( '' === $list_id ) {
			return current_user_can( 'manage_options' )
				? '<p>' . esc_html__( 'HeyLoyalty: missing list-id attribute.', 'heyloyalty-newsletter' ) . '</p>'
				: '';
		}

		self::remember_list_id( $list_id );

		wp_enqueue_style( 'hlnl-form' );
		wp_enqueue_script( 'hlnl-form' );

		$id       = 'hlnl-form-' . ++self::$instances;
		$opt_in   = self::is_true( $atts['opt-in'] );
		$skip     = self::is_true( $atts['skip-opt-in'] );

		ob_start();
		?>
		<form class="hlnl-form" id="<?php echo esc_attr( $id ); ?>" method="post" novalidate
			data-list-id="<?php echo esc_attr( $list_id ); ?>"
			data-opt-in="<?php echo $opt_in ? '1' : '0'; ?>"
			data-skip-opt-in="<?php echo $skip ? '1' : '0'; ?>"
			data-success="<?php echo esc_attr( $atts['success'] ); ?>">

			<?php if ( '' !== $atts['title'] ) : ?>
				<h3 class="hlnl-form__title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<p class="hlnl-field">
				<label for="<?php echo esc_attr( $id ); ?>-firstname"><?php esc_html_e( 'First name', 'heyloyalty-newsletter' ); ?></label>
				<input type="text" name="firstname" id="<?php echo esc_attr( $id ); ?>-firstname" autocomplete="given-name" required>
			</p>
			<p class="hlnl-field">
				<label for="<?php echo esc_attr( $id ); ?>-lastname"><?php esc_html_e( 'Last name', 'heyloyalty-newsletter' ); ?></label>
				<input type="text" name="lastname" id="<?php echo esc_attr( $id ); ?>-lastname" autocomplete="family-name" required>
			</p>
			<p class="hlnl-field">
				<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'Email', 'heyloyalty-newsletter' ); ?></label>
				<input type="email" name="email" id="<?php echo esc_attr( $id ); ?>-email" autocomplete="email" required>
			</p>

			<p class="hlnl-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Leave this field empty', 'heyloyalty-newsletter' ); ?>
					<input type="text" name="hlnl_hp" tabindex="-1" autocomplete="off">
				</label>
			</p>

			<p class="hlnl-actions">
				<button type="submit" class="hlnl-submit"><?php echo esc_html( $atts['button'] ); ?></button>
			</p>

			<p class="hlnl-message" role="status" aria-live="polite"></p>
		</form>
		<?php
		return ob_get_clean();
	}

	private static function is_true( $value ) {
		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Only list IDs used by a published shortcode may be submitted to.
	 */
	private static function remember_list_id( $list_id ) {
		$known = self::get_known_list_ids();

		if ( in_array( $list_id, $known, true ) ) {
			return;
		}

		$known[] = $list_id;
		update_option( self::KNOWN_LISTS_OPTION, array_slice( $known, -50 ), false );
	}

	public static function get_known_list_ids() {
		$known = get_option( self::KNOWN_LISTS_OPTION, array() );
		$known = is_array( $known ) ? array_values( array_filter( array_map( 'strval', $known ) ) ) : array();

		$default = (string) HLNL_Settings::get_options()['default_list_id'];
		if ( '' !== $default && ! in_array( $default, $known, true ) ) {
			$known[] = $default;
		}

		return $known;
	}
}
