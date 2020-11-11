<?php
/**
 * Reports currency selector.
 *
 * @since 1.7.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Reports;

use WOOMC\DAO\Factory;

/**
 * Class CurrencySelector
 *
 * @package WOOMC\Reports
 */
class CurrencySelector {

	/**
	 * The currency selector dropdown.
	 *
	 * @param string $selected_currency The active currency.
	 */
	public static function render( $selected_currency ) {
		$currencies = Factory::getDao()->getEnabledCurrencies();
		?>
		<label for="<?php echo esc_attr( Controller::TAG_CURRENCY_SELECTOR ); ?>">
			<?php esc_html_e( 'Currency', 'woocommerce' ); ?>:
		</label>
		<select id="<?php echo esc_attr( Controller::TAG_CURRENCY_SELECTOR ); ?>">
			<?php foreach ( $currencies as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $code, $selected_currency ); ?>><?php echo esc_html( $code ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<script>
			jQuery(function ($) {
				$("#<?php echo esc_js( Controller::TAG_CURRENCY_SELECTOR ); ?>").on("change", function () {
					var name = "<?php echo esc_js( Controller::COOKIE_REPORTS_CURRENCY ); ?>";
					document.cookie = name + '=' + this.value;
					window.location.reload();
				});
			});
		</script>

		<?php
	}
}
