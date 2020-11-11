<?php
/**
 * Override methods of the WC_Product_Booking class.
 *
 * @since 1.17.1
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Booking;

/**
 * Class OWCProductBooking
 *
 * @package WOOMC\Integration\Booking
 */
class OWCProductBooking extends \WC_Product_Booking {

	/**
	 * Always true.
	 * Need to skip validation when we recalculate the costs of booking in the cart.
	 *
	 * @see ABookingIntegration::action__woocommerce_before_calculate_totals()
	 *
	 * @param array $data The booking data.
	 *
	 * @return true
	 */
	public function is_bookable( $data ) {
		return true;
	}
}
