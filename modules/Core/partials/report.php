<?php

use F4\WCTSV\Core\Helpers;

$total_stock_value = Helpers::get_total_stock_value();

?>

<div class="total-sale-value">
	<div class="total-sale-value__item total-sale-value__item--units">
		<div class="total-sale-value__value">
			<?php echo $total_stock_value['count']; ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Units in stock', 'f4-wc-total-stock-value'); ?>
		</div>
	</div>

	<div class="total-sale-value__item total-sale-value__item--value">
		<div class="total-sale-value__value">
			<?php echo wc_price($total_stock_value['regular_value']); ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Total stock value (without sales)', 'f4-wc-total-stock-value'); ?>
		</div>
	</div>

	<div class="total-sale-value__item total-sale-value__item--value-sale">
		<div class="total-sale-value__value">
			<?php echo wc_price($total_stock_value['current_value']); ?>
		</div>
		<div class="total-sale-value__label">
			<?php _e('Total stock value (with sales)', 'f4-wc-total-stock-value'); ?>
		</div>
	</div>
</div>
