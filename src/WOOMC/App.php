<?php
/**
 * Application.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use TIVWP_140\AbstractApp;
use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use TIVWP_140\WC\WCEnv;
use WOOMC\Settings\Panel;

/**
 * Class App
 */
class App extends AbstractApp implements InterfaceHookable {

	/**
	 * To display a message after activation.
	 *
	 * @var string
	 */
	const ACTIVATION_TRANSIENT = 'woocommerce-multicurrency-activated';

	/**
	 * Flag used to disable changing the settings.
	 *
	 * @var bool
	 */
	protected $read_only_settings = false;

	/**
	 * Getter for $this->read_only_settings.
	 *
	 * @return bool
	 */
	public function isReadOnlySettings() {
		return $this->read_only_settings;
	}

	/**
	 * Setter for $this->read_only_settings.
	 *
	 * @param bool $read_only_settings True/False.
	 */
	public function setReadOnlySettings( $read_only_settings ) {
		$this->read_only_settings = (bool) $read_only_settings;
	}

	/**
	 * The list of enable languages (if multilingual).
	 *
	 * @var string[]
	 */
	protected $enabled_languages;

	/**
	 * Getter for $this->enabled_languages.
	 *
	 * @return string[]
	 */
	public function getEnabledLanguages() {
		return $this->enabled_languages;
	}

	/**
	 * The current language.
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Getter for $this->language.
	 *
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * The language names in English (if multilingual).
	 *
	 * @var string[]
	 */
	protected $en_language_name;

	/**
	 * Get the language name in English.
	 *
	 * @param string $language The language code.
	 *
	 * @return string
	 */
	public function getEnLanguageName( $language ) {
		return $this->en_language_name[ $language ];
	}

	/**
	 * The support URL.
	 *
	 * @var string
	 */
	protected $url_support = 'https://woocommerce.com/my-account/tickets/';

	/**
	 * The documentation URL.
	 *
	 * @var string
	 */
	protected $url_documentation = 'https://docs.woocommerce.com/document/multi-currency/';

	/**
	 * Getter for $this->url_support.
	 *
	 * @return string
	 */
	public function getUrlSupport() {
		return $this->url_support;
	}

	/**
	 * Getter for $this->url_documentation.
	 *
	 * @return string
	 */
	public function getUrlDocumentation() {
		return $this->url_documentation;
	}

	/**
	 * The current User.
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Getter for $this->user.
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Is a supported multilingual plugin active.
	 *
	 * @var bool
	 */
	protected $multilingual = false;

	/**
	 * Getter for $multilingual.
	 *
	 * @return bool
	 */
	public function isMultilingual() {
		return $this->multilingual;
	}

	/**
	 * Setter for $multilingual.
	 *
	 * @param bool $true_false
	 */
	public function setMultilingual( $true_false ) {
		$this->multilingual = $true_false;
	}

	/**
	 * Multilingual provider ID/Name.
	 *
	 * @since 2.6.1
	 * @var string
	 */
	protected $multilingual_provider = '';

	/**
	 * Price calculator instance.
	 *
	 * @since 2.6.0
	 *
	 * @var Price\Calculator
	 */
	protected $price_calculator;

	/**
	 * Getter: Price calculator instance.
	 *
	 * @since 2.6.0
	 * @return Price\Calculator
	 */
	public function getPriceCalculator() {
		return $this->price_calculator;
	}

	/**
	 * Currency controller instance.
	 *
	 * @since 2.6.0
	 *
	 * @var Currency\Controller
	 */
	protected $currency_controller;

	/**
	 * Getter: Currency controller instance.
	 *
	 * @since 2.6.0
	 * @return Currency\Controller
	 */
	public function getCurrencyController() {
		return $this->currency_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		/**
		 * Required by {@see \WOOMC\AApp::load_translations()}.
		 */
		$this->textdomain = 'woocommerce-multicurrency';
		$this->load_translations();

		\register_activation_hook( $this->plugin_file, array( $this, 'set_activation_transient' ) );
		\add_action( 'admin_notices', array( $this, 'display_activation_notice' ) );

		\add_action( 'plugins_loaded', array( $this, 'setup_hooks_after_plugins_loaded' ), self::HOOK_PRIORITY_LATE );

		\register_deactivation_hook( $this->plugin_file, array( $this, 'on_deactivation' ) );
	}

	/**
	 * Do stuff on plugin deactivation.
	 *
	 * @since 1.20.0
	 */
	public function on_deactivation() {

		$rate_update_scheduler = new Rate\UpdateScheduler();
		$rate_update_scheduler->remove_cron_job();
	}

	/**
	 * Set transient to flag that the plugin was just activated.
	 */
	public function set_activation_transient() {
		\set_transient( self::ACTIVATION_TRANSIENT, true );
	}

