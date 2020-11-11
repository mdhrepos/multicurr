<?php
/**
 * Edit currency of orders and subscriptions.
 *
 * @since   1.8.0
 */

namespace WOOMC\Admin;

use TIVWP_140\InterfaceHookable;
use WOOMC\DAO\Factory;

/**
 * Class EditCurrency
 */
class EditCurrency implements InterfaceHookable {

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'woomc_nonce_name_currency';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'woomc_nonce_action_currency';

	/**
	 * List of the admin screens where currency editing is applicable.
	 *
	 * @var string[]
	 */
	protected $screen = array(
		'shop_order',
		'shop_subscription',
	);

	/**
	 * List of fields for the metabox.
	 *
	 * @var array[]
	 */
	protected $meta_fields = array();

	/**
	 * EditCurrency constructor.
	 */
	public function __construct() {

		$this->meta_fields = array(
			array(
				'label'   => __( 'Currency', 'woocommerce' ),
				'id'      => '_order_currency',
				'type'    => 'select',
				'default' => \get_woocommerce_currency(),
				'options' => Factory::getDao()->getEnabledCurrencies(),
			),
		);

	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		\add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 * Add metaboxes.
	 *
	 * @internal action
	 */
	public function add_meta_boxes() {
		foreach ( $this->screen as $single_screen ) {
			\add_meta_box(
				'currency',
				__( 'Multi-currency options', 'woocommerce-multicurrency' ),
				array( $this, 'meta_box_callback' ),
				$single_screen,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Metabox callback.
	 *
	 * @param \WP_Post $post The Post object.
	 *
	 * @internal callback.
	 */
	public function meta_box_callback( $post ) {
		\wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$this->field_generator( $post );
	}

	/**
	 * Metabox content.
	 *
	 * @param \WP_Post $post The Post object.
	 */
	protected function field_generator( $post ) {
		$output = '';
		foreach ( $this->meta_fields as $meta_field ) {
			$label      = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';
			$meta_value = get_post_meta( $post->ID, $meta_field['id'], true );
			if ( empty( $meta_value ) ) {
				$meta_value = $meta_field['default'];
			}
			$input = sprintf(
				'<select id="%s" name="%s">',
				$meta_field['id'],
				$meta_field['id']
			);
			foreach ( $meta_field['options'] as $key => $value ) {
				$meta_field_value = ! is_numeric( $key ) ? $key : $value;

				/**
				 * Inspection %s.
				 *
				 * @noinspection HtmlUnknownAttribute
				 */
				$input .= sprintf(
					'<option %s value="%s">%s</option>',
					$meta_value === $meta_field_value ? 'selected' : '',
					$meta_field_value,
					$value
				);
			}
			$input .= '</select>';

			$output .= $this->format_rows( $label, $input );
		}

		$output .= $this->format_rows( '', __( 'Note: this will only change the currency, not the amounts!', 'woocommerce-multicurrency' ) );

		$allowed_tags = array(
			'option' => array(
				'selected' => true,
				'value'    => true,
			),
			'label'  => array( 'for' => true ),
			'select' => array(
				'id'   => true,
				'name' => true,
			),
			'tr'     => true,
			'th'     => true,
			'td'     => true,
		);

		echo '<table class="form-table"><tbody>' . \wp_kses( $output, $allowed_tags ) . '</tbody></table>';
	}

	/**
	 * Put values in a table row.
	 *
	 * @param string $th Content of the <th> tag.
	 * @param string $td Content of the <td> tag.
	 *
	 * @return string
	 */
	protected function format_rows( $th, $td ) {
		return '<tr><th>' . $th . '</th><td>' . $td . '</td></tr>';
	}

	/**
	 * Save the metabox fields.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @internal action.
	 */
	public function save_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! \wp_verify_nonce( \wc_clean( \wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		foreach ( $this->meta_fields as $meta_field ) {
			if ( isset( $_POST[ $meta_field['id'] ] ) ) {
				\update_post_meta( $post_id, $meta_field['id'], \sanitize_text_field( $_POST[ $meta_field['id'] ] ) );
			}
		}
	}
}
