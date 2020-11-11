<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Gravity Forms Product Add-Ons
 * Plugin URI: http://woothemes.com/products/gravity-forms-add-ons/
 *
 * @since 2.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use WOOMC\App;

/**
 * Class WCGFPA
 *
 * @package WOOMC\Integration
 */
class WCGFPA extends AbstractIntegration {

	/**
	 * Unfiltered form field choices.
	 *
	 * @var array
	 */
	protected $original_form_choices = array();

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function setup_hooks() {

		if ( Env::in_wp_admin() ) {
			return;
		}

		// Apply the same filter to GF as we do for the WC currency.
		\add_filter( 'gform_currency', 'get_woocommerce_currency' );

		// Change the format TODO
		// https://docs.gravityforms.com/gform_currencies/

		// Disable the conversion in certain circumstances.
		\add_filter(
			'woocommerce_multicurrency_pre_product_get_price',
			array( $this, 'filter__woocommerce_multicurrency_pre_product_get_price' ),
			App::HOOK_PRIORITY_EARLY,
			4
		);

		\add_filter(
			'gform_get_field_value',
			array( $this, 'filter__gform_get_field_value' ),
			App::HOOK_PRIORITY_LATE,
			3
		);

		\add_filter(
			'gform_form_post_get_meta',
			array( $this, 'filter__gform_form_post_get_meta' ),
			App::HOOK_PRIORITY_EARLY,
			1
		);

		// Fix cart total.
		\add_filter(
			'woocommerce_gforms_get_cart_item_total',
			array( $this, 'filter__woocommerce_gforms_get_cart_item_total' ),
			App::HOOK_PRIORITY_LATE,
			2
		);

		// Format GF currencies.
		\add_filter(
			'gform_currencies',
			array( $this, 'filter__gform_currencies' ),
			App::HOOK_PRIORITY_LATE,
			1
		);
	}

	/**
	 * Filter gform_form_post_get_meta.
	 *
	 * @param array $form GF Form.
	 *
	 * @return array
	 */
	public function filter__gform_form_post_get_meta( $form ) {

		if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {

			if ( ! isset( $this->original_form_choices[ $form['id'] ] ) ) {
				$this->original_form_choices[ $form['id'] ] = array();
			}

			/**
			 * Field.
			 *
			 * @var \GF_Field $field
			 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
			 */
			foreach ( $form['fields'] as &$field ) {
				if ( isset( $field->choices ) && is_array( $field->choices ) ) {
					$this->original_form_choices[ $form['id'] ][ $field->id ] = $field->choices;
					foreach ( $field->choices as &$choice ) {
						$price           = \rgar( $choice, 'price', '' );
						$choice['price'] = $this->convert_gf_money( $price );
					}
				}
			}
		}

		return $form;
	}

	/**
	 * Convert GF Money ("$1.00 CAD") to the selected currency.
	 *
	 * @param string $gf_money The GF money formatted price.
	 *
	 * @return string
	 */
	protected function convert_gf_money( $gf_money ) {
		$price = \GFCommon::to_number( $gf_money, $this->get_gf_currency() );
		$price = $this->price_controller->convert( $price );

		return \GFCommon::to_money( $price, \get_woocommerce_currency() );

	}

	/**
	 * Get unfiltered GF currency from the Options table.
	 *
	 * @return string
	 */
	protected function get_gf_currency() {
		return \get_option( 'rg_gforms_currency', 'USD' );
	}


