<?php

namespace F4\WCTSV\Core;

/**
 * Core Helpers
 *
 * Helpers for the Core module.
 *
 * @since 1.0.0
 * @package F4\WCTSV\Core
 */
class Helpers {
	/**
	 * Get F4 link.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @return string The F4 link.
	 */
	public static function get_f4_link() {
		echo 'https://www.f4dev.ch?utm_campaign=wp_backend&utm_medium=' . F4_WCTSV_SLUG;
	}

	/**
	 * Get plugin infos.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $info_name The info name to show.
	 * @return string The requested plugin info.
	 */
	public static function get_plugin_info($info_name) {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		$info_value = null;
		$plugin_infos = get_plugin_data(F4_WCTSV_PLUGIN_FILE_PATH);

		if(isset($plugin_infos[$info_name])) {
			$info_value = $plugin_infos[$info_name];
		}

		return $info_value;
	}

	/**
	 * Checks if any/all of the values are in an array.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $needle An array with values to search.
	 * @param array $haystack The array.
	 * @param bool $must_contain_all TRUE if all needes must be found in the haystack, FALSE if only one is needed.
	 * @return bool Returns TRUE if one of the needles is found in the array, FALSE otherwise.
	 */
	public static function array_in_array($needle, $haystack, $must_contain_all = false) {
		if($must_contain_all) {
			return !array_diff($needle, $haystack);
		} else {
			return (count(array_intersect($haystack, $needle))) ? true : false;
		}
	}

	/**
	 * Forces a variable to be an array.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param mixed $value An array with values to search.
	 * @param bool $append_value TRUE if the value should be appended to the array, FALSE if only an empty array should be returned.
	 * @return array The value as array.
	 */
	public static function maybe_force_array($value, $append_value = true) {
		if(!is_array($value)) {
			if($append_value && $value) {
				$value = array($value);
			} else {
				$value = array();
			}
		}

		return $value;
	}

	/**
	 * Insert one or more elements before a specific key.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $array The original array.
	 * @param string|array $search_key One or more keys to insert the values before.
	 * @param array $target_values The associative array to insert.
	 * @return array The new array.
	 */
	public static function insert_before_key($array, $search_key, $target_values) {
		$array_new = array();

		if(!is_array($target_values)) {
			$target_values = array($target_values);
		}

		foreach($array as $key => $value) {
			if($key === $search_key) {
				foreach($target_values as $target_key => $target_value) {
					$array_new[$target_key] = $target_value;
				}
			}

			$array_new[$key] = $value;
		}

		return $array_new;
	}

	/**
	 * Insert one or more elements after a specific key.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $array The original array.
	 * @param string|array $search_key One or more keys to insert the values after.
	 * @param array $target_values The associative array to insert.
	 * @return array The new array.
	 */
	public static function insert_after_key($array, $search_key, $target_values) {
		$array_new = array();

		if(!is_array($target_values)) {
			$target_values = array($target_values);
		}

		foreach($array as $key => $value) {
			$array_new[$key] = $value;

			if($key === $search_key) {
				foreach($target_values as $target_key => $target_value) {
					$array_new[$target_key] = $target_value;
				}
			}
		}

		return $array_new;
	}

	/**
	 * Sort array by key.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array $array The unsorted array.
	 * @param array $key The key name to sort the array.
	 * @return array The sorted array.
	 */
	public static function sort_array_by_key($array, $key) {
		$array_sorted = $array;

		uasort($array_sorted, function($a, $b) use ($key) {
			return strcasecmp($a[$key], $b[$key]);
		});

		return $array_sorted;
	}

	/**
	 * Check if current post is specific post type.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function is_post_type($post_type, $post = null) {
		$is_post_type = false;

		if(function_exists('get_post_type')) {
			$is_post_type = get_post_type($post) === $post_type;
		}

		return $is_post_type;
	}

	/**
	 * Returns zero if input is not a number.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param mixed $input The input value.
	 * @return mixed The input value or zero if the input is not a number.
	 */
	public static function maybe_zero($input) {
		return !is_numeric($input) ? 0 : $input;
	}

	/**
	 * Get default language if current language is "all".
	 *
	 * @since 1.1.4
	 * @access public
	 * @static
	 */
	public static function maybe_get_default_language() {
		global $sitepress;

		$lang = false;

		if(defined('ICL_LANGUAGE_CODE')) {
			$lang = ICL_LANGUAGE_CODE;

			if(ICL_LANGUAGE_CODE === 'all') {
				if(function_exists('pll_default_language')) {
					$lang = pll_default_language();
				} elseif($sitepress) {
					$lang = $sitepress->get_default_language();
				}
			}
		}

		return $lang;
	}

	/**
	 * Translate term_id if WPML or Polylang is active.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param array|int $term_id A single term_id or an array with multiple term_ids.
	 * @param string|null $lang Code of the language, NULL for current language.
	 * @return array|int A single translated term_id or an array with multiple translated term_ids.
	 */
	public static function maybe_translate_term_id($term_id, $lang = null) {
		global $sitepress;

		if(function_exists('pll_get_term')) {
			// Polylang
			if(is_array($term_id)) {
				foreach($term_id as &$term_id_item) {
					$term_id_item = pll_get_term($term_id_item);
				}
			} else {
				$term_id = pll_get_term($term_id, $lang);
			}
		} elseif($sitepress) {
			// WPML
			if(is_null($lang)) {
				$lang = wpml_get_current_language();
			}

			if(is_array($term_id)) {
				foreach($term_id as &$term_id_item) {
					$term_id_item = apply_filters('wpml_object_id', $term_id_item, get_term($term_id_item)->taxonomy, true, $lang);
				}
			} else {
				$term_id = apply_filters('wpml_object_id', $term_id, get_term($term_id)->taxonomy, true, $lang);
			}
		}

		return $term_id;
	}

	/**
	 * Get term_taxonomy_id by term slug.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $slug The term string.
	 * @param string $taxonomy The taxonomy.
	 * @return int The term_taxonomy_id if the term exists, 0 if not.
	 */
	public static function get_ttid_by_slug($slug, $taxonomy) {
		$term = get_term_by('slug', $slug, $taxonomy);
		$term_taxonomy_id = $term->term_taxonomy_id ?? 0;

		return $term_taxonomy_id;
	}
}
