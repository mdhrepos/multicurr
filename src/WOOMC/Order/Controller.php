<?php
/**
 * Order controller.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use TIVWP_140\Env;
use WOOMC\Rate;

/**
 * Class Order\Controller
 *
 * @package WOOMC\Order
 */
class Controller {

	/**
	 * Constructor.
	 *
	 * @param Rate\Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( $rate_storage ) {

		$meta = new Meta( $rate_storage );
		$meta->setup_hooks();

		if ( Env::in_wp_admin() ) {
			$report_page = new ReportPage( $rate_storage );
			$report_page->setup_hooks();
		}
	}
}
