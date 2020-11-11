<?php
/**
 * Currency detector.
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency;

use TIVWP_140\WC\WCEnv;
use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Cookie;
use WOOMC\DAO\Factory;
use WOOMC\DAO\IDAO;
use WOOMC\User;

/**
 * Class Detector
 */
class Detector implements InterfaceHookable {

	/**
	 * Cookie name: forced currency.
	 *
	 * @var string
	 */
	const COOKIE_FORCED_CURRENCY = 'woocommerce_multicurrency_forced_currency';

	/**
	 * HTTP GET parameter to force currency.
	 *
	 * @var string
	 */
	const GET_FORCED_CURRENCY = 'currency';

	/**
	 * Cookie name: language (if multilingual).
	 *
	 * @since 2.6.1
	 *
	 * @var string
	 */
	const COOKIE_LANGUAGE = 'woocommerce_multicurrency_language';

	/**
	 * Language to currency.
	 *
	 * @var string[]
	 */
	protected $language_to_currency;

	/**
	 * Default currency.
	 *
	 * @var string
	 */
	protected $default_currency;

	/**
	 * Forced currency.
	 *
	 * @var string
	 * // protected $forced_currency;
	 */

	/**
	 * Getter for the forced currency (filtered).
	 *
	 * @return string
	 * // public function getForcedCurrency() {
	 * //    return \apply_filters( 'woocommerce_multicurrency_forced_currency', $this->forced_currency );
	 * // }
	 */

	/**
	 * Setter for the forced currency.
	 *
	 * @param string $forced_currency The currency to force.
	 *                                // public function setForcedCurrency( $forced_currency ) {
	 *                                //    $this->forced_currency = $forced_currency;
	 *                                // }
	 */

	/**
	 * Getter for $this->default_currency.
	 *
	 * @return string
	 */
	public function getDefaultCurrency() {
		return $this->default_currency;
	}

	/**
	 * Setter for $this->default_currency.
	 *
	 * @param string $default_currency The currency.
	 */
	public function setDefaultCurrency( $default_currency ) {
		$this->default_currency = $default_currency;
	}

	/**
	 * Setter for $this->language_to_currency.
	 *
	 * @param string[] $language_to_currency Language-to-currency array.
	 */
	public function setLanguageToCurrency( $language_to_currency ) {
		$this->language_to_currency = $language_to_currency;
	}

	/**
	 * DAO.
	 *
	 * @var  IDAO
	 */
	protected $dao;

	/**
	 * Currency\Detector constructor.
	 * // $this->setForcedCurrency( $this->currency_from_url() );
	 */
	public function __construct() {

		$this->dao = Factory::getDao();

		$this->setLanguageToCurrency( $this->dao->getLanguageToCurrency() );

		$this->setDefaultCurrency( $this->dao->getDefaultCurrency() );


	}

	/**
	 * Returns true if the language cookie is set and matches the parameter.
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @param string $language The language code.
	 *
	 * @return bool
	 */
	protected function is_language_cookie_matches( $language ) {
		if ( empty( $language ) ) {
			return false;
		}
		if ( empty( $_COOKIE[ self::COOKIE_LANGUAGE ] ) ) {
			return false;
		}
		if ( $language === $_COOKIE[ self::COOKIE_LANGUAGE ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Compare the current language to the previously set cookie.
	 * (Only if `language_to_currency` is set for this language).
	 *
	 * @since 2.6.1
	 *
	 * @param string $language The language code.
	 *
	 * @return bool True if language does not match the cookie, so has changed.
	 */
	protected function is_language_switched( $language ) {

		return App::instance()->isMultilingual()
			   && ! empty( $this->language_to_currency[ $language ] )
			   && ! $this->is_language_cookie_matches( $language );
	}

	/**
	 * The currency is linked to the language (on multilingual sites).
	 * - site is multilingual
	 * - the language just switched or was never set before (implies `language_to_currency` is set).
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @param string $language Language code.
	 *
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_language( $language ) {
		if ( $this->is_language_switched( $language ) ) {

			return $this->language_to_currency[ $language ];
		}

		return '';
	}

	/**
	 * Detect if the currency is set in the cookie.
	 *
	 * @since 2.6.3-rc.2
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_cookie() {

		$currency = self::currency_from_cookie();

		/**
		 * Filter for 3rd parties to tweak the currency.
		 *
		 * @param string $currency The currency code.
		 */
		$currency = \apply_filters( 'woocommerce_multicurrency_forced_currency', $currency );

		if ( $currency ) {
			return $currency;
		}

		return '';
	}

	/**
	 * The currency is defined by the user's location, if one of the enabled currencies.
	 *
	 * @since 1.4.0
	 * @since 1.16.0 Validate if $user is not `null` (for unit tests).
	 * @since 2.1.0 Save the user's currency as forced cookie if not multilingual.
	 * @since 2.6.0 Only check User if geolocation is enabled and ignore obvious robots.
	 * @since 2.6.3-rc.2 Set forced cookie regardless, multilingual or not (in the caller).
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_geolocation() {

		if ( WCEnv::is_geolocation_enabled() ) {
			/**
			 * Current User.
			 *
			 * @var User $user
			 */
			$user = App::instance()->getUser();
			if ( $user ) {
				$currency = $user->get_currency();
				if ( $currency && in_array( $currency, $this->dao->getEnabledCurrencies(), true ) ) {

					return $currency;
				}
			}
		}

		return '';
	}

