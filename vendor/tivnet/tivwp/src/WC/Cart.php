<?php
/**
 * WooCommerce Cart.
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\WC;

/**
 * Class Cart
 */
class Cart {

	/**
	 * Return true if there is at least one product of a certain type in the cart.
	 *
	 * @param string $product_type Product type.
	 *
	 * @return bool
	 */
	public static function is_product_type_in_cart( $product_type ) {

		foreach ( \WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			/**
			 * Get the product from Cart Item.
			 *
			 * @var \WC_Product $product
			 */
			$product = $cart_item['data'];

			if ( $cart_item['quantity'] > 0 && $product && $product->exists() && $product->is_type( $product_type ) ) {
				return true;
			}
		}

		return false;
	}
}
