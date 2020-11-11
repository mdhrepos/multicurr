<?php
/**
 * Integration.
 * Plugin Name: Name Your Price
 * Plugin URI: https://woocommerce.com/products/name-your-price/
 *
 * @since 1.11.0
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use WOOMC\API;
use WOOMC\App;

/**
 * Class Integration\WCNameYourPrice
 */
class WCNameYourPrice extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! Env::in_wp_admin() ) {

			$raw_price_tags = array(
				'woocommerce_raw_suggested_price',
				'woocommerce_raw_minimum_price',
				'woocommerce_raw_maximum_price',
			);

			/**
			 * Filter tags renamed in NYP 3+.
			 * Method is_nyp_gte exists in NYP 3+.
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			if (
				is_callable( array( '\WC_Name_Your_Price_Compatibility', 'is_nyp_gte' ) )
				&& \WC_Name_Your_Price_Compatibility::is_nyp_gte( '3.0' )
			) {
				$raw_price_tags = array(
					'wc_nyp_raw_suggested_price',
					'wc_nyp_raw_minimum_price',
					'wc_nyp_raw_maximum_price',
				);
			}

			foreach ( $raw_price_tags as $tag ) {
				\add_filter(
					$tag,
					array( $this, 'filter__nyp_prices' ),
					App::HOOK_PRIORITY_EARLY,
					3
				);
			}

			\add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_initial_currency' ) );

			\add_filter(
				'woocommerce_get_cart_item_from_session',
				array( $this, 'filter__woocommerce_get_cart_item_from_session' ),
				App::HOOK_PRIORITY_LATE,
				2
			);
		}
	}

	/**
	 * Store the initial currency when item is added.
	 *
	 * @since 1.11.0
	 *
	 * @param array $cart_item_data The Cart Item data.
	 *
	 * @return array
	 */
	public function add_initial_currency( $cart_item_data ) {

		if ( isset( $cart_item_data['nyp'] ) ) {
			$cart_item_data['nyp_currency'] = \get_woocommerce_currency();
			$cart_item_data['nyp_original'] = $cart_item_data['nyp'];
		}

		return $cart_item_data;
	}

	/**
	 * Filter Name Your Price Cart prices.
	 *
	 * @since    1.11.0
	 *
	 * @param array $session_data The Session data.
	 * @param array $values       The values.
	 *
	 * @return array
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_get_cart_item_from_session( $session_data, $values ) {

		// Preserve original currency.
		if ( isset( $values['nyp_currency'] ) ) {
			$session_data['nyp_currency'] = $values['nyp_currency'];
		}

		// Preserve original entered value.
		if ( isset( $values['nyp_original'] ) ) {
			$session_data['nyp_original'] = $values['nyp_original'];
		}

		$current_currency   = \get_woocommerce_currency();
		$the_store_currency = API::default_currency();

		/**
		 * Special processing for Name Your Price:
		 * If the amount entered was not in the store default currency, convert it back to the default.
		 *
		 * @since 2.5.3 Use raw conversion (was losing cents because of rounding).
		 * @since 2.5.3 Refactor to support changing currency when NYP is in the cart.
		 */
		if (
			isset( $session_data['nyp'] )
			&& isset( $session_data['nyp_original'] )
			&& isset( $session_data['nyp_currency'] )
		) {
			/**
			 * Product is in the 'data'.
			 *
			 * @var \WC_Product $product
			 */
			$product =& $session_data['data'];

			$amount_entered_by_the_client   = $session_data['nyp_original'];
			$currency_of_the_entered_amount = $session_data['nyp_currency'];

			$price_in_store_currency = $this->price_controller->convert_raw( $amount_entered_by_the_client, $product, $the_store_currency, $currency_of_the_entered_amount );

			// Reset to price in store currency and allow MC to convert later.
			$product->set_price( $price_in_store_currency );
			$product->set_regular_price( $price_in_store_currency );
			$product->set_sale_price( $price_in_store_currency );

			// Subscription-specific price and variable billing period.
			if ( $product->is_type( array( 'subscription', 'subscription_variation' ) ) ) {
				$product->update_meta_data( '_subscription_price', $price_in_store_currency );
			}

			$price_in_current_currency = $this->price_controller->convert_raw( $amount_entered_by_the_client, $product, $current_currency, $currency_of_the_entered_amount );

			$session_data['nyp'] = $price_in_current_currency;
		}

		return $session_data;
	}

	/**
	 * Convert NYP prices.
	 *
	 * @since 2.0.0
	 * @since 2.5.3 - NYP3 passes the product object as 3rd parameter.
	 *
	 * @param string|int|float  $value      The price.
	 * @param int               $product_id Product ID.
	 * @param \WC_Product|false $product    The product object.
	 *
	 * @return float|int|string
	 */
	public function filter__nyp_prices( $value, $product_id, $product = false ) {

		if ( ! $product instanceof \WC_Product ) {
			$product = \wc_get_product( $product_id );
		}

		return $this->price_controller->convert( $value, $product );
	}
}
