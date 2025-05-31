<?php
/**
 * Plugin Name:     Buy Now Button for WooCommerce
 * Plugin URI:      
 * Description:     Buy your product only one step in the Product Detail page.
 * Author:          Codeixer
 * Author URI:      https://codeixer.com/
 * Text Domain:     buy-now-woo
 * Domain Path:     /languages
 * Version:         1.1.0
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to: 6.8
 * WC requires at least: 4.9
 * WC tested up to: 9.8.0
 *
 * @package         Woo_Buy_Now
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);
// Include the loader.
require_once __DIR__ . '/loader.php';


/**
 * And only works with PHP 7.4 or later.
 */
if ( version_compare( phpversion(), '7.4', '<' ) ) {
	/**
	 * Adds a message for outdate PHP version.
	 */
	function buy_now_woo_php_upgrade_notice() {
		$message = sprintf( esc_html__( 'WooCommerce Simple Buy Now requires at least PHP version 7.4 to work, you are running version %s. Please contact to your administrator to upgrade PHP version!', 'buy-now-woo' ), phpversion() );
		printf( '<div class="error"><p>%s</p></div>', $message ); // WPCS: XSS OK.

		deactivate_plugins( array( 'buy_now_woo/buy_now_woo.php' ) );
	}

	add_action( 'admin_notices', 'buy_now_woo_php_upgrade_notice' );

	return;
}

if ( defined( 'BUY_NOW_WOO_VERSION' ) ) {
	return;
}

define( 'BUY_NOW_WOO_VERSION', '1.1.0' );
define( 'BUY_NOW_WOO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BUY_NOW_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Admin notice: Require WooCommerce.
 */
function buy_now_woo_admin_notice() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		/* translators: 1. URL link. */
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Buy Now for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'buy-now-woo' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
	}
}

add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'WooCommerce' ) ) {
			$GLOBALS['buy_now_woo'] = Buy_Now_Woo::get_instance();
		}
		add_action( 'admin_notices', 'buy_now_woo_admin_notice', 4 );
	}
);
