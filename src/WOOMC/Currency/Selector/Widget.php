<?php
/**
 * Currency selector widget.
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency\Selector;

use TIVWP_140\InterfaceHookable;

/**
 * Class Widget
 */
class Widget extends \WP_Widget implements InterfaceHookable {

	/**
	 * Widget constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget-' . Shortcode::TAG,
			'description'                 => _x( 'Drop-down currency selector.', 'Widget', 'woocommerce-multicurrency' ),
			'customize_selective_refresh' => true,
		);

		parent::__construct(
			Shortcode::TAG . '-widget',
			_x( 'Currency Selector', 'Widget', 'woocommerce-multicurrency' ),
			$widget_ops
		);
	}

	/**
	 * Default arguments.
	 *
	 * @since 1.17.0 Moved to a separate method. Added `flag`.
	 * @return array
	 */
	protected function default_args() {
		return array(
			'title'  => _x( 'Currency', 'Widget', 'woocommerce-multicurrency' ),
			'format' => Shortcode::DEFAULT_FORMAT,
			'flag'   => 0,
		);
	}

	/**
	 * Widget front-end.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		// Defaults.
		$instance = wp_parse_args( (array) $instance, $this->default_args() );

		$title  = apply_filters( 'widget_title', sanitize_text_field( $instance['title'] ) );
		$format = sanitize_text_field( $instance['format'] );
		$flag   = $instance['flag'] ? 1 : 0;

		// Before and after widget arguments are defined by themes.
		echo \wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo \wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		// Run the code and display the output.
		echo do_shortcode( '[' . Shortcode::TAG . ' format="' . $format . '" flag="' . $flag . '"]' );

		echo \wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Widget back-end.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {

		// Defaults.
		$instance = wp_parse_args( (array) $instance, $this->default_args() );

		$title  = sanitize_text_field( $instance['title'] );
		$format = sanitize_text_field( $instance['format'] );
		$flag   = $instance['flag'] ? 1 : 0;

		// Widget admin form.
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
			<br/>
			<!--suppress XmlDefaultAttributeValue -->
			<input
					class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php echo esc_html_x( 'Display format:', 'Widget', 'woocommerce-multicurrency' ); ?></label>
			<br/>
			<?php
			/**
			 * The "format" input INTENTIONALLY does not have `type="text"`.
			 * 1. WPGlobus does not add its Globe icon to translate this field.
			 * 2. WP does not show its content adjacent to the widget title (bug?).
			 */
			?>
			<input
					class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>"
					value="<?php echo esc_attr( $format ); ?>"/>
			<br/>
			<small><?php echo esc_html_x( 'Example:', 'Widget', 'woocommerce-multicurrency' ); ?><?php echo esc_attr( Shortcode::DEFAULT_FORMAT ); ?></small>
		</p>
		<p>
			<input
					type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'flag' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'flag' ) ); ?>"<?php checked( $flag ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'flag' ) ); ?>"><?php echo esc_html_x( 'Show flag', 'Widget', 'woocommerce-multicurrency' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Updating widget replacing old instances with new.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['format'] = ! empty( $new_instance['format'] ) ? sanitize_text_field( $new_instance['format'] ) : Shortcode::DEFAULT_FORMAT;
		$instance['flag']   = ! empty( $new_instance['flag'] ) ? (bool) $new_instance['flag'] : false;

		return $instance;
	}

	/**
	 * Register and load the widget.
	 */
	public function register() {
		register_widget( __CLASS__ );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		add_action( 'widgets_init', array( $this, 'register' ) );
	}
}
