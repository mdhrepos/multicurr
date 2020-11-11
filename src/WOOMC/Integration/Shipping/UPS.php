<?php
/**
 * UPS.php
 * Support the WooCommerce Shipping extension.
 *
 * @since   1.9.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 * @package WOOMC\Integration\Shipping
 */

namespace WOOMC\Integration\Shipping;

/**
 * Data and method specific to this shipping method.
 */
class UPS extends AbstractController {

	/**
	 * Shipping method ID.
	 *
	 * @var string
	 */
	const METHOD_ID = 'ups';

	/**
	 * Additional hooks, besides those set in the parent class.
	 *
	 * @since 1.17.0
	 */
	public function setup_hooks() {

		parent::setup_hooks();

		/**
		 * // Allow 3rd parties to skip the check against the store currency.
		 * // This check is irrelevant in multi-currency scenarios
		 *
		 * @see   \WC_Shipping_UPS::calculate_shipping
		 * @since 1.17.0
		 */
		add_filter( 'woocommerce_shipping_ups_check_store_currency', '__return_false' );

		add_filter( 'woocommerce_shipping_ups_rate', array( $this, 'filter__woocommerce_shipping_ups_rate' ), 10, 4 );
	}

	/**
	 * Ignore warning about SimpleXMLElement missing in composer.
	 *
	 * @noinspection PhpComposerExtensionStubsInspection
	 */

	/**
	 * Handle the case when the store currency and the currency of the UPS account differ.
	 * The effect is visible when WOOMC does not do its regular conversion.
	 * For example:
	 * - the UPS account is in USD
	 * - the store currency is MXP
	 * - the selected currency is also MXP
	 * In that case, we would see the USD value instead of MXP.
	 * This filter fixes the issue.
	 *
	 * From the UPS code {@see \WC_Shipping_UPS::calculate_shipping}:
	 * // Allow 3rd parties to process the rates returned by UPS. This will
	 * // allow to convert them to the active currency. The original currency
	 * // from the rates, the XML and the shipping method instance are passed
	 * // as well, so that 3rd parties can fetch any additional information
	 * // they might require
	 * // $rates[ $rate_id ] = apply_filters( 'woocommerce_shipping_ups_rate', array(
	 * //    'id'    => $rate_id,
	 * //    'label' => $rate_name,
	 * //    'cost'  => $rate_cost,
	 * //    'sort'  => $sort,
	 * // ), $currency, $xml, $this );
	 *
	 * @since        1.17.3
	 *
	 * @param array             $rate         The rate array.
	 * @param string            $currency     The currency of the rate (currency of the UPS account).
	 * @param \SimpleXMLElement $ups_xml_data The XML with UPS response.
	 * @param \WC_Shipping_UPS  $ups_instance The UPS class instance.
	 *
	 * @return array
	 */
	public function filter__woocommerce_shipping_ups_rate(
		array $rate,
		$currency,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$ups_xml_data,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$ups_instance
	) {

		// Get the actual store currency, unfiltered.
		$store_currency = \get_option( 'woocommerce_currency' );

		// If the rate is returned not in the store currency, covert it.
		if ( $store_currency !== $currency && ! empty( $rate['cost'] ) ) {
			$rate['cost'] = $this->price_controller->convert_raw( $rate['cost'], null, $store_currency, $currency );
		}

		return $rate;
	}

}
