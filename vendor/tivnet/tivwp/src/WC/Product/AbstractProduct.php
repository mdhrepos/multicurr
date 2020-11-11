<?php
/**
 * Abstract product class.
 * Adds several methods to the standard WC Product.
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\WC\Product;

/**
 * Class AbstractProduct
 */
abstract class AbstractProduct extends \WC_Product_Simple {

	/**
	 * Product type. To be defined in the child classes.
	 *
	 * @var string
	 */
	const PRODUCT_TYPE = '';

	/**
	 * Setup product_type taxonomy.
	 *
	 * @return void
	 */
	public static function setup_product_type_taxonomy() {

		// If there is no product type taxonomy, add it.
		if ( ! \get_term_by( 'slug', static::PRODUCT_TYPE, 'product_type' ) ) {
			\wp_insert_term( static::PRODUCT_TYPE, 'product_type' );
		}
	}

	/**
	 * True if the product has been purchased by the current user.
	 *
	 * @return bool
	 */
	public function is_purchased_by_me() {

		$user = \wp_get_current_user();

		return ( $user->exists() && \wc_customer_bought_product( $user->user_email, $user->ID, $this->get_id() ) );
	}

	/**
	 * True if the product is in the cart.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Check cart existence to avoid fatal errors.
	 * @since 1.1.0 Do not rely on product_cart_id because it depends on
	 *        "other cart item data passed which affects this items uniqueness in the cart".
	 *        Loop through the cart to find the product_id.
	 * @return bool
	 */
	public function is_in_cart() {

		$cart = \WC()->cart;
		if ( ! $cart instanceof \WC_Cart ) {
			return false;
		}

		$my_id = $this->get_id();

		// Cache for one HTTP request.
		static $results = array();

		// If not cached yet.
		if ( ! isset( $results[ $my_id ] ) ) {
			// Default is not in cart.
			$results[ $my_id ] = false;

			foreach ( $cart->get_cart_contents() as $cart_item ) {
				if ( isset( $cart_item['product_id'] ) && $cart_item['product_id'] === $my_id ) {
					// Found!
					$results[ $my_id ] = true;
					break;
				}
			}
		}

		return $results[ $my_id ];
	}

	/**
	 * Get the checkout URL.
	 *
	 * @param bool $add_to_cart Set to true if the button should add the current product to the Cart.
	 *
	 * @return string
	 */
	public function get_checkout_url( $add_to_cart = false ) {

		return \wc_get_checkout_url() . ( $add_to_cart ? '?add-to-cart=' . $this->get_id() : '' );
	}
}
