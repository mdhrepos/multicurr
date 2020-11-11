<?php
/**
 * Orders Report Page
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use TIVWP_140\InterfaceHookable;
use WOOMC\Admin\Menu;
use WOOMC\App;
use WOOMC\Rate;

/**
 * Class ReportPage
 *
 * @package WOOMC\Order
 */
class ReportPage implements InterfaceHookable {

	/**
	 * Orders report menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG_ORDERS_REPORT = 'multicurrency-orders';

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
		\add_action( 'admin_menu', array( $this, 'action__admin_menu' ), App::HOOK_PRIORITY_LATE );
	}

	/**
	 * Add submenu.
	 */
	public function action__admin_menu() {

		// Add a submenu to the custom top-level menu.
		\add_submenu_page(
			Menu::SLUG_MAIN,
			\__( 'Orders', 'woocommerce' ),
			Menu::icon_tag( 'dashicons-list-view' ) . \__( 'Orders', 'woocommerce' ),
			Menu::CAPABILITY,
			self::MENU_SLUG_ORDERS_REPORT,
			array( $this, 'callback__page' )
		);

	}

	/**
	 * The page.
	 */
	public function callback__page() {

		if ( ! \current_user_can( Menu::CAPABILITY ) ) {
			\wp_die( \esc_html__( 'Sorry, you are not allowed to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2>
				<?php
				echo \esc_html__( 'Multi-currency', 'woocommerce-multicurrency' ) . ': ' . \esc_html__( 'Orders', 'woocommerce' );
				?>
			</h2>
			<?php
			$o = new ReportTable( $this->rate_storage );
			$o->output_report();
			?>
		</div>

		<?php
	}

}
