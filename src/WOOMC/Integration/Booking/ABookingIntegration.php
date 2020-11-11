<?php
/**
 * Booking integration abstract.
 *
 * @since 1.13.0
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Booking;

use WOOMC\App;
use WOOMC\Integration\AbstractIntegration;
use WOOMC\Log;
use WOOMC\Product;

/**
 * Class ABookingIntegration
 *
 * @package WOOMC\Integration\Booking
 */
abstract class ABookingIntegration extends AbstractIntegration {

	/**
	 * Bookings version before 1.15.0 is "legacy".
	 *
	 * @since 1.17.1
	 *
	 * @var bool
	 */
	protected $is_legacy_booking = false;

	/**
	 * The PHP class of the product I am working with.
	 *
	 * @return string
	 */
	abstract protected function my_product_class();

	/**
	 * Is the product "mine" (by its PHP class name)?
	 *
	 * @param \WC_Product_Booking|\WC_Product_Accommodation_Booking|\WC_Product $product The Product object.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return $this->my_product_class() === Product\Info::classname( $product );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( ! defined( 'WC_BOOKINGS_VERSION' ) ) {
			Log::error( new \Exception( 'WC_BOOKINGS_VERSION not defined. Cannot continue.' ) );

			return;
		}

		if ( version_compare( WC_BOOKINGS_VERSION, '1.15.0', '<' ) ) {
			$this->is_legacy_booking = true;
			Log::info( new \Exception( 'An older version of Bookings is active. Please upgrade.' ) );
		}

		/**
		 * Special filters for Booking resources.
		 *
		 * @see \WC_Product_Booking::get_resource_block_costs
		 * @see \WC_Product_Booking::get_resource_base_costs
		 */
		static $filter_tags_booking_resources = array(
			'woocommerce_product_get_resource_block_costs',
			'woocommerce_product_get_resource_base_costs',
		);

		foreach ( $filter_tags_booking_resources as $tag ) {
			add_filter(
				$tag,
				array(
					$this,
					'filter__booking_resources',
				),
				App::HOOK_PRIORITY_EARLY,
				2
			);
		}

		/**
		 * Filter {@see \WC_Product_Booking::get_pricing}.
		 */
		add_filter(
			'woocommerce_product_get_pricing',
			array(
				$this,
				'filter__woocommerce_product_get_pricing',
			),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		// Disable the conversion in certain circumstances.
		add_filter(
			'woocommerce_multicurrency_pre_product_get_price',
			array(
				$this,
				'filter__woocommerce_multicurrency_pre_product_get_price',
			),
			App::HOOK_PRIORITY_EARLY,
			4
		);

		1 && add_action(
			'woocommerce_before_calculate_totals',
			array(
				$this,
				'action__woocommerce_before_calculate_totals',
			),
			App::HOOK_PRIORITY_EARLY
		);

		/**
		 * This filter is not required since Booking 1.15.2.
		 *
		 * @see        calculate_addon_costs().
		 * @deprecated 1.17.3
		 */
		if ( $this->is_legacy_booking ) {
			add_filter(
				'woocommerce_product_addon_cart_item_data',
				array(
					$this,
					'filter__woocommerce_product_addon_cart_item_data',
				),
				App::HOOK_PRIORITY_EARLY,
				3
			);
		}

		/**
		 * Unused.
		 *
		 * <code>
		 * add_filter(
		 * 'woocommerce_bookings_calculated_booking_cost',
		 * function ( $booking_cost, $product, $data ) {
		 * $booking_cost = $this->price_controller->convert( $booking_cost, $product );
		 *
		 * return $booking_cost;
		 * },
		 * 10,
		 * 3
		 * );
		 * </code>
		 */

	}

	/**
	 * Short-circuit the price conversion for Bookings in some specific cases.
	 *
	 * @param false|string|int|float   $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float         $value      The price.
	 * @param \WC_Product_Booking|null $product    The product object.
	 * @param string                   $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal filter.
	 */
	abstract public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' );


