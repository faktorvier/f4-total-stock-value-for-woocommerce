<?php

namespace F4\WCTSV\Analytics;

use F4\WCTSV\Core\Helpers as Core;

/**
 * Analytics helpers
 *
 * Helpers for the Analytics module.
 *
 * @since 2.0.0
 * @package F4\WCTSV\Analytics
 */
class Helpers {
	/**
	 * Check if the current page is the analytics page.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @return boolean TRUE if the current page is the analytics page, FALSE if not.
	 */
	public static function is_analytics_page() {
		return strpos(get_current_screen()->id ?? '', F4_WCTSV_SUBMENU_SLUG) !== false;
	}

	/**
	 * Get tax hint label.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param boolean|null $inc_taxes TRUE = inc. label, FALSE = ex. label and NULL = detect current setting.
	 * @param boolean $remove_brackets TRUE = remove brackets from label, FALSE = don't remove brackets.
	 * @return string The tax hint either inc or ex.
	 */
	public static function get_tax_hint($inc_taxes = null, $remove_brackets = true) {
		if(is_null($inc_taxes)) {
			$inc_taxes = wc_prices_include_tax();
		}

		$tax_hint = '';

		if($inc_taxes) {
			$tax_hint = WC()->countries->inc_tax_or_vat();
		} else {
			$tax_hint = WC()->countries->ex_tax_or_vat();
		}

		$tax_hint = str_replace(['(', ')'], '', $tax_hint);

		return $tax_hint;
	}

	/**
	 * Get current category filter either from $_REQUEST or user meta.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @return array An array with product category slugs.
	 */
	public static function get_current_filters() {
		$user_id = get_current_user_id();
		$filters = [];

		// Get filter from url or from database
		if(isset($_REQUEST['product_cat'])) {
			$filters['categories'] = explode(',', $_REQUEST['product_cat']);
			update_user_meta($user_id, F4_WCTSV_USER_META_KEY, $filters);
		} else {
			$filters = get_user_meta($user_id, F4_WCTSV_USER_META_KEY, true);
		}

		// Ensure that filters is an array
		if(!is_array($filters)) {
			$filters = [];
		}

		// Ensure that every filter exists, even it empty
		$filters = wp_parse_args($filters, [
			'categories' => []
		]);

		// Get category translation
		$filters['categories'] = Core::maybe_translate_term_id($filters['categories']);

		return $filters;
	}

	/**
	 * Get category ids with all children recursively by filter.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $categories An array with product category slugs.
	 * @return array An array with product category ids.
	 */
	public static function get_category_ttids_by_term_ids($categories = []) {
		$category_term_ids = [];
		$category_ttids = [];

		// Get children term_ids
		foreach($categories as $category_id) {
			$category_children = get_term_children($category_id, 'product_cat');
			$category_term_ids = array_merge($category_term_ids, [$category_id], $category_children);
		}

		// Convert term_ids to term_taxonomy_ids
		foreach($category_term_ids as $category_id) {
			$category = get_term($category_id, 'product_cat');

			if(!$category) {
				continue;
			}

			$category_ttids[] = $category->term_taxonomy_id;
		}

		return $category_ttids;
	}

	/**
	 * Format an integer.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param float|int $number The number to format.
	 * @return int The formatted number.
	 */
	public static function format_integer($number) {
		$number = number_format($number, 0, '.', wc_get_price_thousand_separator());

		return $number;
	}

