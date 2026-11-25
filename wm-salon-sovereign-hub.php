<?php
/**
 * Plugin Name:       WM Salon Sovereign Hub
 * Plugin URI:        https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/
 * Description:       Zwei Rechner für Friseursalons: Umsatzaufteilung bei Stuhlmiete (Salon / selbstständiger Stylist) und lokaler INCI-Inhaltsstoff-Prüfer, als Shortcode, REST-Route und WP-CLI-Befehl.
 * Version:           1.2.1
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Author:            Arnold Wender
 * Author URI:        https://arnoldwender.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wm-salon-sovereign-hub
 * Domain Path:       /languages
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WM_SALON_HUB_VERSION', '1.2.1' );
define( 'WM_SALON_HUB_FILE', __FILE__ );
define( 'WM_SALON_HUB_PATH', plugin_dir_path( __FILE__ ) );

// Core subsystems.
require_once WM_SALON_HUB_PATH . 'includes/class-salon-stuhlmiete-ledger.php';
require_once WM_SALON_HUB_PATH . 'includes/class-salon-inci-analyzer.php';
require_once WM_SALON_HUB_PATH . 'includes/class-salon-shortcodes.php';
require_once WM_SALON_HUB_PATH . 'includes/class-salon-rest-controller.php';
require_once WM_SALON_HUB_PATH . 'includes/class-salon-integrations.php';

WM_Salon_Shortcodes::init();
WM_Salon_REST_Controller::init();
WM_Salon_Integrations::init();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WM_SALON_HUB_PATH . 'includes/class-salon-cli.php';
}

/**
 * Bundled catalogs. WordPress before 7.1 registers a plugin's own languages/ folder only through this call.
 */
add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'wm-salon-sovereign-hub', false, dirname( plugin_basename( WM_SALON_HUB_FILE ) ) . '/languages' );
		do_action( 'wm_salon_ready', WM_SALON_HUB_VERSION );
	}
);

/**
 * Register with WM Suite Hub if active.
 */
function wm_salon_register_spoke_module(): void {
	if ( interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
		require_once WM_SALON_HUB_PATH . 'includes/class-salon-spoke-adapter.php';
		if ( class_exists( 'WM_Salon_Spoke_Adapter' ) ) {
			add_filter(
				'wm_register_suite_module',
				static function ( array $modules ): array {
					$modules[] = new WM_Salon_Spoke_Adapter();
					return $modules;
				}
			);
		}
	}
}
add_action( 'plugins_loaded', 'wm_salon_register_spoke_module', 20 );
