<?php
/**
 * WordPress Environment utilities.
 *
 * @since        1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUnused
 */

namespace TIVWP_140;

/**
 * Class Env
 */
class Env {

	/**
	 * Check if doing AJAX call.
	 *
	 * @return bool
	 */
	public static function is_doing_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || self::is_doing_wc_ajax();
	}

	/**
	 * Check if doing WooCommerce AJAX call.
	 *
	 * @return bool
	 */
	public static function is_doing_wc_ajax() {
		return defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX;
	}

	/**
	 * Attempt to check if an AJAX call was originated from admin screen.
	 *
	 * @return bool
	 *
	 * @todo There should be other actions. See $core_actions_get in admin-ajax.php
	 *       Can also check $GLOBALS['_SERVER']['HTTP_REFERER']
	 *       and $GLOBALS['current_screen']->in_admin()
	 */
	public static function is_admin_doing_ajax() {
		return (
			self::is_doing_ajax() &&
			(
				self::is_http_post_action(
					array(
						'heartbeat',
						'inline-save',
						'save-widget',
						'customize_save',
						'woocommerce_load_variations',
						'ajax-tag-search',
						'wc_braintree_paypal_get_client_token',

						/**
						 * WC Checkout Add-ons AJAX actions.
						 *
						 * @see          \SkyVerge\WooCommerce\Checkout_Add_Ons\Admin\AJAX::__construct
						 */
						'wc_checkout_add_ons_sort_add_ons',
						'wc_checkout_add_ons_enable_disable_add_on',
						'wc_checkout_add_ons_json_search_field',
						'wc_checkout_add_ons_save_order_items',
					)
				)
				|| self::is_http_get_action(
					array(
						'woocommerce_shipping_zone_methods_save_changes',
						'woocommerce_shipping_zone_methods_save_settings',
					)
				)
			)
		);
	}

	/**
	 * Do we have a certain 'action' in the HTTP POST?
	 *
	 * @param string|string[] $action The action to check.
	 *
	 * @return bool
	 */
	public static function is_http_post_action( $action ) {

		// PHPCS: WordPress.Security.NonceVerification.Missing is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		$action = (array) $action;

		return ( ! empty( $_POST['action'] ) && in_array( $_POST['action'], $action, true ) );
	}

	/**
	 * Do we have a certain 'action' in the HTTP GET?
	 *
	 * @param string|string[] $action The action to check.
	 *
	 * @return bool
	 */
	public static function is_http_get_action( $action ) {

		// PHPCS: WordPress.Security.NonceVerification.Recommended is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		$action = (array) $action;

		return ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], $action, true ) );
	}

	/**
	 * True if I am in the Admin Panel, logged in, not doing AJAX.
	 *
	 * @return bool
	 */
	public static function in_wp_admin() {
		return ( \is_admin() && ! self::is_doing_ajax() && \get_current_user_id() );
	}

	/**
	 * True if I am on a front page (not admin area), or doing AJAX from the front.
	 *
	 * @return bool
	 */
	public static function on_front() {
		return ! \is_admin() || ( self::is_doing_ajax() && ! self::is_admin_doing_ajax() );
	}

	/**
	 * Wrap debug_backtrace to avoid PHPCS warnings.
	 *
	 * @see   debug_backtrace()
	 * @since 1.1.1
	 *
	 * @param int $options [optional]
	 * @param int $limit   [optional]
	 *
	 * @return array
	 */
	public static function get_trace( $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit = 0 ) {
		static $fn = 'debug_backtrace';

		return $fn( $options, $limit );
	}

	/**
	 * Check if was called by a specific function (could be any levels deep).
	 *
	 * @param callable|string $method Function name or array(class,function).
	 *
	 * @return bool True if Function is in backtrace.
	 */
	public static function is_function_in_backtrace( $method ) {
		$function_in_backtrace = false;

		// Parse callable into class and function.
		if ( is_string( $method ) ) {
			$function_name = $method;
			$class_name    = '';
		} elseif ( is_array( $method ) && isset( $method[0], $method[1] ) ) {
			list( $class_name, $function_name ) = $method;
		} else {
			return false;
		}

		// Traverse backtrace and stop if the callable is found there.
		foreach ( self::get_trace() as $_ ) {
			if ( isset( $_['function'] ) && $_['function'] === $function_name ) {
				$function_in_backtrace = true;
				if ( $class_name && isset( $_['class'] ) && $_['class'] !== $class_name ) {
					$function_in_backtrace = false;
				}
				if ( $function_in_backtrace ) {
					break;
				}
			}
		}

		return $function_in_backtrace;
	}

	/**
	 * To call {@see is_function_in_backtrace()} with the array of parameters.
	 *
	 * @param callable[] $callables Array of callables.
	 *
	 * @return bool True if any of the pair is found in the backtrace.
	 */
	public static function is_functions_in_backtrace( array $callables ) {
		foreach ( $callables as $callable ) {
			if ( self::is_function_in_backtrace( $callable ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the method that initiated the filter.
	 *
	 * @return array|false
	 */
	public static function get_hook_caller() {

		$next_stop = false;
		foreach ( self::get_trace() as $_ ) {
			if ( $next_stop ) {
				return $_;
			}
			/**
			 * Find the {@see \apply_filters()} function,
			 * not the {@see \WP_Hook::apply_filters()} (thus, class must not present).
			 */
			if ( empty( $_['class'] ) && isset( $_['function'] ) && 'apply_filters' === $_['function'] ) {
				// Found. So, the next trace element is the caller.
				$next_stop = true;
			}
		}

		return false;
	}

	/**
	 * Returns the current URL.
	 * There is no method of getting the current URL in WordPress.
	 * Various snippets published on the Web use a combination of home_url and add_query_arg.
	 * However, none of them work when WordPress is installed in a subfolder.
	 * The method below looks valid. There is a theoretical chance of HTTP_HOST tampered, etc.
	 * However, the same line of code is used by the WordPress core,
	 * for example in {@see wp_admin_canonical_url()}, so we are going to use it, too
	 * *
	 * Note that #hash is always lost because it's a client-side parameter.
	 * We might add it using a JavaScript call.
	 */
	public static function current_url() {

		// Sanitize and give some defaults if unset for some reasons (WP-CLI, etc.)
		$http_host   = isset( $_SERVER['HTTP_HOST'] ) ? \wc_clean( \wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'localhost';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? \wc_clean( \wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return \set_url_scheme( 'http://' . $http_host . $request_uri );
	}

	/**
	 * Returns the value of the specified parameter in the HTTP GET
	 *
	 * @param string $name The parameter name.
	 *
	 * @return string
	 */
	public static function http_get( $name ) {
		// PHPCS: WordPress.Security.NonceVerification.Recommended is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		return empty( $_GET[ $name ] ) ? '' : \sanitize_title( \wp_unslash( $_GET[ $name ] ) );
	}

	/**
	 * Do we have a certain query parameter in the HTTP GET?
	 *
	 * @param string          $name   The parameter name.
	 * @param string|string[] $values The values to check.
	 *
	 * @return bool
	 */
	public static function is_http_get( $name, $values ) {
		$values = (array) $values;

		return in_array( self::http_get( $name ), $values, true );
	}

	/**
	 * Returns the value of the specified parameter in the HTTP POST
	 *
	 * @since 1.2.0
	 *
	 * @param string $name The parameter name.
	 *
	 * @return string
	 */
	public static function http_post( $name ) {
		// PHPCS: WordPress.Security.NonceVerification.Recommended is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		return isset( $_POST[ $name ] )
			? \sanitize_text_field( \wp_unslash( $_POST[ $name ] ) )
			: '';
	}

	/**
	 * Returns the value of the specified parameter in the HTTP POST array.
	 *
	 * @since 1.2.0
	 *
	 * @param string $name The parameter name.
	 * @param string $key  The array key.
	 *
	 * @return string
	 */
	public static function http_post_array( $name, $key ) {
		// PHPCS: WordPress.Security.NonceVerification.Recommended is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		return isset( $_POST[ $name ][ $key ] )
			? \sanitize_text_field( \wp_unslash( $_POST[ $name ][ $key ] ) )
			: '';
	}

	/**
	 * Returns sanitized $_SERVER['REQUEST_URI'].
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public static function request_uri() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			// Something abnormal.
			return '';
		}

		return \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}
}
