<?php
/**
 * Plugin Name: HeyLoyalty Newsletter
 * Description: Newsletter signup form shortcode that creates members in HeyLoyalty.
 * Version:     1.0.0
 * Author:      -
 * License:     GPL-2.0-or-later
 * Text Domain: heyloyalty-newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HLNL_VERSION', '1.0.0' );
define( 'HLNL_FILE', __FILE__ );
define( 'HLNL_PATH', plugin_dir_path( __FILE__ ) );
define( 'HLNL_URL', plugin_dir_url( __FILE__ ) );

require_once HLNL_PATH . 'includes/class-hlnl-api.php';
require_once HLNL_PATH . 'includes/class-hlnl-settings.php';
require_once HLNL_PATH . 'includes/class-hlnl-shortcode.php';
require_once HLNL_PATH . 'includes/class-hlnl-rest.php';

add_action( 'plugins_loaded', static function () {
	HLNL_Settings::init();
	HLNL_Shortcode::init();
	HLNL_Rest::init();
} );
