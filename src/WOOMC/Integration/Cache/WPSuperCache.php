<?php
/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Cache;

use TIVWP_140\InterfaceHookable;
use WOOMC\Currency\Detector;

/**
 * Class WPSuperCache
 */
class WPSuperCache implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		do_action( 'wpsc_add_cookie', Detector::COOKIE_FORCED_CURRENCY );
	}
}
