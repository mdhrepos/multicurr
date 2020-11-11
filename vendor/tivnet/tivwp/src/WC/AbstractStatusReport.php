<?php
/**
 * Status Report abstract class.
 *
 * @since   1.2.0
 */

namespace TIVWP_140\WC;

use TIVWP_140\InterfaceHookable;

/**
 * Class StatusReport
 */
abstract class AbstractStatusReport implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_action( 'woocommerce_system_status_report', array( $this, 'action__woocommerce_system_status_report' ) );
	}

	/**
	 * Render the report.
	 *
	 * @internal action.
	 */
	abstract public function action__woocommerce_system_status_report();

	/**
	 * Do the report.
	 *
	 * @param string $label         Report section name.
	 * @param string $option_prefix Common prefix in the options table.
	 * @param array  $additional    Additional KV to include into the report.
	 * @param string $exclude       Exclude some keys.
	 */
	protected function do_report( $label, $option_prefix, $additional = array(), $exclude = '%credentials%' ) {

		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		$option_like = $option_prefix . '%';

		/**
		 * Retrieve all our options except for the excluded.
		 *
		 * @noinspection SqlResolve
		 */
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s AND option_name NOT LIKE %s ORDER BY option_name", $option_like, $exclude ), ARRAY_A );

		// Convert results to key-value.
		$options = array();
		foreach ( $results as $result ) {
			// Strip option prefix for brevity.
			$option_name = str_replace( $option_prefix, '', $result['option_name'] );

			$option_value = $result['option_value'];

			// Dash instead of empty strings.
			if ( empty( $option_value ) ) {
				$option_value = '-';
			}

			$options[ $option_name ] = $option_value;
		}

		// Add to the report some info.
		$options['store_country_state'] = WCEnv::store_country_state();
		$options['admin_email']         = \get_option( 'admin_email', '' );

		// Add to the report info about the current user - for communication.
		$current_user = \wp_get_current_user();
		if ( $current_user ) {
			$options['reporter_name']  = $current_user->display_name;
			$options['reporter_email'] = $current_user->user_email;
		}

		// Add to the report what's passed as a parameter.
		$options = array_merge( $additional, $options );

		$options = \apply_filters( 'tivwp_wc_status_report_options', $options );

		ksort($options);

		?>
		<!--suppress HtmlDeprecatedAttribute -->
		<table class="wc_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="<?php echo \esc_attr( $label ); ?>">
					<h2><?php echo \esc_html( $label ); ?></h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $options as $option_name => $option_value ) : ?>
				<tr>
					<td data-export-label="<?php echo \esc_attr( $option_name ); ?>"><?php echo \esc_html( $option_name ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo \esc_html( $option_value ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
