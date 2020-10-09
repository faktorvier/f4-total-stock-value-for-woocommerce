<?php

namespace F4\WCTSV\Core;

/**
 * Core Helpers
 *
 * Helpers for the Core module
 *
 * @since 1.0.0
 * @package F4\WCTSV\Core
 */
class Helpers {
	/**
	 * Returns zero if input is not a number
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function maybe_zero($input) {
		return !is_numeric($input) ? 0 : $input;
	}

	/**
	 * Check if product is assigned to a term or its children
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 * @return boolean TRUE if assigned, FALSE if not
	 */
	public static function has_product_category_recursive($terms, $post_id = null) {
        foreach($terms as $term_slug) {
			$term_id = get_term_by('slug', $term_slug, 'product_cat')->term_id;

            $descendants = get_term_children($term_id, 'product_cat' );
			$descendants[] = $term_id;

            if($descendants && has_term($descendants, 'product_cat', $post_id)) {
                return true;
			}
        }

        return false;
    }

	/**
	 * Show total stock value page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_total_stock_value($filter = []) {
		$filter = wp_parse_args($filter, [
			'categories' => []
		]);

		$total_value = [
			'count' => 0,
			'regular_value' => 0,
			'current_value' => 0
		];

		$product_posts = get_posts([
			'post_type' => ['product', 'product_variation'],
			'post_status' => ['publish'],
			'nopaging' => true,
			'meta_query' => [
				[
					'key' => '_stock',
					'value' => 0,
					'compare' => '>'
				],
				[
					'key' => '_manage_stock',
					'value' => 'yes'
				],
			]
		]);

		foreach($product_posts as $product_post) {
			$product = wc_get_product($product_post->ID);

			// Only calculate simple and variation products
			if(!in_array($product->get_type(), ['simple', 'variation'])) {
				continue;
			}

			// Skip orphaned variations
			if($product->get_type() === 'variation' && $product->get_parent_id() === 0) {
				continue;
			}

			// Apply category filter
			if(!empty($filter['categories'])) {
				$product_id = $product->get_parent_id();
				$product_id = !$product_id ? $product->get_id() : $product_id;

				if(!self::has_product_category_recursive($filter['categories'], $product_id)) {
					continue;
				}
			}

			// Calculate count and values
			$product_stock_quantity = (int)$product->get_stock_quantity();
			$product_regular_price = self::maybe_zero($product->get_regular_price());
			$product_price = self::maybe_zero($product->get_price());

			$total_value['count'] += $product_stock_quantity;
			$total_value['regular_value'] += ($product_regular_price * $product_stock_quantity);
			$total_value['current_value'] += ($product_price * $product_stock_quantity);
		}

		return $total_value;
	}
}

?>