	/**
	 * Is the product "mine"?
	 *
	 * @param \WC_Product $product The Product object.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return $product->get_meta( '_gravity_form_data' );
	}

	/**
	 * Short-circuit the price conversion in some specific cases.
	 *
	 * @param false|string|int|float $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float       $value      The price.
	 * @param \WC_Product|null       $product    The product object.
	 * @param string                 $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' ) {

		if ( false !== $pre_value ) {
			// A previous filter already set the `$pre_value`. We do not disturb.
			return $pre_value;
		}

		if ( ! $this->is_my_product( $product ) ) {
			// Not my business.
			return $pre_value;
		}

		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Cart_Totals', 'get_items_from_cart' ),
				array( 'WC_Discounts', 'set_items_from_cart' ),
				array( 'WC_Cart', 'get_product_price' ),
				array( 'WC_Cart', 'get_product_subtotal' ),
			)
		)
		) {
			return $value;
		}

		// Default: we do not interfere. Let the calling method continue.
		return $pre_value;
	}

	/**
	 * Fix total calculation in the Cart.
	 * This is the Woo's line total.
	 *
	 * @param float $total     Total to fix.
	 * @param array $cart_item Cart item.
	 *
	 * @return float
	 */
	public function filter__woocommerce_gforms_get_cart_item_total( $total, $cart_item ) {

		$product = \wc_get_product( $cart_item['data'] );

		if ( ! $product ) {
			return $total;
		}

		if ( $product instanceof \WC_Product_Variation && isset( $cart_item['_gravity_form_lead'] ) ) {
			// For variations, recalculate the total from field choices and do not convert.
			$total = $this->recalculate_gf_field_total( $total, $cart_item['_gravity_form_lead'], false );
		} else {
			// For regular products, trick the gravityforms-product-addons-cart.php.
			// Works as they would call get_price( 'view' ) instead of 'edit'.
			$product_price_raw       = (float) $product->get_price( 'edit' );
			$product_price_converted = (float) $product->get_price( 'view' );

			$total = $total - $product_price_raw + $product_price_converted;
		}

		return $total;
	}

	/**
	 * Filter gform_get_field_value.
	 * This is the total displayed by Gravity (GF_Field_Total), not the Woo's line total.
	 *
	 * @param mixed     $value Field value.
	 * @param array     $lead  GF Lead.
	 * @param \GF_Field $field GF Field.
	 *
	 * @return mixed|float|string
	 */
	public function filter__gform_get_field_value( $value, $lead, $field ) {

		if ( ! $field instanceof \GF_Field ) {
			return $value;
		}

		if ( $field instanceof \GF_Field_Total ) {
			return $this->recalculate_gf_field_total( $value, $lead, true );
		}

		/**
		 * Restore drop-down, checkbox and radio options to their values in the original currency.
		 */
		if ( ! empty( $field->choices ) ) {

			if ( is_string( $value ) ) {
				$value = $this->restore_original_choice_value( $value, $field );
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $sub_id => $sub_value ) {
					$value[ $sub_id ] = $this->restore_original_choice_value( $sub_value, $field );
				}
			}

			return $value;
		}

