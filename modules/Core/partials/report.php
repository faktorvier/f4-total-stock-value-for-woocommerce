<?php

use F4\WCTSV\Core\Helpers;

$categories_filter = [];
$user_id = get_current_user_id();

if(isset($_REQUEST['product_cat'])) {
	$categories_filter = $_REQUEST['product_cat'];
	$categories_filter = explode(',', $categories_filter);
	update_user_meta($user_id, 'total-sale-value-filter', $categories_filter);
} else {
	$categories_filter = get_user_meta($user_id, 'total-sale-value-filter', true);
	$categories_filter = !is_array($categories_filter) ? [] : $categories_filter;
}

$total_stock_value = Helpers::get_total_stock_value([
	'categories' => $categories_filter
]);


if(wc_prices_include_tax()) {
	$tax_hint = WC()->countries->inc_tax_or_vat();
} else {
	$tax_hint = WC()->countries->ex_tax_or_vat();
}

?>

<form action="<?php echo admin_url('admin.php'); ?>" method="post" class="total-sale-value-filter">
	<input type="hidden" name="action" value="total-sale-value-filter">
	<?php wp_referer_field(); ?>

	<select class="total-sale-value-filter__select" name="product_cat[]" size="1" multiple="multiple">
		<?php
			ob_start();

			wc_product_dropdown_categories([
				'show_option_none' => '',
				'show_count' => 0
			]);

			$select = ob_get_clean();
			$select = preg_replace('/<select[^>]*>/', '', $select);
			$select = preg_replace('/<\/select>/', '', $select);
			$select = preg_replace('/(value="(' . implode('|', $categories_filter) . ')")/', '$1 selected', $select);

			echo $select;
		?>
	</select>

	<input
		type="submit"
		class="total-sale-value-filter__button button action"
		value="<?php _e('Apply filter', 'f4-total-stock-value-for-woocommerce'); ?>"
	/>

	<script>
		jQuery(function($) {
			let $select = $('.total-sale-value-filter__select');

			$select.selectWoo({
				placeholder: '<?php _e('All products', 'f4-total-stock-value-for-woocommerce'); ?>',
				allowClear: true
			});
		});
	</script>
</form>

<div class="total-sale-value">
	<div class="total-sale-value__item total-sale-value__item--units">
		<div class="total-sale-value__value">
			<?php echo $total_stock_value['count']; ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Units in stock', 'f4-total-stock-value-for-woocommerce'); ?>
		</div>
	</div>

	<div class="total-sale-value__item total-sale-value__item--value">
		<div class="total-sale-value__value">
			<?php echo wc_price($total_stock_value['regular_value']); ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Total stock value (regular prices)', 'f4-total-stock-value-for-woocommerce'); ?>
			<?php echo $tax_hint; ?>
		</div>
	</div>

	<div class="total-sale-value__item total-sale-value__item--value-sale">
		<div class="total-sale-value__value">
			<?php echo wc_price($total_stock_value['current_value']); ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Total stock value (with sale prices)', 'f4-total-stock-value-for-woocommerce'); ?>
			<?php echo $tax_hint; ?>
		</div>
	</div>
</div>