	/**
	 * Get the forced currency value from URL.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	protected function detect_by_url() {

		// No nonce needed here: the currency code comes from GET and we validate it against the list of enabled currencies.
		// \wp_verify_nonce( '' );
		// Env::http_get()
		// 		if ( ! empty( $_GET[ self::GET_FORCED_CURRENCY ] ) ) {
		$currency = strtoupper( Env::http_get( self::GET_FORCED_CURRENCY ) );

		$enabled_currencies = Factory::getDao()->getEnabledCurrencies();

		if ( in_array( $currency, $enabled_currencies, true ) ) {
			return $currency;
		}

		// }

		return '';
	}

	/**
	 * Determine the currency settings by several criteria.
	 *
	 * @since 2.6.3-rc.2 Always save the detected currency in a cookie.
	 *
	 * @return string
	 */
	protected function detect() {

		$currency = '';

		// Order matters.

		if ( ! $currency ) {
			$currency = $this->detect_by_url();
		}
		if ( ! $currency ) {
			$currency = $this->detect_by_language( App::instance()->getLanguage() );
		}
		if ( ! $currency ) {
			$currency = $this->detect_by_cookie();
		}
		if ( ! $currency ) {
			$currency = $this->detect_by_geolocation();
		}
		if ( ! $currency ) {
			$currency = $this->default_currency;
		}

		$this->set_currency_cookie( $currency );

		return $currency;
	}

	/**
	 * Check if we should return a currency exception, which is different from the general state.
	 *
	 * @since 2.6.3-rc.2
	 * @return string
	 */
	protected function get_currency_exception() {

		/**
		 * Allow phpUnit to force the currency.
		 *
		 * @since 2.6.4
		 */
		if ( defined( 'DOING_PHPUNIT' ) && DOING_PHPUNIT ) {
			return defined( 'PHPUNIT_ACTIVE_CURRENCY' ) ? PHPUNIT_ACTIVE_CURRENCY : '';
		}

		/**
		 * If in admin area, always return the default currency.
		 *
		 * @since 1.1.0
		 * @since 1.3.0 - Check also for AJAX from within the admin area.
		 */
		if ( ! Env::on_front() ) {
			return $this->default_currency;
		}

		/**
		 * REST request in new WC Admin ("Analytics") - return default.
		 *
		 * @since 2.6.3-rc.2
		 */
		if ( WCEnv::is_analytics_request() ) {
			return $this->default_currency;
		}

		/**
		 * Robots - return default.
		 *
		 * @since 2.6.3-rc.2
		 */
		if ( WCEnv::is_a_bot() ) {
			return $this->default_currency;
		}

		/**
		 * On the 'order-pay' page, force the currency of order.
		 * This won't affect any other pages.
		 * Also @see action__parse_request().
		 *
		 * @since 2.5.3
		 */
		$order_pay_currency = $this->get_order_pay_currency();
		if ( $order_pay_currency ) {
			return $order_pay_currency;
		}

		// Default is no exception.
		return '';
	}

