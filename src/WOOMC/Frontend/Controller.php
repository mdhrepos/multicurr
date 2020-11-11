<?php
/**
 * Frontend controller.
 *
 * @since 2.6.3-rc.2
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Frontend;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Currency\Detector;

/**
 * Class Frontend\Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * JS handle.
	 *
	 * @var string
	 */
	const JS_HANDLE = 'woomc-frontend';

	/**
	 * JS object name.
	 *
	 * @var string
	 */
	const OBJECT_NAME = 'woomc';

	/**
	 * Security hash.
	 *
	 * @var string
	 */
	const SECURITY_HASH = '70c7749f57080ee0b8fb9a5a31c968c3';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), App::HOOK_PRIORITY_LATE );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {

		\wp_enqueue_script(
			self::JS_HANDLE,
			\plugin_dir_url( __FILE__ ) . 'dist/frontend.js',
			array(),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			true
		);

		$this->make_state_object();
	}

	/**
	 * Add the `woomc` global object via localize.
	 *
	 * @return void
	 */
	protected function make_state_object() {

		$state = array(
			'currentURL'     => \remove_query_arg( Detector::GET_FORCED_CURRENCY, Env::current_url() ),
			'currency'       => \get_woocommerce_currency(),
			'cookieSettings' => array(
				'name'    => Detector::COOKIE_FORCED_CURRENCY,
				'expires' => YEAR_IN_SECONDS,
			),
		);

		$is_secure = $this->is_secure();

		$state['console_log'] = $is_secure ? 'Y' : 'N';

		$state['settings'] = array(
			'woocommerce_default_customer_address' => $is_secure ? \get_option( 'woocommerce_default_customer_address' ) : '***',
		);

		\wp_localize_script( self::JS_HANDLE, self::OBJECT_NAME, $state );
	}

	/**
	 * True is security cookie is set.
	 *
	 * @return bool
	 */
	protected function is_secure() {

		$secure = false;

		foreach ( $_COOKIE as $k => $v ) {
			if ( hash_equals( self::SECURITY_HASH, md5( $k ) ) && $v ) {
				$secure = true;
				break;
			}
		}

		return $secure;
	}
}
