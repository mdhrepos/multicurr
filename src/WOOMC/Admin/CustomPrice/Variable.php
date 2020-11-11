<?php
/**
 * Custom prices metas for variable products
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
 * Class CustomPrice/Variable
 *
 * @package WOOMC\Admin\CustomPrice
 */
class Variable implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action(
			'woocommerce_product_after_variable_attributes',
			array( $this, 'action__create' ),
			App::HOOK_PRIORITY_LATE,
			3
		);

		\add_action(
			'woocommerce_save_product_variation',
			array( $this, 'save_product_variation' ),
			App::HOOK_PRIORITY_LATE,
			2
		);

	}

	/**
	 * True if the product type is one of those we support.
	 *
	 * @param \WC_Product $product Product instance.
	 *
	 * @return bool
	 */
	protected function is_my_product( $product ) {
		return $product && $product instanceof \WC_Product_Variation;
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
	 * Add metaboxes to the Product Edit - Variations tab in admin.
	 *
	 * @param int      $loop           Position in the loop.
	 * @param array    $variation_data Variation data. (Unused).
	 * @param \WP_Post $variation      The product variation Post.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function action__create( $loop, $variation_data, $variation ) {

		$product = \wc_get_product( $variation );
		if ( ! $this->is_my_product( $product ) ) {
			return;
		}

		$keys = $this->setup_keys( $product );
		if ( empty( $keys ) ) {
			return;
		}

		echo '<div style="clear: both">';
		$this->create_intro();

		foreach ( API::extra_currencies() as $currency ) {
			echo '<p><strong>' . \esc_html( $currency ) . '</strong></p>';
			foreach ( $keys as $key => $label ) {
				$meta_key = "{$key}{$currency}";
				// Input ID has [loop] so we can save it to the proper variation when processing.
				$id = "{$meta_key}[{$loop}]";
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
	 * @param int $loop         Position in the loop of saving.
	 * @param int $variation_id Variation ID.
	 *
	 * @return void
	 */
	public function save_product_variation( $variation_id, $loop ) {

		\check_ajax_referer( 'save-variations', 'security' );

		$product = \wc_get_product( $variation_id );

		$keys = $this->setup_keys( $product );
		if ( empty( $keys ) ) {
			return;
		}

		$meta_keys = array_keys( $keys );

		foreach ( API::extra_currencies() as $currency ) {
			foreach ( $meta_keys as $key ) {
				$meta_key = "{$key}{$currency}";

				$posted = Env::http_post_array( $meta_key, $loop );

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
