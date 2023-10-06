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
class Hooks extends \F4\WCTSV\Core\AbstractHooks {
	/**
	 * Initialize the hooks
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		self::add_action('F4/WCTSV/set_constants', 'set_default_constants', 1);
		self::add_action('plugins_loaded', 'core_loaded');
		self::add_action('setup_theme', 'core_after_loaded');
		self::add_action('init', 'load_textdomain');
		self::add_action('before_woocommerce_init', 'declare_woocommerce_compatibilities');

		self::register_activation_hook('core_loaded');
	}

	/**
	 * Sets the plugin default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {

	}

	/**
	 * Fires once the plugin is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function core_loaded() {
		do_action('F4/WCTSV/set_constants');
		do_action('F4/WCTSV/loaded');
	}

	/**
	 * Fires once after the plugin is loaded
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function core_after_loaded() {
		do_action('F4/WCTSV/after_loaded');
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
	 * Declare WooCommerce compatibilities.
	 *
	 * @since 2.0.6
	 * @access public
	 * @static
	 */
	public static function declare_woocommerce_compatibilities() {
		if(class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', F4_WCTSV_MAIN_FILE, true);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', F4_WCTSV_MAIN_FILE, true);
		}
	}
}