		return $value;
	}

	/**
	 * Recalculate total using preserved form choices.
	 * This is the total displayed by Gravity (GF_Field_Total), not the Woo's line total.
	 *
	 * @param float $total   The total currently in the cart.
	 * @param array $lead    The GF Lead.
	 * @param bool  $convert If true, convert each choice's value before adding to total. Fixes rounding.
	 *
	 * @return float
	 */
	protected function recalculate_gf_field_total( $total, $lead, $convert = false ) {

		$form_id = $lead['form_id'];
		if ( ! isset( $this->original_form_choices[ $form_id ] ) ) {
			return $total;
		}

		$form_choices = $this->original_form_choices[ $form_id ];

		$total = 0.0;
		foreach ( $lead as $id => $content ) {
			if ( $this->is_option_value_string( $content ) ) {

				list( $option_value, ) = explode( '|', $content );

				list( $field_id, ) = explode( '.', $id );

				foreach ( $form_choices[ $field_id ] as $choice ) {
					if ( \rgar( $choice, 'value', '' ) === $option_value ) {
						$price = \rgar( $choice, 'price', '' );
						$value = (float) \GFCommon::to_number( $price, $this->get_gf_currency() );

						/**
						 * Convert before adding to the total. Otherwise, rounding is done on the total,
						 * which causes inconsistent display.
						 *
						 * @since 2.4.1
						 */
						if ( $convert ) {
							$value = $this->price_controller->convert( $value );
						}

						/**
						 * Filter choice value. Added by Gravity Wiz.
						 *
						 * @since 2.5.2
						 */
						$total += (float) apply_filters( 'woocommerce_multicurrency_choice_value', $value, $choice, $field_id, $lead );
						break;
					}
				}
			}
		}

		return $total;

	}

	/**
	 * Returns true if the string has the form of "Option1|100".
	 *
	 * @param string $sz The string to check.
	 *
	 * @return bool
	 */
	protected function is_option_value_string( $sz ) {
		return is_string( $sz ) && false !== strpos( $sz, '|' );
	}

	/**
	 * For the cart values like "Option1|100", restore the original value of the "100".
	 * "Original" means in the currency of GForms and not in the currency of the cart.
	 * Need it when currency is switched while there are products in the cart already.
	 *
	 * @param string    $value Field value.
	 * @param \GF_Field $field GF Field.
	 *
	 * @return string
	 */
	protected function restore_original_choice_value( $value, $field ) {

		if ( isset( $field->choices ) && $this->is_option_value_string( $value ) ) {
			list( $choice_value, ) = explode( '|', $value );
			foreach ( $field->choices as $choice ) {
				if ( $choice_value === $choice['value'] ) {
					$price = \rgempty( 'price', $choice ) ? 0 : \GFCommon::to_number( \rgar( $choice, 'price' ) );

					$value = implode( '|', array( $choice_value, $price ) );
					break;
				}
			}
		}

		return $value;
	}

	/**
	 * Set GF currency formatting to match ours.
	 *
	 * @since 2.5.4
	 *
	 * @param string[][] $currencies Currency formats.
	 *
	 * @return string[][]
	 * @link  https://docs.gravityforms.com/gform_currencies/
	 */
	public function filter__gform_currencies( $currencies ) {
		$active_currency = \get_woocommerce_currency();
		$price_format    = \get_woocommerce_price_format();
		$currency_symbol = \get_woocommerce_currency_symbol( $active_currency );

		$parsed_format = self::parse_price_format( $price_format, $currency_symbol );

		$currencies[ $active_currency ]['symbol_left']    = $parsed_format['symbol_left'];
		$currencies[ $active_currency ]['symbol_right']   = $parsed_format['symbol_right'];
		$currencies[ $active_currency ]['symbol_padding'] = $parsed_format['symbol_padding'];

		$currencies[ $active_currency ]['thousand_separator'] = \wc_get_price_thousand_separator();
		$currencies[ $active_currency ]['decimal_separator']  = \wc_get_price_decimal_separator();
		$currencies[ $active_currency ]['decimals']           = \wc_get_price_decimals();

		return $currencies;
	}

	/**
	 * Parse Woo price format into GF.
	 *
	 * @param string $price_format    The price format.
	 * @param string $currency_symbol The currency symbol.
	 *
	 * @return string[]
	 */
	public static function parse_price_format( $price_format, $currency_symbol ) {

		// Symbol at the left.
		$re = '/(.*)%1\$s(.*)%2\$s(.*)/';
		if ( preg_match( $re, $price_format, $matches ) ) {
			$parsed['symbol_left']    = $matches[1] . $currency_symbol;
			$parsed['symbol_padding'] = $matches[2];
			$parsed['symbol_right']   = $matches[3];

			return $parsed;
		}

		// Symbol at the right.
		$re = '/(.*)%2\$s(.*)%1\$s(.*)/';
		if ( preg_match( $re, $price_format, $matches ) ) {
			$parsed['symbol_right']   = $currency_symbol . $matches[3];
			$parsed['symbol_padding'] = $matches[2];
			$parsed['symbol_left']    = $matches[1];

			return $parsed;
		}

		// Default if price format is invalid.
		$parsed = array(
			'symbol_left'    => $currency_symbol,
			'symbol_right'   => '',
			'symbol_padding' => '',
		);

		return $parsed;
	}
}
