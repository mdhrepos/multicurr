<?php
/**
 * Price calculator.
 *
 * @since 1.0.0
 */

namespace WOOMC\Price;

use WOOMC\Log;
use WOOMC\Rate\Storage;


/**
 * Class Price\Calculator
 */
class Calculator {

	/**
	 * The Rate Storage instance.
	 *
	 * @var  Storage
	 */
	protected $rate_storage;

	/**
	 * The Price Rounder instance.
	 *
	 * @var  Rounder
	 */
	protected $price_rounder;

	/**
	 * True if currency conversion is enabled.
	 *
	 * @since 2.6.0
	 *
	 * @var bool
	 */
	protected $conversion_enabled = true;

	/**
	 * Getter for $conversion_enabled.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function isConversionEnabled() {
		return $this->conversion_enabled;
	}

	/**
	 * Setter for $conversion_enabled.
	 *
	 * @since 2.6.0
	 *
	 * @param bool $conversion_enabled
	 *
	 * @return bool
	 */
	protected function setConversionEnabled( $conversion_enabled ) {
		$previous = $this->conversion_enabled;

		$this->conversion_enabled = (bool) $conversion_enabled;

		return $previous;
	}

	/**
	 * Enable conversion.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function enable_conversion() {
		return $this->setConversionEnabled( true );
	}

	/**
	 * Disable conversion.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function disable_conversion() {
		return $this->setConversionEnabled( false );
	}

	/**
	 * Price\Calculator constructor.
	 *
	 * @param Storage $rate_storage  The Rate Storage instance.
	 * @param Rounder $price_rounder The Price Rounder instance.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( Storage $rate_storage, Rounder $price_rounder ) {
		$this->rate_storage  = $rate_storage;
		$this->price_rounder = $price_rounder;
	}

	/**
	 * Calculate the price for the currency specified.
	 *
	 * @since 1.12.1 Added the `reverse` parameter.
	 *
	 * @param int|float|string $value   The value to convert.
	 * @param string           $to      Currency to convert to.
	 * @param string           $from    Currency to convert from.
	 * @param bool             $reverse If this is a reverse conversion.
	 *
	 * @return int|float|string
	 */
	public function calculate( $value, $to, $from, $reverse = false ) {

		/**
		 * Stopper.
		 *
		 * @since 2.6.0
		 */
		if ( ! $this->isConversionEnabled() ) {
			return $value;
		}

		if ( ! $value || ! is_numeric( $value ) ) {
			// Default return value (invalid parameters).
			$calculated = 0.0;
		} elseif ( $to === $from ) {
			// Same currency..no need to convert.
			$calculated = $value;
		} else {
			if ( $reverse ) {
				// Undo price adjustments.
				$calculated = $this->price_rounder->down( $value, $to );
				// Convert.
				$calculated = (float) $calculated * $this->rate_storage->get_rate( $to, $from );
			} else {
				// Convert.
				$calculated = (float) $value * $this->rate_storage->get_rate( $to, $from );
				// Do price adjustments.
				$calculated = $this->price_rounder->up( $calculated, $to );
			}

			// Try to cast back to the input variable's type.
			$calculated = self::cast_to( $calculated, $value );

			/**
			 * Filter to adjust the calculated value.
			 *
			 * @since 2.3.0
			 *
			 * @param int|float|string $calculated The calculated value to adjust.
			 * @param int|float|string $value      The original value to convert.
			 * @param string           $to         Currency to convert to.
			 * @param string           $from       Currency to convert from.
			 * @param bool             $reverse    If this is a reverse conversion.
			 * @param Calculator       $this       The Calculator instance.
			 */
			$calculated = \apply_filters( 'woocommerce_multicurrency_calculate', $calculated, $value, $to, $from, $reverse, $this );

		}

		return $calculated;
	}

	/**
	 * Calculate the price for the currency specified, ignoring all price adjustments.
	 *
	 * @since 1.16.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 */
	public function calculate_raw( $value, $to, $from ) {

		// Default return value (invalid parameters).
		if ( ! $value || ! is_numeric( $value ) ) {
			Log::error( new \Exception( 'Invalid value passed: ' . \maybe_serialize( $value ) ) );

			return 0.0;
		}
		if ( $to === $from ) {
			return $value;
		}

		// Convert.
		$calculated = (float) $value * $this->rate_storage->get_rate( $to, $from );

		// Drop insignificant digits. "3" is because some currencies have 3 decimals.
		$calculated = round( $calculated, 3 );

		// Try to cast back to the input variable's type.
		if ( settype( $calculated, gettype( $value ) ) ) {
			return $calculated;
		}

		// Could not cast? Return the unchanged input. Probably, unreachable code.

		// @codeCoverageIgnoreStart
		return $value;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Cast one variable to the type of another.
	 * If cannot cast, return unchanged.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed $value                    Value to cast.
	 * @param mixed $value_with_required_type Value to get the type from.
	 *
	 * @return mixed
	 */
	public static function cast_to( $value, $value_with_required_type ) {

		// Try to cast.
		$result = $value;
		if ( settype( $result, gettype( $value_with_required_type ) ) ) {
			// Cast succeeded.
			return $result;
		}

		// Could not cast? Return the unchanged input. Probably, unreachable code.
		// @codeCoverageIgnoreStart
		return $value;
		// @codeCoverageIgnoreEnd
	}

}
