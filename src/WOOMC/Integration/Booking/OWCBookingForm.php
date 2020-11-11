<?php
/**
 * Override methods of the WC_Booking_Form class (legacy).
 *
 * @since 1.13.0
 *
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Booking;

/**
 * Class OWCBookingForm
 *
 * @package WOOMC\Integration\Booking
 */
class OWCBookingForm extends \WC_Booking_Form {

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
