<?php
/**
 * Integration controller.
 *
 * @since 2.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;

/**
 * Class Controller
 *
 * @package WOOMC\Integration
 */
class Controller extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Subscriptions have filter for both front and admin
		 *
		 * @since 2.2.0
		 */
		$this->subscription_extensions();

		if ( Env::in_wp_admin() ) {
			$this->setup_gateways_hooks();
		}

		if ( Env::on_front() ) {
			$this->booking_extensions();
			$this->shipping_methods();
			$this->various_extensions();
		}
	}

	/**
	 * Hooks for payment gateways.
	 * Separate from other integration because need to run them also in admin.
	 *
	 * @since 1.18.0
	 */
	protected function setup_gateways_hooks() {

		/**
		 * Plugin Name: WooCommerce Stripe Gateway
		 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-stripe/
		 *
		 * @since 1.18.0
		 */
		if ( class_exists( 'WC_Stripe', false ) ) {
			$stripe = new Gateways\WCStripe();
			$stripe->setup_hooks();
		}

		/**
		 * Standard PayPal gateway.
		 * (Always exists, no need to check for class presence).
		 *
		 * @since 1.18.0
		 */
		$paypal = new Gateways\WCGatewayPaypal();
		$paypal->setup_hooks();

	}

	/**
	 * Various.
	 */
	protected function various_extensions() {

		/**
		 * Plugin Name: WooCommerce Mix and Match
		 * Plugin URI: https://woocommerce.com/products/woocommerce-mix-and-match-products/
		 *
		 * @since 1.16.0
		 */
		if ( class_exists( 'WC_Mix_and_Match', false ) ) {
			$mix_and_match = new WCMixAndMatch( $this->price_controller );
			$mix_and_match->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Customer Specific Pricing
		 * Plugin URI:https://wisdmlabs.com/woocommerce-user-specific-pricing-extension/
		 *
		 * @since 1.19.0
		 */
		if ( defined( 'CSP_VERSION' ) && \is_user_logged_in() ) {
			$wccsp = new WCCSP( $this->price_controller );
			$wccsp->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Product Add-ons.
		 * Plugin URI: https://woocommerce.com/products/product-add-ons/
		 *
		 * @since 1.6.0
		 */
		if ( class_exists( 'WC_Product_Addons', false ) ) {
			$product_addons = new WCProductAddons( $this->price_controller );
			$product_addons->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Name Your Price
		 * Plugin URI: https://woocommerce.com/products/name-your-price/
		 *
		 * @since 1.11.0
		 */
		if ( class_exists( 'WC_Name_Your_Price', false ) ) {
			$name_your_price = new WCNameYourPrice( $this->price_controller );
			$name_your_price->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Dynamic Pricing
		 * Plugin URI: https://woocommerce.com/products/dynamic-pricing/
		 *
		 * @since 1.10.0
		 */
		if ( class_exists( 'WC_Dynamic_Pricing', false ) ) {
			$dynamic_pricing = new WCDynamicPricing( $this->price_controller );
			$dynamic_pricing->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Product Vendors
		 * Plugin URI: https://woocommerce.com/products/product-vendors/
		 *
		 * @since 1.12.0
		 */
		if ( class_exists( 'WC_Product_Vendors', false ) ) {
			$product_vendors = new WCProductVendors( $this->price_controller );
			$product_vendors->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Checkout Add-Ons
		 * Plugin URI: https://woocommerce.com/products/woocommerce-checkout-add-ons/
		 *
		 * @since 1.13.0
		 * @since 1.14.0 Check also for version 2's \WC_Checkout_Add_Ons_Loader class.
		 */
		if ( class_exists( 'WC_Checkout_Add_Ons', false ) || class_exists( 'WC_Checkout_Add_Ons_Loader', false ) ) {
			$checkout_add_ons = new WCCheckoutAddOns( $this->price_controller );
			$checkout_add_ons->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Measurement Price Calculator
		 * Plugin URI: http://www.woocommerce.com/products/measurement-price-calculator/
		 *
		 * @since 1.18.0
		 */
		if ( class_exists( 'WC_Measurement_Price_Calculator', false ) ) {
			$measurement_price_calculator = new WCMeasurementPriceCalculator( $this->price_controller );
			$measurement_price_calculator->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Payment Gateway Based Fees
		 * Plugin URI: https://www.woothemes.com/products/payment-gateway-based-fees/
		 *
		 * @since 1.19.0
		 * @note  if ( class_exists( 'WC_Add_Fees', false ) ) does not work.
		 */
		if ( function_exists( 'wc_add_fees_load_plugin_version' ) ) {
			$wc_add_fees = new WCAddFees( $this->price_controller );
			$wc_add_fees->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Gravity Forms Product Add-Ons
		 * Plugin URI: http://woothemes.com/products/gravity-forms-add-ons/
		 *
		 * @since 2.0.0
		 */
		if ( class_exists( 'WC_GFPA_Main', false ) ) {
			$wcgfpa = new WCGFPA( $this->price_controller );
			$wcgfpa->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce MSRP Pricing
		 * Plugin URI: https://woocommerce.com/products/msrp-pricing/
		 *
		 * @since 2.4.0
		 */
		if ( class_exists( 'woocommerce_msrp_frontend', false ) ) {
			$wcmsrp = new WCMSRP( $this->price_controller );
			$wcmsrp->setup_hooks();
		}

	}

	/**
	 * Subscriptions.
	 */
	protected function subscription_extensions() {

		/**
		 * Plugin Name: WooCommerce Subscriptions
		 * Plugin URI: https://woocommerce.com/products/woocommerce-subscriptions/
		 *
		 * @since 1.3.0
		 */
		if ( class_exists( 'WC_Subscriptions', false ) ) {
			$wc_subscriptions = new WCSubscriptions( $this->price_controller );
			$wc_subscriptions->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce All Products For Subscriptions
		 * Plugin URI: https://woocommerce.com/product/all-products-for-woocommerce-subscriptions
		 *
		 * @since 1.4.0
		 */
		if ( class_exists( 'WCS_ATT', false ) ) {
			$wc_apfs = new WCAPFS( $this->price_controller );
			$wc_apfs->setup_hooks();
		}
	}

	/**
	 * Booking.
	 */
	protected function booking_extensions() {

		/**
		 * Plugin Name: WooCommerce Bookings.
		 * Plugin URI: https://woocommerce.com/products/woocommerce-bookings/
		 *
		 * @note  DO NOT MOVE.
		 *
		 * @since 1.3.0
		 * @since 1.13.0 - Moved to a separate class.
		 */
		if ( class_exists( 'WC_Bookings', false ) ) {
			$bookings = new Booking\WCBookings( $this->price_controller );
			$bookings->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Accommodation Bookings
		 * Plugin URI: https://woocommerce.com/products/woocommerce-accommodation-bookings/
		 *
		 * @since 1.13.0
		 */
		if ( class_exists( 'WC_Accommodation_Booking', false ) ) {
			$accommodation_booking = new Booking\WCAccommodationBooking( $this->price_controller );
			$accommodation_booking->setup_hooks();
		}

	}

	/**
	 * Shipping methods.
	 */
	protected function shipping_methods() {
		/**
		 * Plugin Name: WooCommerce Table Rate Shipping
		 * Plugin URI: https://woocommerce.com/products/table-rate-shipping/
		 *
		 * @since 1.8.0
		 */
		if ( class_exists( 'WC_Table_Rate_Shipping', false ) ) {
			$table_rate = new Shipping\TableRate( $this->price_controller );
			$table_rate->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce USPS Shipping.
		 * Plugin URI: https://woocommerce.com/products/usps-shipping-method/
		 *
		 * @since 1.8.0
		 */
		if ( class_exists( 'WC_USPS', false ) ) {
			$usps = new Shipping\USPS( $this->price_controller );
			$usps->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Royal Mail
		 * Plugin URI: https://woocommerce.com/products/royal-mail/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_RoyalMail', false ) ) {
			$royal_mail = new Shipping\RoyalMail( $this->price_controller );
			$royal_mail->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Canada Post Shipping
		 * Plugin URI: https://woocommerce.com/products/canada-post-shipping-method/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_Shipping_Canada_Post_Init', false ) ) {
			$canada_post = new Shipping\CanadaPost( $this->price_controller );
			$canada_post->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce Australia Post Shipping
		 * Plugin URI: https://woocommerce.com/products/australia-post-shipping-method/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_Shipping_Australia_Post_Init', false ) ) {
			$canada_post = new Shipping\AustraliaPost( $this->price_controller );
			$canada_post->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce FedEx Shipping
		 * Plugin URI: https://woocommerce.com/products/fedex-shipping-module/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_Shipping_Fedex_Init', false ) ) {
			$fedex = new Shipping\FedEx( $this->price_controller );
			$fedex->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce UPS Shipping
		 * Plugin URI: https://woocommerce.com/products/ups-shipping-method/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_Shipping_UPS_Init', false ) ) {
			$ups = new Shipping\UPS( $this->price_controller );
			$ups->setup_hooks();
		}

		/**
		 * Plugin Name: WooCommerce PostNL
		 * Plugin URI: http://www.woocommerce.com/products/woocommerce-shipping-postnl/
		 *
		 * @since 1.9.0
		 */
		if ( class_exists( 'WC_PostNL', false ) ) {
			$ups = new Shipping\UPS( $this->price_controller );
			$ups->setup_hooks();
		}
	}
}
