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
	 * @return string The tax hint either incl or excl.
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

		return $filters ;
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

		// FROM: Add product type filter
		if($product_type === 'variation') {
			$variable_product_ttid = Core::get_ttid_by_slug('variable', 'product_type');

			$sql_parts['from'][] = "
				INNER JOIN wp_term_relationships as tt_product_type
					ON (
						product.post_parent = tt_product_type.object_id
						AND tt_product_type.term_taxonomy_id = $variable_product_ttid
					)
			";
		} else {
			$simple_product_ttid = Core::get_ttid_by_slug($product_type, 'product_type');

			$sql_parts['from'][] = "
				INNER JOIN wp_term_relationships as tt_product_type
					ON (
						product.id = tt_product_type.object_id
						AND tt_product_type.term_taxonomy_id = $simple_product_ttid
					)
			";
		}

		// FROM: Add category filter
		if(!empty($filter['categories'])) {
			$sql_parts['from'][] = "
				INNER JOIN wp_term_relationships as tt_category
					ON (
						product." . ($product_type === 'variation' ? 'post_parent' : 'id') . " = tt_category.object_id
						AND tt_category.term_taxonomy_id IN ( " . implode(',', $filter['categories']) . " )
					)
			";
		}

		// FROM: Ignore orphaned variations
		if($product_type === 'variation') {
			$sql_parts['from'][] = "
				INNER JOIN wp_posts as product_parent
					ON ( product.post_parent = product_parent.id )
			";
		}

		// FROM: Add polylang filter
		if(function_exists('pll_default_language')) {
			// @todo: check if product posttype is translatable
			// @todo: product.id or product.post_parent if variation?

			$language_ttid = Core::get_ttid_by_slug(Core::maybe_get_default_language(), 'language');

			$sql_parts['from'][] = "
				INNER JOIN wp_term_relationships as tt_ppl_language
					ON (
						product.id = tt_ppl_language.object_id
						AND tt_ppl_language.term_taxonomy_id = $language_ttid
					)
			";
		}

		// FROM: Add stock filter
		$sql_parts['from'][] = "
			INNER JOIN wp_postmeta as pm_stock
				ON (
					product.id = pm_stock.post_id
					AND pm_stock.meta_key = '_stock'
					AND pm_stock.meta_value > 0
				)
			INNER JOIN wp_postmeta as pm_manage_stock
				ON (
					product.id = pm_manage_stock.post_id
					AND pm_manage_stock.meta_key = '_manage_stock'
					AND pm_manage_stock.meta_value = 'yes'
				)
			LEFT JOIN wp_postmeta as pm_price
				ON (
					product.id = pm_price.post_id
					AND pm_price.meta_key = '_price'
				)
			LEFT JOIN wp_postmeta as pm_price_regular
				ON (
					product.id = pm_price_regular.post_id
					AND pm_price_regular.meta_key = '_regular_price'
				)
		";

		// WHERE: Add post type filter
		if($product_type === 'variation') {
			$sql_parts['where'][] = "
				AND product.post_type IN ( 'product_variation' )
			";
		} else {
			$sql_parts['where'][] = "
				AND product.post_type IN ( 'product' )
			";
		}

		// WHERE: Add post status filter
		$sql_parts['where'][] = "
			AND product.post_status IN ( 'publish' )
		";

		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<br />';
		// echo '<pre>';
		// echo "
		// 	SELECT " . implode(',', $sql_parts['select']) . "
		// 	FROM wp_posts as product " . implode(' ', $sql_parts['from']) . "
		// 	WHERE 1 = 1 " . implode(' ', $sql_parts['where']) . "
		// 	GROUP BY product.id
		// ";
		// echo '</pre>';

		return "
			SELECT " . implode(',', $sql_parts['select']) . "
			FROM wp_posts as product " . implode(' ', $sql_parts['from']) . "
			WHERE 1 = 1 " . implode(' ', $sql_parts['where']) . "
			GROUP BY product.id
		";

		// NEW (WPML)
		// SELECT wp_posts.*
		// FROM   wp_posts
		// 	LEFT JOIN wp_term_relationships
		// 			ON ( wp_posts.id = wp_term_relationships.object_id )
		// 	LEFT JOIN wp_term_relationships AS tt1
		// 			ON ( wp_posts.id = tt1.object_id )
		// 	INNER JOIN wp_postmeta
		// 			ON ( wp_posts.id = wp_postmeta.post_id )
		// 	INNER JOIN wp_postmeta AS mt1
		// 			ON ( wp_posts.id = mt1.post_id )
		// 	LEFT JOIN wp_icl_translations wpml_translations
		// 			ON wp_posts.id = wpml_translations.element_id
		// 				AND wpml_translations.element_type =
		// 					Concat('post_', wp_posts.post_type)
		// WHERE  1 = 1
		// 	AND ( wp_term_relationships.term_taxonomy_id IN ( 2 )
		// 			AND ( tt1.term_taxonomy_id IN ( 16, 17, 18, 19 ) ) )
		// 	AND ( ( wp_postmeta.meta_key = '_stock'
		// 			AND wp_postmeta.meta_value > '0' )
		// 			AND ( mt1.meta_key = '_manage_stock'
		// 				AND mt1.meta_value = 'yes' ) )
		// 	AND wp_posts.post_type IN ( 'product', 'product_variation' )
		// 	AND (( wp_posts.post_status = 'publish' ))
		// 	AND ( ( ( wpml_translations.language_code = 'de'
		// 				OR 0 )
		// 			AND wp_posts.post_type IN ( 'post', 'page', 'attachment',
		// 										'wp_block',
		// 										'wp_template', 'wp_template_part',
		// 										'wp_navigation'
		// 										,
		// 											'product',
		// 										'product_variation' )
		// 				)
		// 			OR wp_posts.post_type NOT IN ( 'post', 'page', 'attachment',
		// 											'wp_block',
		// 											'wp_template', 'wp_template_part',
		// 											'wp_navigation',
		// 												'product',
		// 											'product_variation' ) )
		// GROUP  BY wp_posts.id
		// ORDER  BY wp_posts.post_date DESC
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
			//if(!is_numeric($product->stock) || !is_numeric($product->price) || !is_numeric($product->price_regular)) {
				//continue;
			//}

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
				'color' => '#007cba',
				//'info' => '100%'
			],
			'current_value' => [
				'label' => __('Total stock value (with sale prices)', 'f4-total-stock-value-for-woocommerce'),
				'value' => wc_price($values['price']),
				'color' => '#F1C40F',
				//'info' => round($values['price'] / $values['price_regular'] * 100) . '%'
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
