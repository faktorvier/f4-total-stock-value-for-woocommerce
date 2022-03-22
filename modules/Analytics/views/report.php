<?php

use F4\WCTSV\Core\Helpers;
use F4\WCTSV\Analytics\Helpers as Analytics;

// Get current product categories filter
$current_filters = Analytics::get_current_filters();

// Get statistics
$statistics = Analytics::get_stock_value_statistics($current_filters);

?>

<div class="total-stock-value">
	<div class="total-stock-value__header">
		<h1 class="total-stock-value__headline">
			<?php _e('Stock value', 'f4-total-stock-value-for-woocommerce'); ?>
		</h1>

		<a class="total-stock-value__f4-logo" href="<?php echo Helpers::get_f4_link(); ?>" target="_blank">
			<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="75px" height="100px" viewBox="0 0 75 100" xml:space="preserve">
				<path d="M74.295,21.04c0,1.38-1.122,2.504-2.502,2.504H54.259c-1.384,0-2.504-1.124-2.504-2.504V3.504
					c0-1.379,1.12-2.504,2.504-2.504h17.534c1.38,0,2.502,1.125,2.502,2.504V21.04z"></path>
				<path d="M74.295,46.562c0,1.384-1.122,2.506-2.502,2.506H54.259c-1.384,0-2.504-1.122-2.504-2.506V29.029
					c0-1.382,1.12-2.504,2.504-2.504h17.534c1.38,0,2.502,1.122,2.502,2.504V46.562z"></path>
				<path d="M74.295,72.086c0,1.384-1.122,2.506-2.502,2.506H54.259c-1.384,0-2.504-1.122-2.504-2.506V54.557
					c0-1.387,1.12-2.506,2.504-2.506h17.534c1.38,0,2.502,1.119,2.502,2.506V72.086z"></path>
				<path d="M48.769,46.562c0,1.384-1.12,2.506-2.502,2.506H28.733c-1.384,0-2.504-1.122-2.504-2.506V29.029
					c0-1.382,1.12-2.504,2.504-2.504h17.533c1.382,0,2.502,1.122,2.502,2.504V46.562z"></path>
				<path d="M48.769,72.086c0,1.384-1.12,2.506-2.502,2.506H28.733c-1.384,0-2.504-1.122-2.504-2.506V54.557
					c0-1.387,1.12-2.506,2.504-2.506h17.533c1.382,0,2.502,1.119,2.502,2.506V72.086z"></path>
				<path d="M23.247,72.086c0,1.384-1.124,2.506-2.503,2.506H3.21c-1.384,0-2.505-1.122-2.505-2.506V54.557
					c0-1.387,1.122-2.506,2.505-2.506h17.533c1.379,0,2.503,1.119,2.503,2.506V72.086z"></path>
				<path d="M53.833,98.412c-1.086,1.085-2.078,0.581-2.078-0.799V80.077c0-1.38,1.12-2.501,2.504-2.501h17.534
					c1.38,0,1.768,1.106,0.798,2.075L53.833,98.412z"></path>
				<path d="M46.691,2.708c1.084-1.087,2.077-0.583,2.077,0.796v17.534c0,1.382-1.12,2.506-2.502,2.506H28.733
					c-1.384,0-1.771-1.107-0.799-2.08L46.691,2.708z"></path>
				<path d="M21.167,28.229c1.086-1.085,2.08-0.582,2.08,0.8v17.532c0,1.384-1.124,2.506-2.503,2.506H3.21
					c-1.384,0-1.773-1.107-0.801-2.078L21.167,28.229z"></path>
			</svg>
		</a>
	</div>

	<div class="total-stock-value__main">
		<form action="<?php echo admin_url('admin.php'); ?>" method="post" class="total-stock-value__filter">
			<input type="hidden" name="action" value="total-stock-value-filter">
			<?php wp_referer_field(); ?>

			<label class="total-stock-value__filter-label" for="product_cat_filter">
				<?php _e('Category filter', 'f4-total-stock-value-for-woocommerce'); ?>:
			</label>

			<div class="total-stock-value__filter-fields">
				<select class="total-stock-value__filter-select" name="product_cat[]" id="product_cat_filter" size="1" multiple="multiple">
					<?php
						ob_start();

						$dropdown_args = [
							'show_option_none' => '',
							'show_count' => 0,
							'value_field' => 'term_id'
						];

						// Set default language if "all" is selected
						if($default_lang = Helpers::maybe_get_default_language()) {
							$dropdown_args['lang'] = $default_lang;
						}

						wc_product_dropdown_categories($dropdown_args);

						$select = ob_get_clean();
						$select = preg_replace('/<select[^>]*>/', '', $select);
						$select = preg_replace('/<\/select>/', '', $select);
						$select = preg_replace('/(value="(' . implode('|', $current_filters['categories']) . ')")/', '$1 selected', $select);

						echo $select;
					?>
				</select>

				<input
					type="submit"
					class="total-stock-value__filter-button button action"
					value="<?php _e('Apply filter', 'f4-total-stock-value-for-woocommerce'); ?>"
				/>
			</div>

			<script>
				jQuery(function($) {
					let $select = $('.total-stock-value__filter-select');

					$select.selectWoo({
						placeholder: '<?php _e('All categories', 'f4-total-stock-value-for-woocommerce'); ?>',
						allowClear: true
					});

					$(window).on('scroll', function() {
						let $header = $('.total-stock-value__header');

						if($(window).scrollTop() > 20) {
							$header.addClass('total-stock-value__header--scrolled');
						} else {
							$header.removeClass('total-stock-value__header--scrolled');
						}
					});

					$(window).trigger('scroll');
				});
			</script>
		</form>

		<div class="total-stock-value__items">
			<?php foreach($statistics as $key => $statistic): ?>
				<div class="total-stock-value__item total-stock-value__item--<?php echo $key; ?>" style="color:<?php echo $statistic['color']; ?>;">
					<div class="total-stock-value__label">
						<?php echo $statistic['label']; ?>
					</div>

					<div class="total-stock-value__value">
						<?php echo $statistic['value']; ?>

						<?php if(!empty($statistic['info'])): ?>
							<div class="total-stock-value__info">
								<?php echo $statistic['info']; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="total-stock-value__tax-hint">
			<i>
				<?php echo str_replace('%taxhint%', Analytics::get_tax_hint(), __('All prices %taxhint%', 'f4-total-stock-value-for-woocommerce')); ?>
			</i>
		</div>
	</div>
</div>