	/**
	 * Display the activation notice once.
	 */
	public function display_activation_notice() {

		if ( \get_transient( self::ACTIVATION_TRANSIENT ) ) {
			$url_settings = \add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab'  => Panel::TAB_SLUG,
				),
				\admin_url( 'admin.php' )
			);

			Admin\Notices::activation( $url_settings, $this->getUrlDocumentation() );

			\delete_transient( self::ACTIVATION_TRANSIENT );
		}
	}

	/**
	 * Check the prerequisites.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public static function requirements_met() {
		return class_exists( 'WooCommerce', false ) && version_compare( wc()->version, '3.0.0', '>=' );
	}

	/**
	 * This runs only after we checked the prerequisites.
	 *
	 * @internal filter.
	 */
	public function setup_hooks_after_plugins_loaded() {

		if ( ! self::requirements_met() ) {
			\add_action( 'admin_notices', array( Admin\Notices::get_class(), 'requirements' ) );

			return;
		}

		// This must be the first action. Do not do anything before it.
		$this->get_multilingual_config();

		$migration = new Settings\Migration();
		$migration->maybe_migrate();

		/**
		 * Hook to run before we loaded.
		 *
		 * @since 1.9.0
		 */
		\do_action( 'woocommerce_multicurrency_before_loading' );

		/**
		 * Recalculate totals on "refresh fragments" AJAX.
		 *
		 * @since 1.9.0
		 *
		 * @since 1.10.0 Moved to run after plugins loaded.
		 * WC Subscriptions do the same on priority 1. Let us work later.
		 * However, we cannot run on priority >= 10, because {@see \WC_AJAX::get_refreshed_fragments()} dies there.
		 */
		\add_action(
			'wc_ajax_get_refreshed_fragments',
			array(
				$this,
				'action__wc_ajax_get_refreshed_fragments',
			),
			9
		);

		/**
		 * Initialise the User (visitor).
		 *
		 * @since 1.4.0
		 */
		$this->user = new User();
		$this->user->setup_hooks();

		$currency_detector = new Currency\Detector();
		$currency_detector->setup_hooks();

		$rate_storage = new Rate\Storage();
		$rate_storage->setup_hooks();

		$price_rounder = new Price\Rounder();

		$this->price_calculator = new Price\Calculator( $rate_storage, $price_rounder );

		$this->currency_controller = new Currency\Controller( $currency_detector );
		$this->currency_controller->setup_hooks();

		/**
		 * Some currencies use different number of decimals. JPY has no decimals, for example.
		 *
		 * @since 1.5.0
		 * @since 2.0.0 Do not need to pass Detector.
		 */
		$decimals = new Currency\Decimals();
		$decimals->setup_hooks();

		$price_controller = new Price\Controller( $this->price_calculator, $currency_detector );
		$price_controller->setup_hooks();

		$locale = new Locale();
		$locale->setup_hooks();

		$price_formatter = new Price\Formatter( $locale );
		$price_formatter->setup_hooks();

		$currency_selector_shortcode = new Currency\Selector\Shortcode();
		$currency_selector_shortcode->setup_hooks();

		$currency_selector_widget = new Currency\Selector\Widget();
		$currency_selector_widget->setup_hooks();

		$settings = new Settings\Controller( $rate_storage );
		$settings->setup_hooks();

		/**
		 * Multi-currency admin reports (sales, etc.)
		 *
		 * @since 1.7.0
		 */
		$reports = new Reports\Controller();
		$reports->setup_hooks();

		/**
		 * Classes related to orders.
		 *
		 * @since 1.16.0
		 */
		new Order\Controller( $rate_storage );

		/**
		 * Shortcode: Convert.
		 *
		 * @since 1.16.0
		 */
		$shortcode_covert = new Shortcode\Convert( $price_controller );
		$shortcode_covert->setup_hooks();

		/**
		 * Rate update scheduler.
		 *
		 * @since 1.20.0
		 */
		$rate_update_scheduler = new Rate\UpdateScheduler();
		$rate_update_scheduler->setup_hooks();

		/**
		 * Integration with WooCommerce Analytics.
		 *
		 * @note  Does not work if placed under `is_admin`.
		 *
		 * @since 2.5.0
		 */
		$wc_analytics = new Admin\Analytics\Controller();
		$wc_analytics->setup_hooks();

		/**
		 * Support caching plugins.
		 *
		 * @since 1.8.0
		 * @since 2.7.1 Moved to own folder and Controller. Running always, not only on frontend.
		 */
		$integration_cache_controller = new Integration\Cache\Controller();
		$integration_cache_controller->setup_hooks();

		if ( \is_admin() ) {
			/**
			 * Custom price metaboxes for products.
			 *
			 * @since 2.4.0
			 */
			$custom_price_controller = new Admin\CustomPrice\Controller();
			$custom_price_controller->setup_hooks();
		}

		if ( Env::in_wp_admin() ) {
			/**
			 * Edit currency of the order.
			 *
			 * @since 1.8.0
			 */
			$edit_currency = new Admin\EditCurrency();
			$edit_currency->setup_hooks();

			/**
			 * Include Multi-currency in the WooCommerce status report.
			 *
			 * @since 1.13.0
			 */
			$status_report = new Admin\StatusReport();
			$status_report->setup_hooks();

			/**
			 * Add our tools to the WooCommerce "Tools" tab.
			 *
			 * @since 1.15.0
			 */
			$tools = new Admin\Tools();
			$tools->setup_hooks();

			/**
			 * Check the compatibility issues.
			 *
			 * @since 1.16.0
			 * @since 2.0.0 Disabled. Confusing some users. Especially WPML.
			 * <code>
			 * $compatibility = new Admin\Compatibility();
			 * $compatibility->setup_hooks();
			 * </code>
			 */

			/**
			 * Admin menu setup.
			 *
			 * @since 1.16.0
			 */
			$admin_menu = new Admin\Menu();
			$admin_menu->setup_hooks();

		} else {

			/**
			 * Frontend.
			 *
			 * @since 2.6.3-rc.2
			 */
			$frontend = new Frontend\Controller();
			$frontend->setup_hooks();
		}

		if ( Env::on_front() && ! WCEnv::is_analytics_request() ) {
			/**
			 * Standard shipping fees converter.
			 *
			 * @since 2.6.7-beta.1 Moved to a separate class.
			 */
			$shipping_standard = new Shipping\OptionsConverter( $price_controller );
			$shipping_standard->setup_hooks();
		}


		/**
		 * Hook to run after we finished loading.
		 *
		 * @since 1.9.0
		 */
		do_action( 'woocommerce_multicurrency_loaded' );
	}

	/**
	 * Multilingual support.
	 * Get WPGlobus language settings and other configuration options into the App instance.
	 *
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function get_multilingual_config() {

		$admin_locale     = \get_locale();
		$admin_language   = substr( $admin_locale, 0, 2 );
		$default_settings = array(
			'active'                => false,
			'multilingual_provider' => '',
			'enabled_languages'     => (array) $admin_language,
			'language'              => $admin_language,
			'en_language_name'      => array( $admin_language => $admin_locale ),
		);

		/**
		 * Hook for 3rd party multilingual extensions.
		 *
		 * @since 2.6.1
		 *
		 * @param array $default_settings Multilingual settings.
		 */
		$multilingual_settings = \apply_filters( 'woocommerce_multicurrency_multilingual_settings', $default_settings );

		if ( ! empty( $multilingual_settings['active'] ) ) {
			$this->setMultilingual( true );
			$this->multilingual_provider = $multilingual_settings['multilingual_provider'];
			$this->enabled_languages     = $multilingual_settings['enabled_languages'];
			$this->language              = $multilingual_settings['language'];
			$this->en_language_name      = $multilingual_settings['en_language_name'];

			return;
		}

		if (
			class_exists( 'Polylang', false )
			&& function_exists( 'pll_languages_list' )
		) {
			/**
			 * Polylang is active. We'll use its language settings.
			 */
			$this->setMultilingual( true );

			$this->enabled_languages = \pll_languages_list();
			$this->language          = \pll_current_language();
			$language_names          = \pll_languages_list( array( 'fields' => 'name' ) );
			$this->en_language_name  = array_combine( $this->enabled_languages, $language_names );
		} elseif ( class_exists( 'WPGlobus', false ) ) {
			/**
			 * WPGlobus is active. We'll use its language settings.
			 */
			$this->setMultilingual( true );

			$wpglobus_config         = \WPGlobus::Config();
			$this->enabled_languages = $wpglobus_config->enabled_languages;
			$this->language          = $wpglobus_config->language;
			$this->en_language_name  = $wpglobus_config->en_language_name;
		} else {
			// No supported multilingual. Use the current site language.
			$this->setMultilingual( false );

			$admin_locale            = \get_locale();
			$this->enabled_languages = array( substr( $admin_locale, 0, 2 ) );
			$this->language          = $this->enabled_languages[0];
			$this->en_language_name  = array( $this->language => $admin_locale );
		}
	}

	/**
	 * Recalculate totals on "refresh fragments" AJAX.
	 * Without this, the mini-cart widget and the top bar cart show wrong totals when the currency is switched.
	 *
	 * @since    1.9.0
	 *
	 * @internal action.
	 */
	public function action__wc_ajax_get_refreshed_fragments() {
		\wc()->cart->calculate_totals();
	}

}
