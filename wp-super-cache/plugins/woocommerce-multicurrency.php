<?php
/**
 * Plugin for WP Super Cache.
 *
 * To enable, replace the value of `$wp_cache_plugins_dir` variable to:
 * $wp_cache_plugins_dir = WP_CONTENT_DIR . '/plugins/woocommerce-multicurrency/wp-super-cache/plugins';
 * in the wp-cache-config.php file
 *
 * @since 1.6.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

/**
 * Let Super Cache react on our cookie.
 *
 * @see \wp_cache_get_cookies_values()
 *
 * @param string $string The cookies already considered by Super Cache.
 *
 * @return string Returned with our cookie added.
 */
function woomc_super_cache( $string ) {
	$cookie_name = 'woocommerce_multicurrency_forced_currency';
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$string .= '|WOOMC|' . $_COOKIE[ $cookie_name ];
	}

	return $string;
}

/**
 * Prints information about this plugin in the Super Cache's "Plugins" admin tab.
 */
function woomc_super_cache_admin() {
	?>
	<h4><?php esc_html_e( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ); ?></h4>
	<p><?php esc_html_e( 'Provides support for multiple currencies in WooCommerce store by making different cache snapshots for different currencies.', 'woocommerce-multicurrency' ); ?></p>
	<label>
		<input type="radio" checked="checked"/>
		<?php esc_html_e( 'Enabled', 'wp-super-cache' ); ?>
	</label>

	<?php
}

// Hook our actions.
add_cacheaction( 'wp_cache_get_cookies_values', 'woomc_super_cache' );
add_cacheaction( 'cache_admin_page', 'woomc_super_cache_admin' );

// Include the bundled plugins.
$plugins = glob( WPCACHEHOME . 'plugins/*.php' );
if ( is_array( $plugins ) ) {
	foreach ( $plugins as $plugin ) {
		if ( is_file( $plugin ) ) {
			/** @noinspection PhpIncludeInspection */
			require_once $plugin;
		}
	}
}