	/**
	 * Get sql statement for a specific product type.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $product_type The product type. Supported are "simple" and "variation".
	 * @param array $filter The filter that should be applied to the values.
	 * @return string The sql statement.
	 */
	public static function get_sql_statement($product_type = 'simple', $filter = []) {
		global $wpdb;

		$sql_parts = [
			'select' => [],
			'from' => [],
			'where' => []
		];

		// SELECT
		$sql_parts['select'] = [
			'product.ID as ID',
			'pm_stock.meta_value as stock',
			'pm_price.meta_value as price',
			'pm_price_regular.meta_value as price_regular'
		];

		// Get post type
		$post_type = in_array($product_type, ['variation']) ? 'product_variation' : 'product';

		// Get post status
		$post_status = 'publish';

		// FROM: Add product type filter
		if($product_type === 'variation') {
			$sql_parts['from'][] = "
				INNER JOIN $wpdb->term_relationships AS tr_product_type_rel
					ON (
						product.post_parent = tr_product_type_rel.object_id
					)
				INNER JOIN $wpdb->term_taxonomy AS tt_product_type
					ON (
						tr_product_type_rel.term_taxonomy_id = tt_product_type.term_taxonomy_id
						AND tt_product_type.taxonomy = 'product_type'
					)
				INNER JOIN $wpdb->terms as term_product_type
					ON (
						term_product_type.term_id = tt_product_type.term_id
						AND term_product_type.slug = 'variable'
				)
			";

		} else {
			$sql_parts['from'][] = "
				INNER JOIN $wpdb->term_relationships AS tr_product_type_rel
					ON (
						product.id = tr_product_type_rel.object_id
					)
				INNER JOIN $wpdb->term_taxonomy AS tt_product_type
					ON (
						tr_product_type_rel.term_taxonomy_id = tt_product_type.term_taxonomy_id
						AND tt_product_type.taxonomy = 'product_type'
					)
				INNER JOIN $wpdb->terms as term_product_type
					ON (
						term_product_type.term_id = tt_product_type.term_id
						AND term_product_type.slug = 'simple'
				)
			";
		}

		// FROM: Add category filter
		if(!empty($filter['categories'])) {
			$sql_parts['from'][] = "
				INNER JOIN $wpdb->term_relationships as tr_category
					ON (
						product." . ($product_type === 'variation' ? 'post_parent' : 'id') . " = tr_category.object_id
						AND tr_category.term_taxonomy_id IN ( " . implode(',', $filter['categories']) . " )
					)
			";
		}

		// FROM: Ignore orphaned variations
		if($product_type === 'variation') {
			$sql_parts['from'][] = "
				INNER JOIN $wpdb->posts as product_parent
					ON (
						product.post_parent = product_parent.id
						AND product_parent.post_status = '$post_status'
					)
			";
		}

		// FROM: Add multilang filter
		if(function_exists('pll_is_translated_post_type') && pll_is_translated_post_type($post_type)) {
			$language_ttid = Core::get_ttid_by_slug(Core::maybe_get_default_language(), 'language');

			$sql_parts['from'][] = "
				INNER JOIN $wpdb->term_relationships as tr_ppl_language
					ON (
						product.id = tr_ppl_language.object_id
						AND tr_ppl_language.term_taxonomy_id = $language_ttid
					)
			";
		} elseif(class_exists('SitePress')) {
			$language = Core::maybe_get_default_language();

			$sql_parts['from'][] = "
				INNER JOIN {$wpdb->prefix}icl_translations as wpml_translation
					ON (
						product.id = wpml_translation.element_id
						AND wpml_translation.element_type = Concat('post_', product.post_type)
						AND wpml_translation.language_code = '$language'
					)
			";
		}

		// FROM: Add stock filter
		$sql_parts['from'][] = "
			INNER JOIN $wpdb->postmeta as pm_stock
				ON (
					product.id = pm_stock.post_id
					AND pm_stock.meta_key = '_stock'
					AND pm_stock.meta_value > 0
				)
			INNER JOIN $wpdb->postmeta as pm_manage_stock
				ON (
					product.id = pm_manage_stock.post_id
					AND pm_manage_stock.meta_key = '_manage_stock'
					AND pm_manage_stock.meta_value = 'yes'
				)
			LEFT JOIN $wpdb->postmeta as pm_price
				ON (
					product.id = pm_price.post_id
					AND pm_price.meta_key = '_price'
				)
			LEFT JOIN $wpdb->postmeta as pm_price_regular
				ON (
					product.id = pm_price_regular.post_id
					AND pm_price_regular.meta_key = '_regular_price'
				)
		";

		// WHERE: Add post type filter
		$sql_parts['where'][] = "
			AND product.post_type = '$post_type'
		";

		// WHERE: Add post status filter
		$sql_parts['where'][] = "
			AND product.post_status = '$post_status'
		";

		return "
			SELECT " . implode(',', $sql_parts['select']) . "
			FROM $wpdb->posts as product " . implode(' ', $sql_parts['from']) . "
			WHERE 1 = 1 " . implode(' ', $sql_parts['where']) . "
			GROUP BY product.id
		";
	}

	/**
	 * Get statistics.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $filter The filter that should be applied to the values.
	 * @return array The calculated stock values.
	 */
	public static function get_stock_value_statistics($filter = []) {
		global $wpdb;

		// Set default params
		$filter = wp_parse_args($filter, [
			'categories' => []
		]);

		// Prepare filters
		$filter['categories'] = self::get_category_ttids_by_term_ids($filter['categories']);

		// Execute sql statement
		$products = $wpdb->get_results(
			self::get_sql_statement('simple', $filter)
			. " UNION" .
			self::get_sql_statement('variation', $filter)
		);

		// Calculate values
		$values = [
			'units' => 0,
			'price' => 0,
			'price_regular' => 0
		];

		foreach($products as $product) {
			$values['units'] += (int)$product->stock;
			$values['price'] += ((int)$product->stock * Core::maybe_zero($product->price));
			$values['price_regular'] += ((int)$product->stock * Core::maybe_zero($product->price_regular));
		}

		// Set default statistics
		$statistics = [
			'units' => [
				'label' => __('Units in stock', 'f4-total-stock-value-for-woocommerce'),
				'value' => self::format_integer($values['units']),
				'color' => '#AAAAAA',
				'info' => str_replace(
					'%count%',
					self::format_integer(count($products)),
					_n('%count% product', '%count% products', count($products), 'f4-total-stock-value-for-woocommerce')
				)
			],
			'regular_value' => [
				'label' => __('Total stock value (regular prices)', 'f4-total-stock-value-for-woocommerce'),
				'value' => wc_price($values['price_regular']),
				'color' => '#007cba'
			],
			'current_value' => [
				'label' => __('Total stock value (with sale prices)', 'f4-total-stock-value-for-woocommerce'),
				'value' => wc_price($values['price']),
				'color' => '#F1C40F'
			]
		];

		// Allow manipulation by third party hooks
		$statistics = apply_filters('F4/WCTSV/get_stock_value_statistics', $statistics, $values, $products, $filter);

		// Prepare statistics
		foreach($statistics as $key => &$statistic) {
			$statistic = wp_parse_args($statistic, [
				'label' => '',
				'value' => '',
				'color' => '#AAAAAA',
				'info' => ''
			]);
		}

		return $statistics;
	}
}
