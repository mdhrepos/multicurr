<?php
/**
 * Locale.
 *
 * @since 2.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use TIVWP_140\InterfaceHookable;

/**
 * Class Locale
 *
 * @package WOOMC
 */
class Locale implements InterfaceHookable {

	/**
	 * Country.
	 *
	 * @var string
	 */
	protected $country = '';

	/**
	 * Decimal separator.
	 *
	 * @var string
	 */
	protected $decimal_separator = '.';

	/**
	 * Thousand separator.
	 *
	 * @var string
	 */
	protected $thousand_separator = ',';

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getDecimalSeparator() {
		return $this->decimal_separator;
	}

	/**
	 * Setter.
	 *
	 * @param string $decimal_separator
	 */
	public function setDecimalSeparator( $decimal_separator ) {
		$this->decimal_separator = $decimal_separator;
	}

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getThousandSeparator() {
		return $this->thousand_separator;
	}

	/**
	 * Setter.
	 *
	 * @param string $thousand_separator
	 */
	public function setThousandSeparator( $thousand_separator ) {
		$this->thousand_separator = $thousand_separator;
	}


	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$this->set_default_separators();

		\add_action(
			'init',
			array( $this, 'setup' ),
			App::HOOK_PRIORITY_LATE
		);
	}

	/**
	 * Default values for separators are taken from the Woo General Tab.
	 *
	 * @return void
	 */
	protected function set_default_separators() {
		$this->setDecimalSeparator( \get_option( 'woocommerce_price_decimal_sep', '.' ) );
		$this->setThousandSeparator( \get_option( 'woocommerce_price_thousand_sep', ',' ) );
	}

	/**
	 * Setup locale.
	 *
	 * @return void
	 * @noinspection PhpIncludeInspection
	 */
	public function setup() {
		$this->country = $this->detect_country();

		$locale_info = include \WC()->plugin_path() . '/i18n/locale-info.php';

		$locale_info = array_merge( $locale_info, $this->additional_locales() );

		if ( isset( $locale_info[ $this->country ] ) ) {
			if ( \get_woocommerce_currency() === $locale_info[ $this->country ]['currency_code'] ) {
				$this->decimal_separator  = $locale_info[ $this->country ]['decimal_sep'];
				$this->thousand_separator = $locale_info[ $this->country ]['thousand_sep'];
			}
		}
	}

	/**
	 * True if one of the supported multilingual plugins is active.
	 *
	 * @return bool
	 */
	protected function is_multilingual() {
		return class_exists( 'Polylang', false )
			   || class_exists( 'WPGlobus', false );
	}

	/**
	 * Parse locale and return the country part, uppercase.
	 *
	 * @return string
	 */
	protected function get_country_from_locale() {

		$locale = \get_locale();

		if ( false !== strpos( $locale, '_' ) ) {
			// Locale in the form `en_US`.
			list( , $country ) = explode( '_', $locale );

		} else {
			// Locale in the form `de`.
			$country = $locale;
		}

		return strtoupper( $country );
	}

	/**
	 * Detect country of user.
	 *
	 * @return string
	 */
	protected function detect_country() {

		if ( $this->is_multilingual() ) {
			return $this->get_country_from_locale();
		}

		/**
		 * Try Geolocation.
		 *
		 * @var User $user
		 */
		$user = App::instance()->getUser();
		if ( $user ) {
			$country_of_user = $user->get_country();
			if ( $country_of_user ) {
				return $country_of_user;
			}
		}

		return \WC()->countries->get_base_country();
	}

	/**
	 * Locales missing in i18n/locale-info.php.
	 *
	 * @return array
	 */
	protected function additional_locales() {
		return array(
			'RU' =>
				array(
					'currency_code'  => 'RUB',
					'currency_pos'   => 'right_space',
					'thousand_sep'   => ' ',
					'decimal_sep'    => ',',
					'num_decimals'   => 2,
					'weight_unit'    => 'kg',
					'dimension_unit' => 'cm',
				),
			'CH' =>
				array(
					'currency_code'  => 'CHF',
					'currency_pos'   => 'right_space',
					'thousand_sep'   => "'",
					'decimal_sep'    => '.',
					'num_decimals'   => 2,
					'weight_unit'    => 'kg',
					'dimension_unit' => 'cm',
				),
		);
	}
}
