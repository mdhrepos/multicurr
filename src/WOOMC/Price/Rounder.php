<?php
/**
 * Price rounder.
 *
 * @since 1.0.0
 */

namespace WOOMC\Price;

use WOOMC\DAO\Factory;


/**
 * Price\Rounder
 */
class Rounder {

	/**
	 * Default "Round To" value.
	 *
	 * @var float
	 */
	const DEFAULT_ROUND_TO = 0.01;

	/**
	 * Default "Price charm" value.
	 *
	 * @var float
	 */
	const DEFAULT_PRICE_CHARM = 0.0;

	/**
	 * Default "Fee percent" value.
	 *
	 * @var float
	 */
	const DEFAULT_FEE_PERCENT = 0.0;

	/**
	 * The settings array.
	 *
	 * @var float[]
	 */
	protected $settings = array(
		'round_to'    => self::DEFAULT_ROUND_TO,
		'price_charm' => self::DEFAULT_PRICE_CHARM,
		'fee_percent' => self::DEFAULT_FEE_PERCENT,
	);

	/**
	 * Price\Rounder constructor.
	 */
	public function __construct() {

		$dao = Factory::getDao();
		$this->setRoundTo( $dao->getRoundTo() );
		$this->setPriceCharm( $dao->getPriceCharm() );
		$this->setFeePercent( $dao->getFeePercent() );
	}

	/**
	 * Discard invalid parameters and values lower than 1 cent (that also discards negative values).
	 *
	 * @param mixed $value    The value.
	 * @param float $fallback The fallback if not sanitize-able.
	 *
	 * @return float
	 */
	protected function sanitize_setting_value( $value, $fallback = 0.0 ) {
		if ( ! is_numeric( $value ) || $value < 0.01 ) {
			$value = $fallback;
		}

		return (float) $value;

	}

	/**
	 * Getter for "Round to".
	 *
	 * @return float
	 */
	public function getRoundTo() {
		return $this->settings['round_to'];
	}

	/**
	 * Setter for "Round to".
	 *
	 * @param float|int $value The value.
	 */
	public function setRoundTo( $value ) {
		$this->settings['round_to'] = $this->sanitize_setting_value( $value, 0.01 );
	}

	/**
	 * Getter for "Price charm".
	 *
	 * @return float
	 */
	public function getPriceCharm() {
		return $this->settings['price_charm'];
	}

	/**
	 * Setter for "Price charm".
	 *
	 * @param float|int $value The value.
	 */
	public function setPriceCharm( $value ) {
		$this->settings['price_charm'] = $this->sanitize_setting_value( $value );
	}

	/**
	 * Getter for "Fee percent".
	 *
	 * @return float
	 */
	public function getFeePercent() {
		return $this->settings['fee_percent'];
	}

	/**
	 * Setter for "Fee percent".
	 *
	 * @param float|int $value The value.
	 */
	public function setFeePercent( $value ) {
		$this->settings['fee_percent'] = $this->sanitize_setting_value( $value );
	}

	/**
	 * Round up a float value.
	 *
	 * @param float  $value    The value to round.
	 * @param string $currency The currency code.
	 *
	 * @return float
	 */
	public function up( $value, $currency = '' ) {
		if ( ! is_numeric( $value ) ) {
			$value = 0.0;
		}

		/**
		 * Do not touch negative values.
		 *
		 * @since 1.15.0 Do not touch zero values either.
		 */
		if ( $value <= 0 ) {
			return $value;
		}

		$value = (float) $value;

		$this->settings = \apply_filters( 'woocommerce_multicurrency_rounder_settings', $this->settings, $currency );

		$value *= ( 1.0 + $this->settings['fee_percent'] / 100.0 );

		if ( $this->settings['round_to'] > 0.01 ) {
			$value = ceil( $value / $this->settings['round_to'] ) * $this->settings['round_to'];
		}

		$value -= $this->settings['price_charm'];

		return round( $value, 2 );
	}

	/**
	 * Reverse to the {@see up()}.
	 *
	 * @param float  $value    The value to reverse-round.
	 * @param string $currency The currency code.
	 *
	 * @return float
	 */
	public function down( $value, $currency = '' ) {
		if ( ! is_numeric( $value ) ) {
			$value = 0.0;
		}

		// Do not touch negative values.
		if ( $value < 0 ) {
			return $value;
		}

		$value = (float) $value;

		$this->settings = \apply_filters( 'woocommerce_multicurrency_rounder_settings', $this->settings, $currency );

		// Un-charm.
		$value += $this->settings['price_charm'];

		if ( $this->settings['round_to'] > 0.01 ) {
			// Cannot restore the value before rounding, so make it down by half of the "round_to".
			$value -= $this->settings['round_to'] / 2;
		}

		// Un-fee.
		$value /= ( 1.0 + $this->settings['fee_percent'] / 100.0 );

		return round( $value, 2 );
	}
}