	/**
	 * Convert Booking addon prices in the cart.
	 * This filter is not required since Booking 1.15.2.
	 *
	 * @see        calculate_addon_costs().
	 * @since      1.14.1
	 *
	 * @param mixed $addon          Unused.
	 * @param int   $product_id     The Product ID.
	 *
	 * @param array $cart_item_data Array of cart item data.
	 *
	 * @return array
	 *
	 * @deprecated 1.17.3
	 *
	 * @internal   filter.
	 */
	public function filter__woocommerce_product_addon_cart_item_data(
		$cart_item_data,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$addon,
		$product_id
	) {
		/**
		 * Get the product by ID.
		 *
		 * @var \WC_Product_Booking $product
		 */
		$product = \wc_get_product( $product_id );

		if ( $this->is_my_product( $product ) ) {
			foreach ( $cart_item_data as $id => $data ) {
				// Only if it's a value and not percent.
				if ( in_array( $data['price_type'], array( 'flat_fee', 'quantity_based' ), true ) ) {
					$cart_item_data[ $id ]['price'] = $this->price_controller->convert( $data['price'], $product );
				}
			}
		}

		return $cart_item_data;
	}

	/**
	 * Convert Booking resource costs.
	 *
	 * @param array               $resources The `id=>value` array of all resources.
	 * @param \WC_Product_Booking $product   The Product object.
	 *
	 * @return array
	 * @internal     filter.
	 *
	 * @noinspection PhpUndefinedClassInspection If not using Booking.
	 */
	public function filter__booking_resources( $resources, $product = null ) {

		if ( $this->is_my_product( $product ) ) {
			foreach ( $resources as $id => $value ) {
				$resources[ $id ] = $this->price_controller->convert( $value, $product );
			}
		}

		return $resources;
	}

	/**
	 * Convert additional booking prices (eg, costs per person).
	 *
	 * @since        1.13.0
	 *
	 * @param array               $pricing The pricing_rules.
	 * @param \WC_Product_Booking $product The product.
	 *
	 * @return mixed
	 *
	 * @internal     filter.
	 *
	 * @noinspection PhpUndefinedClassInspection If not using Booking.
	 */
	public function filter__woocommerce_product_get_pricing( $pricing, $product ) {

		if ( count( $pricing ) && $this->is_my_product( $product ) ) {
			foreach ( $pricing as &$costs ) {
				if ( ! empty( $costs['cost'] ) ) {
					$costs['cost'] = $this->price_controller->convert( $costs['cost'], $product );
				}
				if ( ! empty( $costs['base_cost'] ) ) {
					$costs['base_cost'] = $this->price_controller->convert( $costs['base_cost'], $product );
				}
			}
		}

		return $pricing;
	}

