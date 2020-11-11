<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Product Vendors
 * Plugin URI: https://woocommerce.com/products/product-vendors/
 *
 * @since 1.12.0
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Log;
use WOOMC\Order\Meta;
use WOOMC\Price;

/**
 * Class Integration\WCProductVendors
 */
class WCProductVendors implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * The default currency.
	 *
	 * @var string
	 */
	protected $default_currency;

	/**
	 * WCProductVendors constructor.
	 *
	 * @param Price\Controller $price_controller The Price controller instance.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
		$this->default_currency = Factory::getDao()->getDefaultCurrency();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		add_filter(
			'woocommerce_order_get_items',
			array( $this, 'filter__woocommerce_order_get_items' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		add_action(
			'wc_product_vendors_email_order_meta',
			array( $this, 'action__wc_product_vendors_email_order_meta' ),
			App::HOOK_PRIORITY_LATE,
			4
		);

	}

	/**
	 * Shortcut: Convert from order currency to the base store currency.
	 *
	 * @since 1.12.0
	 * @since 1.16.0 Use raw conversion.
	 *
	 * @param float|int|string $value          The value to convert.
	 * @param string           $order_currency The order currency code.
	 *
	 * @return float|int|string
	 */
	protected function convert_to_store_currency( $value, $order_currency ) {
		return $this->price_controller->convert_raw( $value, null, $this->default_currency, $order_currency );
	}

	/**
	 * When an order is processed by {@see \WC_Product_Vendors_Order::process},
	 * the list of items in the order is retrieved, the commission is calculated and
	 * inserted to the table by {@see \WC_Product_Vendors_Commission::insert}.
	 * This filter converts all amounts in the order items to the Store base currency, so that
	 * the commission is calculated in the Store currency.
	 *
	 * @since    1.12.0
	 *
	 * @param \WC_Order_Item_Product[] $items The products in Order.
	 * @param \WC_Order                $order The Order object.
	 *
	 * @return \WC_Order_Item_Product[]
	 *
	 * @internal filter
	 */
	public function filter__woocommerce_order_get_items( $items, $order ) {

		$order_currency = $order->get_currency();
		if (
			count( $items )
			&& $this->default_currency !== $order_currency
			&& Env::is_function_in_backtrace( array( 'WC_Product_Vendors_Order', 'process' ) )
		) {

			Log::debug(
				array(
					'Processing order ' . $order->get_id(),
					'Order currency: ' . $order_currency,
					'Store currency: ' . $this->default_currency,
					__METHOD__,
					__LINE__,
				)
			);

			foreach ( $items as $product ) {

				if ( ! $product instanceof \WC_Order_Item_Product ) {
					/** Exclude other order items, such as {@see \WC_Order_Item_Shipping} */
					continue;
				}

				/**
				 * Convert amounts to Store Currency (_sc).
				 */
				$subtotal        = $product->get_subtotal();
				$subtotal_sc     = $this->convert_to_store_currency( $subtotal, $order_currency );
				$subtotal_tax    = $product->get_subtotal_tax();
				$subtotal_tax_sc = $this->convert_to_store_currency( $subtotal_tax, $order_currency );
				$total           = $product->get_total();
				$total_sc        = $this->convert_to_store_currency( $total, $order_currency );
				$total_tax       = $product->get_total_tax();
				$total_tax_sc    = $this->convert_to_store_currency( $total_tax, $order_currency );

				/**
				 * Replace product totals with converted values.
				 */
				$product->set_subtotal( $subtotal_sc );
				$product->set_total( $total_sc );
				$product->set_subtotal_tax( $subtotal_tax_sc );
				$product->set_total_tax( $total_tax_sc );

				Log::debug( 'Processing product ' . $product->get_id() );
				Log::debug( array( 'Subtotal', $subtotal, $subtotal_sc ) );
				Log::debug( array( 'Subtotal Tax', $subtotal_tax, $subtotal_tax_sc ) );
				Log::debug( array( 'Total', $total, $total_sc ) );
				Log::debug( array( 'Total Tax', $total_tax, $total_tax_sc ) );
			}
		}

		return $items;
	}

	/**
	 * Add conversion information to the email sent to Vendor.
	 *
	 * @since 1.16.0
	 *
	 * @param \WC_Order                                 $order         The order object.
	 * @param bool                                      $sent_to_admin Is sent to admin.
	 * @param bool                                      $plain_text    Is plain text email.
	 * @param \WC_Product_Vendors_Order_Email_To_Vendor $email         The email object.
	 */
	public function action__wc_product_vendors_email_order_meta(
		$order,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$sent_to_admin,
		$plain_text,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$email
	) {

		if ( $plain_text ) {
			return;
		}

		$order_currency = $order->get_currency();
		$rate           = $order->get_meta( Meta::PREFIX . 'rate' );
		$store_currency = $order->get_meta( Meta::PREFIX . 'store_currency' );

		$currency_names = get_woocommerce_currencies();
		?>
		<p>
			<?php
			/* translators: %1$s - the currency symbol, %2$s - the currency name */
			echo esc_html( sprintf( __( 'The commission is calculated in: %1$s (%2$s).', 'woocommerce-multicurrency' ), $store_currency, $currency_names[ $store_currency ] ) );
			?>
		</p>
		<p>
			<?php
			/* translators: %1$s - the currency symbol, %2$s - the currency name */
			echo esc_html( sprintf( __( 'The order was paid in %1$s (%2$s).', 'woocommerce-multicurrency' ), $order_currency, $currency_names[ $order_currency ] ) );
			?>
		</p>
		<p>
			<?php
			echo esc_html( __( 'Payment method:', 'woocommerce' ) . ' ' . $order->get_payment_method_title() );
			?>
		</p>
		<p>
			<?php
			/* translators: %f - the currency exchange rate */
			echo esc_html( sprintf( __( 'The currency exchange rate was: %f.', 'woocommerce-multicurrency' ), $rate ) );
			?>
		</p>
		<?php

	}
}
