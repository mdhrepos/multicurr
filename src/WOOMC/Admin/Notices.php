<?php
/**
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use WOOMC\App;

/**
 * Class Notices
 *
 * @since   1.10.0
 *
 * @package WOOMC\Admin
 */
class Notices {

	/**
	 * Public access to the __CLASS__.
	 *
	 * @return string
	 */
	public static function get_class() {
		return __CLASS__;
	}

	/**
	 * Requirements not met.
	 */
	public static function requirements() {
		?>
		<div class="error">
			<p>
				<?php
				printf( /* Translators: %1$s - WooCommerce Multi-currency, %2$s - link to woocommerce.com */
					\esc_html( __( '%1$s requires %2$s version 3+ to be installed and active.', 'woocommerce-multicurrency' ) ),
					\esc_html__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ),
					'<a href="https://woocommerce.com" target="_blank">WooCommerce</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin activated.
	 *
	 * @since 1.15.0 Do not display this notice if requirements not met.
	 *
	 * @param string $url_settings      The settings URL.
	 * @param string $url_documentation The documentation URL.
	 */
	public static function activation( $url_settings, $url_documentation ) {
		if ( ! App::requirements_met() ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(/* translators: %s - WooCommerce Multi-currency. */
					\esc_html__( 'The %s extension is active. Please configure its settings.', 'woocommerce-multicurrency' ),
					\esc_html__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' )
				);
				?>
			</p>
			<p>
				<a href="<?php echo \esc_url( $url_settings ); ?>" class="button button-primary">
					<?php \esc_html_e( 'Settings' ); ?>
				</a>
				<a href="<?php echo \esc_url( $url_documentation ); ?>" class="button">
					<?php \esc_html_e( 'Documentation', 'woocommerce-multicurrency' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

}
