<?php
/**
 * Custom prices metas for simple products
 *
 * @since 2.0.4
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\CustomPrice;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\API;
use WOOMC\App;

/**
 * Class CustomPrice/Simple
 *
 * @package WOOMC\Admin\CustomPrice
 */
class Simple implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		// Place at the bottom of the General tab.
		\add_action(
			'woocommerce_product_options_general_product_data',
			array( $this, 'action__create' ),
			App::HOOK_PRIORITY_LATE
		);

		\add_action(
			'woocommerce_process_product_meta',
			array( $this, 'action__woocommerce_process_product_meta' )
		);

	}

	/**
	 * True if the product type is one of those we support.
	 *
	 * @since 2.7.2 Support Product Bundle.
	 *
	 * @param \WC_Product $product Product instance.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return $product
			   && ( $product instanceof \WC_Product_Simple || $product instanceof \WC_Product_Bundle );
	}

	/**
	 * Build array of meta keys depending on the product type.
	 *
	 * @param \WC_Product $product The product object.
	 *
	 * @return string[]
	 */
	protected function setup_keys( $product ) {

		$keys = array(
			'_regular_price_' => __( 'Regular price', 'woocommerce' ),
			'_sale_price_'    => __( 'Sale price', 'woocommerce' ),
		);

		/**
		 * Meta keys to keep custom pricing values.
		 *
		 * @param string[]    $keys    Array of meta keys.
		 * @param \WC_Product $product The product object.
		 *
		 * @return string[]
		 */
		$keys = \apply_filters( 'woocommerce_multicurrency_custom_pricing_meta_keys', $keys, $product );

		return $keys;
	}

	/**
	 * Introduction text printed above the metaboxes.
	 *
	 * @return void
	 */
	protected function create_intro() {

		echo '<h2>' . esc_html__( 'Multi-currency', 'woocommerce-multicurrency' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'To override the automatic price conversion for this product, put the fixed price values in the fields below.', 'woocommerce-multicurrency' ) . '</p>';

	}

	/**
	 * Add metaboxes to the Product Edit in admin.
	 *
	 * @return void
	 */
	public function action__create() {

		$product = \wc_get_product( false );
		if ( ! $this->is_my_product( $product ) ) {
			return;
		}

		$keys = $this->setup_keys( $product );
		if ( empty( $keys ) ) {
			return;
		}

		/**
		 * Same classes as the General tab pricing has.
		 *
		 * @since 2.5.3 Replace "pricing" with "woomc_pricing" because NYP hides it.
		 */
		echo '<div class="options_group woomc_pricing show_if_simple show_if_external hidden">';

		$this->create_intro();

		foreach ( API::extra_currencies() as $currency ) {
			echo '<p><strong>' . \esc_html( $currency ) . '</strong></p>';
			foreach ( $keys as $key => $label ) {
				$meta_key = "{$key}{$currency}";
				$id       = $meta_key;
				\woocommerce_wp_text_input(
					array(
						'id'            => $id,
						'wrapper_class' => 'woomc_custom_price',
						'label'         => $label,
						'data_type'     => 'price',
						'value'         => \wc_format_localized_price( $product->get_meta( $meta_key, true ) ),
						'type'          => 'text',
					)
				);
			}
		}

		echo '</div>';
	}

	/**
	 * Process metaboxes upon edit save.
	 *
	 * @see \WC_Meta_Box_Product_Data::save
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function action__woocommerce_process_product_meta( $post_id ) {

		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! \wp_verify_nonce( \wc_clean( \wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		$product = \wc_get_product( $post_id );

		$keys = $this->setup_keys( $product );
		if ( empty( $keys ) ) {
			return;
		}

		$meta_keys = array_keys( $keys );

		foreach ( API::extra_currencies() as $currency ) {
			foreach ( $meta_keys as $key ) {
				$meta_key = "{$key}{$currency}";

				$posted = Env::http_post( $meta_key );

				if ( $posted ) {
					$value = \wc_format_decimal( $posted );
					$product->update_meta_data( $meta_key, $value );
					if ( '_subscription_price_' === $key ) {
						// Update ALSO the regular price for this currency.
						$product->update_meta_data( '_regular_price_' . $currency, $value );
					}
				} else {
					$product->delete_meta_data( $meta_key );
					if ( '_subscription_price_' === $key ) {
						// Delete ALSO the regular price for this currency.
						$product->delete_meta_data( '_regular_price_' . $currency );
					}
				}
			}
		}

		$product->save();
	}
}
