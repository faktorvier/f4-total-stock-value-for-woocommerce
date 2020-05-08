<?php

namespace F4\WCTSV\Core;

/**
 * Core Hooks
 *
 * Hooks for the Core module
 *
 * @since 1.0.0
 * @package F4\WCTSV\Core
 */
class Hooks {
	/**
	 * Initialize the hooks
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		add_action('plugins_loaded', __NAMESPACE__ . '\\Hooks::core_loaded');
		add_action('F4/WCTSV/Core/set_constants', __NAMESPACE__ . '\\Hooks::set_default_constants', 98);

		add_filter('woocommerce_admin_reports', __NAMESPACE__ . '\\Hooks::add_report_tab', 10, 1);
		add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\Hooks::add_custom_admin_styles');
	}

	/**
	 * Sets the module default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {}

	/**
	 * Fires once the core module is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function core_loaded() {
		do_action('F4/WCTSV/Core/set_constants');
		do_action('F4/WCTSV/Core/loaded');

		add_action('init', __NAMESPACE__ . '\\Hooks::load_textdomain');
	}

	/**
	 * Load plugin textdomain
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_textdomain() {
		load_plugin_textdomain('f4-total-stock-value-for-woocommerce', false, plugin_basename(F4_WCTSV_PATH . 'languages') . '/');
	}

	/**
	 * Add new tab to reports
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_report_tab($reports) {
		$reports['stock']['reports']['value'] = [
			'title' => __('Total stock value', 'f4-total-stock-value-for-woocommerce'),
			'description' => '',
			'hide_title' => true,
			'callback' => function() {
				include F4_WCTSV_PATH . 'modules/Core/partials/report.php';
			}
		];

		return $reports;
	}

	/**
	 * Add css file for the report page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_custom_admin_styles() {
		$is_report_page = get_current_screen()->id === 'woocommerce_page_wc-reports';
		$is_total_value_tab = isset($_GET['tab']) && $_GET['tab'] === 'stock' && isset($_GET['report']) && $_GET['report'] === 'value';

		if($is_total_value_tab && $is_report_page) {
			wp_enqueue_style(F4_WCTSV_SLUG, F4_WCTSV_URL . 'assets/css/main.css', [], F4_WCTSV_VERSION);
		}
	}
}

?>
