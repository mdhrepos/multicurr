<?php
/**
 * Order metas.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use TIVWP_140\InterfaceHookable;
use WOOMC\Log;
use WOOMC\Rate;

/**
 * Class Meta
 *
 * @package WOOMC\Order
 */
class Meta implements InterfaceHookable {

	/**
	 * Meta keys prefix.
	 *
	 * @var string
	 */
	const PREFIX = '_woomc_';

	/**
	 * DI.
	 *
	 * @var Rate\Storage
	 */
	protected $rate_storage;

	/**
	 * Meta constructor.
	 *
	 * @param Rate\Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( $rate_storage ) {
		$this->rate_storage = $rate_storage;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action(
			'woocommerce_checkout_update_order_meta',
			array( $this, 'action__woocommerce_checkout_update_order_meta' )
		);

	}

	/**
	 * Add order metas at checkout.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @internal action.
	 */
	public function action__woocommerce_checkout_update_order_meta( $order_id ) {

		try {

			$store_currency = \get_option( 'woocommerce_currency' );
			$order_currency = \get_post_meta( $order_id, '_order_currency', true );
			$rate           = $this->rate_storage->get_rate( $store_currency, $order_currency );

			\update_post_meta( $order_id, self::PREFIX . 'store_currency', $store_currency );
			\update_post_meta( $order_id, self::PREFIX . 'rate', $rate );
		} catch ( \Exception $exception ) {
			Log::error( $exception );
		}
	}

}
