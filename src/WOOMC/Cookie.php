<?php
/**
 * Cookie management.
 *
 * @since 2.6.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

/**
 * Class Cookie
 */
class Cookie {

	/**
	 * Set the cookie.
	 *
	 * @since 2.6.3-rc.2 Do not set JS cookie if the page is cached in browser. Use JS cookies optionally.
	 * @since 2.6.7-beta.2 Added $force parameter.
	 *
	 * @param string     $name    Name.
	 * @param string|int $value   Value.
	 * @param int|float  $max_age Expiration.
	 * @param bool       $force   Allow repeated setcookie calls.
	 *
	 * @return void
	 */
	public static function set( $name, $value, $max_age = YEAR_IN_SECONDS, $force = false ) {

		/**
		 * Prevent multiple {@see setcookie()} calls.
		 *
		 * @var bool
		 */
		static $already_done = array();

		$client_side_cookies = DAO\Factory::getDao()->isClientSideCookies();

		if (
			! headers_sent()
			&& ( $force || ! isset( $already_done[ $name ] ) )
		) {

			if ( $client_side_cookies ) {

				// Cookies set by server do not work for some reason (disabled by hosting, etc.).
				// Note: this breaks caching that uses mod_rewrite to serve pages from disk.

				\add_action( 'wp_print_scripts', function () use ( $name, $value, $max_age ) {
					?>
					<script>
						document.addEventListener("DOMContentLoaded", function () {
							if (performance.getEntriesByType("navigation")[0].transferSize > 0) {
								document.cookie = "<?php echo \esc_js( $name ); ?>=<?php echo \esc_js( $value ); ?>;path=/;max-age=<?php echo \esc_js( $max_age ); ?>;samesite=strict";
							}
						});
					</script>
					<?php
				} );

			} elseif ( ! headers_sent() ) {

				// Standard cookie.
				$expires  = time() + $max_age;
				$path     = '/';
				$domain   = '';
				$secure   = false;
				$httponly = false;
				$samesite = 'strict';


				if ( PHP_VERSION_ID >= 70300 ) {
					// SameSite works starting from PHP 7.3.
					setcookie(
						$name,
						$value,
						array(
							'expires'  => $expires,
							'path'     => $path,
							'domain'   => $domain,
							'secure'   => $secure,
							'HttpOnly' => $httponly,
							'SameSite' => $samesite,
						)
					);
				} else {
					setcookie(
						$name,
						$value,
						$expires,
						$path,
						$domain,
						$secure,
						$httponly
					);
				}

			}

			$_COOKIE[ $name ]      = $value;
			$already_done[ $name ] = true;
		}
	}
}