	/**
	 * Recalculate booking cost for each item in the cart.
	 * Without it, when the currency changes, costs in the cart stay the same.
	 *
	 * @since 1.3.0
	 * @since 1.17.1 Support Bookings 1.15.0+
	 *
	 * @param \WC_Cart $cart The cart.
	 *
	 * @return \WC_Cart
	 */
	public function action__woocommerce_before_calculate_totals( $cart ) {

		foreach ( $cart->cart_contents as $key => &$cart_item ) {

			if ( ! isset( $cart_item['booking'] ) ) {
				continue;
			}

			/**
			 * Check if the product in the Cart is a Booking.
			 *
			 * @var \WC_Product_Booking $product
			 */
			$product = $cart_item['data'];
			if ( ! $this->is_my_product( $product ) ) {
				continue;
			}

			$booking_data = $cart_item['booking'];

			// Emulate $_POST from the Booking Form at the add-to-cart.
			$posted = array(
				'wc_bookings_field_start_date_year'  => $booking_data['_year'],
				'wc_bookings_field_start_date_month' => $booking_data['_month'],
				'wc_bookings_field_start_date_day'   => $booking_data['_day'],
				'add-to-cart'                        => $product->get_id(),
			);

			if ( isset( $booking_data['_duration'] ) ) {
				$posted['wc_bookings_field_duration'] = $booking_data['_duration'];
			}

			if ( isset( $booking_data['_time'] ) ) {
				$date = isset( $booking_data['_date'] ) ? $booking_data['_date'] :
					sprintf(
						'%1$4d-%2$02d-%3$02d',
						$booking_data['_year'],
						$booking_data['_month'],
						$booking_data['_day']
					);

				$timestamp = strtotime( $date . ' ' . $booking_data['_time'] );

				// PHPCS insists on `gmdate`. Cannot do it - the snippet is from `Bookings`.
				$fdate = 'date';

				$posted['wc_bookings_field_start_date_time'] = $fdate( 'c', $timestamp );
			}

			if ( $product->has_persons() && isset( $booking_data['_persons'] ) ) {
				if ( $product->has_person_types() ) {
					foreach ( $booking_data['_persons'] as $person_id => $value ) {
						$posted[ 'wc_bookings_field_persons_' . $person_id ] = $value;
					}
				} else {
					$posted['wc_bookings_field_persons'] = $booking_data['_persons'][0];
				}
			}

			if ( isset( $booking_data['_resource_id'] ) ) {
				$posted['wc_bookings_field_resource'] = $booking_data['_resource_id'];
			}

			/**
			 * TODO
			 * wc_bookings_field_start_date_yearmonth
			 * wc_bookings_field_start_date_local_timezone
			 */

			/**
			 * These are child classes that do not attempt to validate "is_bookable".
			 * Otherwise, we cannot calculate the costs when there are limits in bookable spaces.
			 */
			$owc_product_booking = new OWCProductBooking( $product );
			$owc_booking_form    = new OWCBookingForm( $product ); // legacy.

			/**
			 * The `cost` is used below, within the `if` statement.
			 *
			 * @var string|\WP_Error $cost
			 */

			/**
			 * Handle bookings with "Product Add-ons".
			 *
			 * @since   1.15.0
			 *
			 * @example of the POST array with various add-ons.
			 * $_POST = {array} [7]
			 * add-to-cart = "595"
			 * addon-595-checkbox-per-person-1 = {array} [3]
			 * 0 = "flat"
			 * 1 = "quantity"
			 * 2 = "percent"
			 * addon-595-quantity-based-add-on-per-person-0 = "2"
			 * wc_bookings_field_persons = "2"
			 * wc_bookings_field_start_date_day = "26"
			 * wc_bookings_field_start_date_month = "04"
			 * wc_bookings_field_start_date_year = "2019"
			 */
			if ( ! empty( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
				// Emulate "posted" addons.
				foreach ( $cart_item['addons'] as $addon ) {
					if ( is_numeric( $addon['value'] ) ) {
						/**
						 * This is for the `input type=number` add-on fields.
						 * field_type = input_multiplier
						 * price_type = quantity_based
						 *
						 * @example addon-595-quantity-based-add-on-per-person-0 = "2"
						 */
						$posted[ 'addon-' . $addon['field_name'] ] = $addon['value'];
					} else {
						/**
						 * This is for the multiple choice type (checkbox, dropdown).
						 *
						 * @example
						 * addon-595-checkbox-per-person-1 = {array} [3]
						 * 0 = "flat"
						 * 1 = "quantity"
						 * 2 = "percent"
						 */
						$posted[ 'addon-' . $addon['field_name'] ][] = sanitize_title( $addon['value'] );
					}
				}

				/**
				 * The filter @see \WC_Product_Addons_Cart::add_cart_item_data() uses $_POST and not $posted.
				 * So we temporarily "make" the $_POST.
				 * There is no nonce posted, so we fake the verification.
				 */
				\wp_verify_nonce( '' );
				if ( isset( $_POST ) ) {
					$saved_post = $_POST;
				}
				$_POST = $posted;

				if ( $this->is_legacy_booking ) {
					/**
					 * Legacy.
					 *
					 * @noinspection PhpDeprecationInspection
					 */
					$cost = $owc_booking_form->calculate_booking_cost( $posted );
				} else {
					/** $booking_data1 = \wc_bookings_get_posted_data( $posted, $product ); */
					$cost = \WC_Bookings_Cost_Calculation::calculate_booking_cost( $booking_data, $owc_product_booking );

					if ( ! $this->is_legacy_booking ) {
						/**
						 * Adapt the changes in Bookings 1.15.2.
						 *
						 * @since 1.18.0
						 */
						$addon_costs = $this->calculate_addon_costs( $cart_item, $cost );

						$cost += $addon_costs;
					}
				}

				if ( isset( $saved_post ) ) {
					// Restore the original $_POST.
					$_POST = $saved_post;
				}
			} else {
				// Without addons.
				if ( $this->is_legacy_booking ) {
					/**
					 * Legacy.
					 *
					 * @noinspection PhpDeprecationInspection
					 */
					$cost = $owc_booking_form->calculate_booking_cost( $posted );
				} else {
					/** $booking_data = \wc_bookings_get_posted_data( $posted, $product ); */
					$cost = \WC_Bookings_Cost_Calculation::calculate_booking_cost( $booking_data, $owc_product_booking );
				}
			}

			if ( ! is_wp_error( $cost ) ) {
				$product->set_price( $cost );
			} else {
				/**
				 * If there was an error, let's remove this item from the cart.
				 *
				 * Example: the cart was on the screen too long.
				 * Recalculation returned "Date must be in the future" error.
				 */
				$cart->remove_cart_item( $key );
				Log::error( array( $cost->get_error_message(), __METHOD__, __LINE__ ) );
			}
		}

		return $cart;
	}

	/**
	 * Calculate the cost of product add-ons.
	 *
	 * Adapted from {@see \WC_Bookings_Addons::add_cart_item_data_adjust_booking_cost}
	 *
	 * @see          \WC_Bookings_Addons::add_cart_item_data_adjust_booking_cost
	 * @since        1.18.0
	 *
	 * @param float|int $cost           The cost calculated in the caller.
	 *
	 * @param array     $cart_item_data Extra data for cart item.
	 *
	 * @return float|int
	 * @noinspection DuplicatedCode
	 */
	protected function calculate_addon_costs( $cart_item_data, $cost ) {

		$addon_costs = 0;

		if ( ! empty( $cart_item_data['addons'] ) && ! empty( $cart_item_data['booking'] ) ) {
			$booking_data = $cart_item_data['booking'];

			foreach ( $cart_item_data['addons'] as $addon ) {
				$person_multiplier   = 1;
				$duration_multiplier = 1;

				$addon['price'] = ( ! empty( $addon['price'] ) ) ? $addon['price'] : 0;

				if ( ! empty( $addon['wc_booking_person_qty_multiplier'] ) && ! empty( $booking_data['_persons'] ) && array_sum( $booking_data['_persons'] ) ) {
					$person_multiplier = array_sum( $booking_data['_persons'] );
				}
				if ( ! empty( $addon['wc_booking_block_qty_multiplier'] ) && ! empty( $booking_data['_duration'] ) ) {
					$duration_multiplier = (int) $booking_data['_duration'];
				}

				if ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '3.0', '>=' ) ) {
					$price_type  = $addon['price_type'];
					$addon_price = $addon['price'];

					switch ( $price_type ) {
						case 'percentage_based':
							/**
							 * Notes:
							 * 1. use the $cost and not `_cost` from the $booking_data
							 * because that has the add-ons in it already.
							 * 2. Do not convert currency. It's a percent of the cost.
							 */
							$addon_costs += (float) ( ( $cost * ( $addon_price / 100 ) ) * ( $person_multiplier * $duration_multiplier ) );
							break;
						case 'flat_fee':
							$addon_price = $this->price_controller->convert( $addon_price );

							$addon_costs += (float) $addon_price;

							break;
						default:
							$addon_price = $this->price_controller->convert( $addon_price );

							$addon_costs += (float) ( $addon_price * $person_multiplier * $duration_multiplier );

							break;
					}
				} else {
					$addon_costs += floatval( $addon['price'] ) * $person_multiplier * $duration_multiplier;
				}
			}
		}

		return $addon_costs;
	}

}
