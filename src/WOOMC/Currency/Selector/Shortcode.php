<?php
/**
 * Currency Selector Shortcode.
 *
 * @example
 *  [TAG format="{{code}}: {{name}} ({{symbol}})"]
 */

namespace WOOMC\Currency\Selector;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;

/**
 * Class Shortcode
 */
class Shortcode implements InterfaceHookable {

	/**
	 * Default format, if not passed.
	 *
	 * @var string
	 */
	const DEFAULT_FORMAT = '{{code}}: {{name}} ({{symbol}})';

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'woocommerce-currency-selector';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function setup_hooks() {

		\add_shortcode( self::TAG, array( $this, 'process_shortcode' ) );

	}

	/**
	 * Process shortcode.
	 *
	 * @since        1.17.0 Added `flag` parameter.
	 *
	 * @param string[] $params The shortcode attributes.
	 *
	 * @return string
	 *
	 * @internal
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	public function process_shortcode( $params ) {

		// Defaults if not passed.
		$params = \shortcode_atts(
			array(
				'format' => self::DEFAULT_FORMAT,
				'flag'   => 0,
			),
			$params,
			self::TAG
		);

		/**
		 * Used in the View.
		 */
		$woocommerce_currencies = \get_woocommerce_currencies();

		/**
		 * Used in the View.
		 */
		$currencies = Factory::getDao()->getEnabledCurrencies();

		/**
		 * Filter the list of currencies displayed in the dropdown.
		 *
		 * @since 2.3.0
		 *
		 * @param string[] $currencies List of currencies.
		 */
		$currencies = \apply_filters( 'woocommerce_multicurrency_shortcode_currencies', $currencies );

		/**
		 * Used in the View.
		 */
		$current_currency = \get_woocommerce_currency();

		/**
		 * Used in the View.
		 */
		$format = \apply_filters( 'woocommerce_multicurrency_shortcode_format', $params['format'] );

		/**
		 * Used in the View.
		 */
		$flag = \apply_filters( 'woocommerce_multicurrency_shortcode_flag', $params['flag'] );

		$this->enqueue_scripts();

		ob_start();
		require __DIR__ . '/ShortcodeView.php';

		return ob_get_clean();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.1.2
	 * @since 2.5.1 Enqueued only if shortcode (or widget) is on the page.
	 * @since 2.6.3-rc.2 Non-related settings moved to {@see \WOOMC\Frontend\Controller}.
	 */
	protected function enqueue_scripts() {
		static $already_done = false;
		if ( ! $already_done ) {

			// Styles for jQuery-UI selectmenu.
			$url_css = \plugin_dir_url( __FILE__ ) . 'dist/currency-selector.css?ver=' . WOOCOMMERCE_MULTICURRENCY_VERSION;
			// Flags.
			$url_vendor = App::instance()->plugin_dir_url() . 'vendor';
			$url_flags  = $url_vendor . '/tivnet/currency-flags/currency-flags.min.css?ver=' . WOOCOMMERCE_MULTICURRENCY_VERSION;

			\wp_enqueue_script(
				'woomc-currency-selector',
				\plugin_dir_url( __FILE__ ) . 'dist/currency-selector.js',
				array(
					'jquery',
					'jquery-ui-selectmenu',
				),
				WOOCOMMERCE_MULTICURRENCY_VERSION,
				true
			);

			$pass_to_js = array(
				'currencySelectorDOM' => '.' . self::TAG,
				'url'                 => array(
					'currencySelectorCSS' => $url_css,
					'currencyFlagsCSS'    => $url_flags,
				),
			);

			\wp_localize_script( 'woomc-currency-selector', 'woomc_currency_selector', $pass_to_js );

			$already_done = true;
		}
	}
}
