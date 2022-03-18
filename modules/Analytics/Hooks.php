<?php

namespace F4\WCTSV\Analytics;

use F4\WCTSV\Core\Helpers as Core;
use F4\WCTSV\Core\Options\Helpers as Options;

/**
 * Analytics hooks
 *
 * Hooks for the Analytics module.
 *
 * @since 2.0.0
 * @package F4\WCTSV\Analytics
 */
class Hooks extends \F4\WCTSV\Core\AbstractHooks {
	/**
	 * Initialize the hooks.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		self::add_action('F4/WCTSV/set_constants', 'set_default_constants', 99);
		self::add_action('F4/WCTSV/loaded', 'loaded');
	}

	/**
	 * Sets the module default constants.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {
		if(!defined('F4_WCTSV_SUBMENU_SLUG')) {
			define('F4_WCTSV_SUBMENU_SLUG', 'f4-total-stock-value');
		}

		if(!defined('F4_WCTSV_SUBMENU_PARENT_SLUG')) {
			define('F4_WCTSV_SUBMENU_PARENT_SLUG', 'wc-admin&path=/analytics/overview');
		}

		if(!defined('F4_WCTSV_SUBMENU_ADD_AFTER_SLUG')) {
			define('F4_WCTSV_SUBMENU_ADD_AFTER_SLUG', 'wc-admin&path=/analytics/stock');
		}

		if(!defined('F4_WCTSV_SUBMENU_CAPABILITY')) {
			define('F4_WCTSV_SUBMENU_CAPABILITY', 'view_woocommerce_reports');
		}

		if(!defined('F4_WCTSV_SUBMENU_URL')) {
			define('F4_WCTSV_SUBMENU_URL', 'admin.php?page=' . F4_WCTSV_SUBMENU_SLUG);
		}

		if(!defined('F4_WCTSV_USER_META_KEY')) {
			define('F4_WCTSV_USER_META_KEY', 'f4-total-stock-value-filter');
		}
	}

	/**
	 * Fires once the module is loaded.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function loaded() {
		self::add_action('admin_menu', 'add_analytics_submenu', 99);
		self::add_filter('woocommerce_admin_reports', 'add_report_tab', 10, 1);
		self::add_action('admin_enqueue_scripts', 'add_custom_admin_styles');
		self::add_action('admin_action_total-stock-value-filter',  'apply_product_cat_filter');
	}

	/**
	 * Add submenu item to the analytics menu.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function add_analytics_submenu() {
		global $current_screen, $parent_file, $submenu_file, $submenu;

		// Skip if analytics menu is not registered
		if(!isset($submenu[F4_WCTSV_SUBMENU_PARENT_SLUG])) {
			return;
		}

		// Register submenu page
		add_submenu_page(
			F4_WCTSV_SUBMENU_PARENT_SLUG,
			__('Stock value', 'f4-total-stock-value-for-woocommerce') . ' &lsaquo; ' . __('Analytics', 'woocommerce') . ' &lsaquo; ' . __('WooCommerce', 'woocommerce'),
			__('Stock value', 'f4-total-stock-value-for-woocommerce'),
			F4_WCTSV_SUBMENU_CAPABILITY,
			F4_WCTSV_SUBMENU_SLUG,
			function() {
				include F4_WCTSV_PATH . 'modules/Analytics/views/report.php';
			}
		);

		// Move new submenu right after the already existing stock submenu
		$stock_value_position = array_search(
			F4_WCTSV_SUBMENU_SLUG,
			array_column($submenu[F4_WCTSV_SUBMENU_PARENT_SLUG], '2')
		);

		$submenu_new = [];

		foreach($submenu[F4_WCTSV_SUBMENU_PARENT_SLUG] as $index => $item) {
			if($item[2] !== F4_WCTSV_SUBMENU_SLUG) {
				$submenu_new[] = $item;
			}

			if($item[2] === F4_WCTSV_SUBMENU_ADD_AFTER_SLUG && $stock_value_position !== false) {
				$submenu_new[] = $submenu[F4_WCTSV_SUBMENU_PARENT_SLUG][$stock_value_position];
			}
		}

		$submenu[F4_WCTSV_SUBMENU_PARENT_SLUG] = $submenu_new;
	}

	/**
	 * Add new tab to reports.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $reports The already registered report tabs.
	 * @return array All the registered report tabs.
	 */
	public static function add_report_tab($reports) {
		$reports['stock']['reports']['value'] = [
			'title' => __('Total stock value', 'f4-total-stock-value-for-woocommerce'),
			'description' => '',
			'hide_title' => true,
			'callback' => function() {
				echo '<div class="notice notice-info">
					<p>' . str_replace('%url%', F4_WCTSV_SUBMENU_URL, __('We\'ve moved the total stock value reports to the new <a href="%url%">Analytics section</a>.', 'f4-total-stock-value-for-woocommerce')) . '</p>
				</div>';
			}
		];

		return $reports;
	}

	/**
	 * Add styles and scripts to the analytics page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_custom_admin_styles() {
		if(!Helpers::is_analytics_page()) {
			return;
		}

		wp_enqueue_style(F4_WCTSV_SLUG, F4_WCTSV_URL . 'assets/css/main.css', [], F4_WCTSV_VERSION);
		wp_enqueue_style('woocommerce_admin_styles');
		wp_enqueue_script('selectWoo');
	}

	/**
	 * Apply product category filter.
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 */
	public static function apply_product_cat_filter() {
		$categories = $_REQUEST['product_cat'] ?? [];
		$redirect = wp_get_referer();
		$user_id = get_current_user_id();

		if(empty($categories)) {
			$redirect = remove_query_arg('product_cat', $redirect);
			delete_user_meta($user_id, F4_WCTSV_USER_META_KEY);
		} else {
			$redirect = add_query_arg('product_cat', implode(',', $categories), $redirect);
			update_user_meta($user_id, F4_WCTSV_USER_META_KEY, $categories);
		}

		wp_redirect($redirect);
		exit();
	}
}
