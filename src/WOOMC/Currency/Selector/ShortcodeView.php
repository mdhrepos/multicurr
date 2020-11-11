<?php
/**
 * View: Shortcode
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency\Selector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File: ShortcodeView.php
 *
 * @global string[] $currencies
 * @global string[] $woocommerce_currencies
 * @global string   $current_currency
 * @global string   $format
 * @global int      $flag
 */
?>
	<div class="<?php echo \esc_attr( Shortcode::TAG ); ?>-wrap">
		<select class="<?php echo \esc_attr( Shortcode::TAG ); ?>"
				data-flag="<?php echo $flag ? 1 : 0; ?>"
				aria-label="<?php echo \esc_attr_x( 'Currency', 'Widget', 'woocommerce-multicurrency' ); ?>">
			<?php foreach ( $currencies as $code ) : ?>
				<?php
				$option_text = str_replace(
					array(
						'{{code}}',
						'{{name}}',
						'{{symbol}}',
					),
					array(
						$code,
						$woocommerce_currencies[ $code ],
						\get_woocommerce_currency_symbol( $code ),
					),
					$format
				);
				?>
				<option value="<?php echo \esc_attr( $code ); ?>"<?php \selected( $code, $current_currency ); ?>><?php echo \esc_html( $option_text ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
<?php
