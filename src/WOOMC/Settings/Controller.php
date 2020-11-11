<?php
/**
 * Settings controller.
 *
 * @since 1.0.0
 */

namespace WOOMC\Settings;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Rate\Storage;

/**
 * Class Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * The Rate Storage instance.
	 *
	 * @var  Storage
	 */
	protected $rate_storage;

	/**
	 * Controller constructor.
	 *
	 * @param Storage $rate_storage The Rate Storage instance.
	 */
	public function __construct( Storage $rate_storage ) {
		$this->rate_storage = $rate_storage;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {

			// The currencies data is needed to build some drop-down boxes.
			$fields = new Fields( $this->rate_storage );

			$panel = new Panel( $fields );
			$panel->setup_hooks();

			add_filter(
				'plugin_action_links_' . App::instance()->plugin_basename,
				array( $this, 'add_settings_link' )
			);
		}
	}

	/**
	 * Add the "Settings" link in the plugins list in Admin area.
	 *
	 * @param array $actions An array of plugin action links.
	 *
	 * @return array
	 *
	 * @internal Filter.
	 */
	public function add_settings_link( $actions ) {
		$settings_link =
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . Panel::TAB_SLUG ) ) . '">' .
			esc_html__( 'Settings' ) . '</a>';
		array_unshift( $actions, $settings_link );

		return $actions;
	}
}
