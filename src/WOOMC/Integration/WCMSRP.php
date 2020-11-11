<?php
/**
 * Integration.
 *
 * Plugin Name: WooCommerce MSRP Pricing
 * Plugin URI: https://woocommerce.com/products/msrp-pricing/
 *
 * @since 2.4.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\App;

/**
 * Class WCMSRP
 *
 * @package WOOMC\Integration
 */
class WCMSRP extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Convert MSRP value: filter tags.
		 *
		 * @var string[] $tags
		 */
		static $tags = array(

			/**
			 * Get the MSRP for a non-variable product
			 * <code>
			 * $current_product->get_meta( '_msrp_price' );
			 * </code>
			 *
			 * @see \woocommerce_msrp_frontend::get_msrp_for_single_product
			 */
			'woocommerce_product_get__msrp_price',

			/**
			 * Get the MSRP for a single variation.
			 * <code>
			 * $current_product->get_meta( '_msrp' );
			 * </code>
			 *
			 * @see \woocommerce_msrp_frontend::get_msrp_for_variation,
			 */
			'woocommerce_product_get__msrp',

			/**
			 * Product variation JS.
			 * <code>
			 * $variation->get_meta( '_msrp' );
			 * </code>
			 *
			 * @see \woocommerce_msrp_frontend::add_msrp_to_js
			 */
			'woocommerce_product_variation_get__msrp',
		);

		foreach ( $tags as $tag ) {
			\add_filter(
				$tag,
				array( $this->price_controller, 'convert' ),
				App::HOOK_PRIORITY_EARLY,
				2
			);
		}

		/**
		 * Convert MSRP value for variable product.
		 * <code>
		 * get_post_meta( $child_id, '_msrp', true );
		 * </code>
		 *
		 * @see \woocommerce_msrp_frontend::get_savings_for_variable_product
		 */
		\add_filter(
			'get_post_metadata',
			array( $this, 'filter__convert_for_variable_product' ),
			App::HOOK_PRIORITY_EARLY,
			4
		);
	}

	/**
	 * Filters whether to retrieve metadata of a specific type.
	 *
	 * The dynamic portion of the hook, `$meta_type`, refers to the meta
	 * object type (comment, post, term, or user). Returning a non-null value
	 * will effectively short-circuit the function.
	 *
	 * @see          \get_metadata()
	 *
	 * @param null|array|string $value     The value get_metadata() should return - a single metadata value,
	 *                                     or an array of values.
	 * @param int               $object_id ID of the object metadata is for.
	 * @param string            $meta_key  Metadata key.
	 * @param bool              $single    Whether to return only the first value of the specified $meta_key.
	 *
	 * @return array|string|null
	 */
	public function filter__convert_for_variable_product( $value, $object_id, $meta_key, $single ) {
		if ( '_msrp' === $meta_key ) {
			$value = $this->price_controller->convert( $this->retrieve_metadata( 'post', $object_id, $meta_key, $single ) );
		}

		return $value;

	}

	/**
	 * Retrieve metadata.
	 *
	 * The dynamic portion of the hook, `$meta_type`, refers to the meta
	 * object type (comment, post, term, or user). Returning a non-null value
	 * will effectively short-circuit the function.
	 *
	 * @see          \get_metadata()
	 *
	 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
	 *                          or any other object type with an associated meta table.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Metadata key.
	 * @param bool   $single    Whether to return only the first value of the specified $meta_key.
	 *
	 * @return array|string|null
	 */
	protected function retrieve_metadata( $meta_type, $object_id, $meta_key, $single ) {
		$meta_cache = \wp_cache_get( $object_id, $meta_type . '_meta' );

		if ( ! $meta_cache ) {
			$meta_cache = \update_meta_cache( $meta_type, array( $object_id ) );
			if ( isset( $meta_cache[ $object_id ] ) ) {
				$meta_cache = $meta_cache[ $object_id ];
			} else {
				$meta_cache = null;
			}
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset( $meta_cache[ $meta_key ] ) ) {
			if ( $single ) {
				return \maybe_unserialize( $meta_cache[ $meta_key ][0] );
			} else {
				return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
			}
		}

		if ( $single ) {
			return '';
		} else {
			return array();
		}
	}
}
