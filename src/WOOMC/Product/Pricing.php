<?php
/**
 * Product prices.
 *
 * @since 1.19.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Product;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Product;
use WOOMC\Price;

/**
 * Class Pricing
 *
 * @package WOOMC\Product
 */
class Pricing implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The Price controller instance.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$this->setup_get_props_filters();

		\add_filter(
			'woocommerce_product_get_price',
			array( $this, 'filter__woocommerce_product_get_price' ),
			App::HOOK_PRIORITY_EARLY,
			3
		);

		\add_filter(
			'woocommerce_product_get_regular_price',
			array( $this, 'filter__woocommerce_product_get_regular_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_variation_get_regular_price',
			array( $this, 'filter__woocommerce_product_get_regular_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_get_sale_price',
			array( $this, 'filter__woocommerce_product_get_sale_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_variation_get_sale_price',
			array( $this, 'filter__woocommerce_product_get_sale_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_variation_get_regular_price',
			array( $this, 'filter__woocommerce_product_get_regular_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_variation_prices',
			array(
				$this,
				'filter__woocommerce_variation_prices',
			),
			App::HOOK_PRIORITY_EARLY,
			3
		);
	}

	/**
	 * Return custom price or pass-through to {@see get_price}.
	 *
	 * @param string|int|float                  $value       The price.
	 * @param \WC_Product|\WC_Product_Variation $product     The product object.
	 *
	 * @param bool                              $include_tax Return price with tax?
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_price( $value, $product, $include_tax = false ) {

		if ( ! $value ) {
			// Save time.
			return $value;
		}

		if ( ! is_a( $product, 'WC_Product' ) ) {
			// For example, WC_Product_Booking_Person_Type.
			return $this->get_price( $value, '', $product );
		}

		$product_info = new Product\Info( $product );

		/**
		 * Is the price we found - custom?
		 *
		 * @since 2.6.3
		 */
		$returning_custom_price = false;

		if ( ! $product_info->is_custom_priced() ) {
			// No custom pricing..pass-through.
			$value = $this->get_price( $value, '', $product );
		} else {

			// Should we return the sale price?
			$sp  = $product_info->get_raw_sale_price();
			$csp = $product_info->get_custom_sale_price();
			$crp = $product_info->get_custom_regular_price();
			if ( $csp ) {
				// Custom sale price is set.
				$value                  = $csp;
				$returning_custom_price = true;
			} elseif ( $sp ) {
				// Regular sale price is set. Pass-through works.
				$value = $this->get_price( $value, '', $product );
			} elseif ( $crp ) {
				// Custom sale price is not set but custom regular - yes. Returning custom regular.
				$value                  = $crp;
				$returning_custom_price = true;
			}
		}

		/**
		 * The custom price does not include tax.
		 * If we need to `$include_tax`, let's add it now.
		 * This is needed for {@see \WC_Product_Variable::get_price_html }, for example,
		 * to display the single product price with tax.
		 *
		 * @since 2.6.3
		 */
		if ( $returning_custom_price && $include_tax && $value ) {
			$value = \wc_get_price_including_tax( $product, array( 'qty' => 1, 'price' => $value ) );
		}

		return $value;
	}

	/**
	 * Convert regular price.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The product object.
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_regular_price( $value, $product ) {
		return $this->get_price( $value, '_regular_price', $product );
	}

	/**
	 * Convert sale price.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The product object.
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_sale_price( $value, $product ) {
		return $this->get_price( $value, '_sale_price', $product );
	}

	/**
	 * Generic price conversion.
	 *
	 * @param string|int|float $value      The price.
	 * @param string           $price_type Regular, Sale, etc.
	 * @param \WC_Product      $product    The product object.
	 *
	 * @return string
	 */
	protected function get_price( $value, $price_type, $product ) {

		/**
		 * Short-circuits (no conversion).
		 *
		 * @since 2.0.0 When it's just an 'is_purchasable' check.
		 * @since 2.6.2-rc.1 When exporting products.
		 */
		if ( Env::is_functions_in_backtrace( array(
				array( 'WC_Product', 'is_purchasable' ),
				array( 'WC_Admin_Exporters', 'do_ajax_product_export' ),
				/**
				 * These break Dynamic Pricing.
				 * array( 'WC_Product', 'is_on_sale' ),
				 * array( 'WC_Product_Simple', 'is_on_sale' ),
				 * array( 'WC_Product_Variable', 'is_on_sale' ),
				 */
			)
		) ) {
			return $value;
		}

		/**
		 * Check for the custom product pricing.
		 *
		 * @since 1.19.0
		 * @since 1.19.1 Bail out if price per product is not allowed in Settings.
		 * @since 2.0.0 Check for the custom pricing before short-circuiting.
		 */
		if ( $price_type && Factory::getDao()->isAllowPricePerProduct() ) {
			$custom_price_meta_key = $price_type . '_' . \get_woocommerce_currency();
			$custom_price          = $product->get_meta( $custom_price_meta_key );
			if ( $custom_price ) {
				return $custom_price;
			}
		}

		$pre_value = false;
		/**
		 * Pre-filter to allow short-circuiting.
		 * If a non-false value comes out of the filter, it will be returned.
		 *
		 * @param false|string|int|float $pre_value  Initially passed as "false". May return the actual value.
		 * @param string|int|float       $value      The price.
		 * @param \WC_Product|null       $product    The product object.
		 * @param string                 $price_type Regular, Sale, etc.
		 */
		$pre_value = apply_filters( 'woocommerce_multicurrency_pre_product_get_price', $pre_value, $value, $product, $price_type );
		if ( false !== $pre_value ) {
			return $pre_value;
		}

		$value = $this->price_controller->convert( $value, $product );

		return $value;
	}

	/**
	 * Convert variation prices.
	 *
	 * @since        1.0.0
	 * @since        2.0.0 Moved to Product\Pricing.
	 * @since        2.4.0 Calculate product variation's price using the same methods
	 *                     as with the regular product - to consider custom pricing.
	 *
	 * @param string[][]  $transient_cached_prices_array The `$price_type => $values` array.
	 * @param \WC_Product $product                       The Product object.
	 * @param bool        $for_display                   If true, prices will be adapted for display based on the `woocommerce_tax_display_shop` setting (including or excluding taxes).
	 *
	 * @return string[][]
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_variation_prices(
		$transient_cached_prices_array,
		$product = null,
		$for_display = false
	) {

		/**
		 * Are we asked to return prices with tax?
		 *
		 * @since 2.6.3
		 */
		$include_tax = $for_display && ( 'incl' === \get_option( 'woocommerce_tax_display_shop' ) );

		foreach ( $transient_cached_prices_array as $price_type => $variation_prices ) {
			$method = "filter__woocommerce_product_get_{$price_type}";
			foreach ( $variation_prices as $variation_id => $value ) {
				if ( $value ) {
					$variation = \wc_get_product( $variation_id );
					$price     = $this->$method( $value, $variation, $include_tax );

					$transient_cached_prices_array[ $price_type ][ $variation_id ] = $price;
				}
			}
		}

		return $transient_cached_prices_array;
	}

	/**
	 * Setup filters for get_prop_* methods.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to a function in Pricing.
	 */
	protected function setup_get_props_filters() {

		/**
		 * Simple and Variable product prices.
		 */
		static $filter_tags = array(
			'woocommerce_product_get_price',
			'woocommerce_product_variation_get_price',
			// 'woocommerce_product_get_regular_price', -- Excluded intentionally @since 1.19.0.
			// 'woocommerce_product_get_sale_price', -- Excluded intentionally @since 1.19.0.
			// 'woocommerce_product_variation_get_sale_price', -- Excluded intentionally @since 2.4.0.
			// 'woocommerce_product_variation_get_regular_price', -- Excluded intentionally @since 2.4.0.
		);

		/**
		 * --- MUST COME AFTER THE INTEGRATIONS TO HOOK ALL TAGS ---
		 *
		 * @param string[] $filter_tags
		 */
		$filter_tags = \apply_filters( 'woocommerce_multicurrency_get_props_filters', $filter_tags );
		$filter_tags = array_unique( $filter_tags );

		foreach ( $filter_tags as $tag ) {
			\add_filter( $tag, array( $this, 'filter__woocommerce_product_get_price' ), App::HOOK_PRIORITY_EARLY, 2 );
		}

		/**
		 * --- DO NOT USE ---
		 *
		 * - woocommerce_get_price_excluding_tax
		 * - woocommerce_get_price_including_tax
		 * They usually come after the prices already calculated. Exception: Product Add-ons, see below.
		 *
		 * - raw_woocommerce_price
		 * This is a "strange" filter.
		 * Not sure how it can be used, because it affects every price,
		 * and does not tell, which one.
		 *
		 * - woocommerce_subscriptions_cart_get_price
		 * - woocommerce_variation_prices_price
		 * - woocommerce_variation_prices_regular_price
		 * - woocommerce_variation_prices_sale_price
		 * - woocommerce_variation_prices_sign_up_fee
		 * Covered by other hooks.
		 *
		 * Additional hooks are in {@see WC_Product_Variable_Data_Store_CPT::read_price_data}.
		 * Should not use those because the prices then stored in transients.
		 */
	}
}