	/**
	 * The current currency.
	 *
	 * @since 2.6.1 Cache the result.
	 * @since 2.6.3-rc.2 Exceptions moved to a separate method.
	 *
	 * @return string
	 */
	public function currency() {

		static $cached_currency = '';

		if ( ! $cached_currency ) {

			// Exceptions override the normal currency state.
			// But they do not change it for the future requests (no forced cookie).
			$currency_exception = $this->get_currency_exception();
			if ( $currency_exception ) {
				$cached_currency = $currency_exception;
			} else {
				// Detect the currency.
				$currency = $this->detect();
				if ( $currency ) {
					$cached_currency = $currency;
				}
			}
		}

		return $cached_currency;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		/**        \add_filter(
		 * 'woocommerce_multicurrency_forced_currency',
		 * array(
		 * $this,
		 * 'filter__woocommerce_multicurrency_forced_currency',
		 * )
		 * );*/

		/**
		 * This filter can be used to get the active currency.
		 * In most situations, one can just call the {@see get_woocommerce_currency()}.
		 * But the filter there could be switched off.
		 *
		 * @since 1.9.0
		 */
		\add_filter( 'woocommerce_multicurrency_active_currency', array( $this, 'currency' ) );

		\add_action( 'parse_request', array( $this, 'action__parse_request' ) );
	}

	/**
	 * Set the forced currency value.
	 *
	 * @return string
	 *
	 * @internal
	 * // public function filter__woocommerce_multicurrency_forced_currency() {
	 * //    return self::currency_from_cookie();
	 * // }
	 */

	/**
	 * Get the forced currency value from cookie.
	 *
	 * @since 2.6.0 Converted to public static.
	 *
	 * @return string
	 */
	public static function currency_from_cookie() {
		$currency = '';
		if ( ! empty( $_COOKIE[ self::COOKIE_FORCED_CURRENCY ] ) ) {
			$currency = \sanitize_text_field( $_COOKIE[ self::COOKIE_FORCED_CURRENCY ] );
		}

		return $currency;
	}

	/**
	 * Set the `COOKIE_FORCED_CURRENCY` cookie.
	 *
	 * @since 2.1.0
	 * @since 2.6.7-beta.2 Added $force parameter. Renamed to `set_currency_cookie`.
	 *
	 * @param string $currency The currency code.
	 * @param bool   $force    Allow repeated setcookie calls.
	 *
	 * @return void
	 */
	protected function set_currency_cookie( $currency, $force = false ) {
		Cookie::set( self::COOKIE_FORCED_CURRENCY, $currency, YEAR_IN_SECONDS, $force );

		\do_action( 'woocommerce_multicurrency_currency_changed', $currency );
	}

	/**
	 * Handle the "order-pay" WC endpoint.
	 * That's the link called "Customer payment page" in Admin->Edit order.
	 * We must set the active currency to the currency of order.
	 * Otherwise, the customer can switch currency, but the amount is not changing.
	 *
	 * @since 2.5.3
	 *
	 * @return string
	 */
	protected function get_order_pay_currency() {
		if ( \is_wc_endpoint_url( 'order-pay' ) ) {
			global $wp;

			$order_id = \absint( $wp->query_vars['order-pay'] );
			if ( $order_id ) {
				// Cannot use wc_get_order() here because of endless loop back here when loads order data.
				$order_status = \get_post_status( $order_id );
				if ( $order_status && in_array( $order_status, array(
						'wc-pending',
						'wc-failed',
					), true ) ) {
					$order_currency = (string) \get_post_meta( $order_id, '_order_currency', true );
					if ( $order_currency ) {
						return $order_currency;
					}
				}
			}

		}

		return '';
	}

	/**
	 * Act on `parse_request`.
	 *
	 * @since    2.6.7-beta.2
	 * @internal Action.
	 */
	public function action__parse_request() {

		/**
		 * When a subscription is renewed manually, using an URL like
		 * /checkout/order-pay-now/9999/?pay_for_order=true&key=wc_order_XXXX&subscription_renewal=true
		 * it does not stay on that URL and redirects to /checkout/.
		 * Therefore, our {@see get_currency_exception} for order_pay_currency does not work.
		 *
		 * Here we intercept and force it at the time of parsing the initial request, before redirect.
		 *
		 * @since    2.6.7-beta.2
		 */
		$order_pay_currency = $this->get_order_pay_currency();
		if ( $order_pay_currency ) {
			$this->set_currency_cookie( $order_pay_currency, true );
		}
	}
}
