<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Dynamic Pricing
 * Plugin URI: https://woocommerce.com/products/dynamic-pricing/
 *
 * @since 1.10.0
 */

namespace WOOMC\Integration;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Price;
use WOOMC\Product\Info;


/**
 * Class Integration\WCDynamicPricing
 */
class WCDynamicPricing implements InterfaceHookable {

	/**
	 * Convert amounts only flag.
	 *
	 * @var string
	 */
	const CONVERT_AMOUNTS_ONLY = 'convert_amounts_only';

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The Price controller instance.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Shortcut to {@see \WOOMC\Price\Controller::convert()}
	 *
	 * @param float|int|string $price The price to convert.
	 *
	 * @return float|int|string
	 *
	 * @internal filter
	 */
	public function convert( $price ) {
		return $this->price_controller->convert( $price );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! Env::in_wp_admin() ) {

			// Convert the price used by WC Dynamic Prices as the base for calculations.
			\add_filter(
				'woocommerce_dynamic_pricing_get_price_to_discount',
				array( $this, 'filter__woocommerce_dynamic_pricing_get_price_to_discount' ),
				App::HOOK_PRIORITY_EARLY,
				3
			);

			// Convert the rules in store-wide adjustment sets when modules loaded.
			1 && \add_filter( 'wc_dynamic_pricing_load_modules', array( $this, 'filter__load_modules' ) );

			// Alternatively: Use a filter that passes module's $this to convert the rules in adjustment sets.
			0 && \add_filter(
				'woocommerce_dynamic_pricing_process_product_discounts',
				array( $this, 'convert_adjustment_sets' ),
				App::HOOK_PRIORITY_EARLY,
				5
			);

			// Convert the rules in the individual products: 1.
			\add_filter(
				'wc_dynamic_pricing_get_product_pricing_rule_sets',
				array(
					$this,
					'convert_sets_amounts_only',
				),
				App::HOOK_PRIORITY_EARLY
			);

			// 2.
			\add_filter(
				'dynamic_pricing_product_rules',
				array(
					$this,
					'convert_sets_amounts_only',
				),
				App::HOOK_PRIORITY_EARLY
			);

			/**
			 * Additional filter for the https://github.com/lucasstark/woocommerce-dynamic-pricing-table plugin.
			 *
			 * @see   \WC_Dynamic_Pricing_Table::output_dynamic_pricing_table
			 * @since 1.14.0
			 */
			\add_filter(
				'woocommerce_dynamic_pricing_table_get_filtered_rules',
				array(
					$this,
					'convert_sets_amounts_only',
				),
				App::HOOK_PRIORITY_EARLY
			);

			// --- Do not need: covered by sets conversion.
			// add_filter( 'woocommerce_dynamic_pricing_get_rule_amount', function ( $amount ) {
			// return $amount;
			// } );

			// --- To check.
			// add_filter( 'wc_dynamic_pricing_get_cart_item_pricing_rule_sets', function ( $pricing_rule_sets ) {
			// return $pricing_rule_sets;
			// } );

		}
	}

	/**
	 * Convert array of sets, amounts only, not changing `from-to`.
	 *
	 * @param array|string|\Traversable $sets Array of rulesets of empty string iа product does not have rules.
	 *
	 * @return array
	 *
	 * @internal filter
	 */
	public function convert_sets_amounts_only( $sets ) {
		if ( is_array( $sets ) || ( is_object( $sets ) && $sets instanceof \Traversable ) ) {
			foreach ( $sets as &$set ) {
				$this->convert_set( $set, self::CONVERT_AMOUNTS_ONLY );
			}
		}

		return $sets;
	}

	/**
	 * Convert the rules in adjustment sets.
	 *
	 * @param bool                              $is_true        Pass-through unchanged.
	 * @param \WC_Product                       $cart_item_data Unused.
	 * @param string                            $module_id      Type of the module.
	 * @param \WC_Dynamic_Pricing_Advanced_Base $module         Module object.
	 * @param array                             $cart_item      Unused.
	 *
	 * @return bool
	 *
	 * @internal filter.
	 */
	public function convert_adjustment_sets(
		$is_true,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$cart_item_data,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$module_id,
		$module,
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$cart_item
	) {

		$this->convert_module( $module );

		return $is_true;
	}

	/**
	 * Convert set of rules.
	 *
	 * @param array|\stdClass $set       The set of Rules.
	 * @param string          $module_id Type of the module.
	 */
	protected function convert_set( &$set, $module_id ) {

		if ( is_array( $set ) ) {

			// Flag to prevent repeated conversions.
			if ( isset( $set['woomc_converted'] ) ) {
				return;
			}

			if ( ! empty( $set['rules'] ) ) {
				$this->convert_rules( $set['rules'], $module_id );
			}
			if ( ! empty( $set['blockrules'] ) ) {
				$this->convert_rules( $set['blockrules'], $module_id );
			}

			$set['woomc_converted'] = true;
		}

		if ( is_object( $set ) && ! empty( $set->pricing_rules ) ) {

			// Flag to prevent repeated conversions.
			if ( isset( $set->woomc_converted ) ) {
				return;
			}

			$this->convert_rules( $set->pricing_rules, $module_id );

			$set->woomc_converted = true;
		}

	}

	/**
	 * Convert array or rules.
	 *
	 * @param array[] $rules     The rules.
	 * @param string  $module_id Type of the module.
	 */
	protected function convert_rules( &$rules, $module_id ) {
		foreach ( $rules as &$rule ) {
			$this->convert_rule( $rule, $module_id );
		}
	}

	/**
	 * Convert rule.
	 *
	 * @param array  $rule      The rule: from, to, amount, etc.
	 * @param string $module_id Type of the module.
	 */
	protected function convert_rule( &$rule, $module_id ) {
		// In categories, from and to are quantities, so do not convert.
		if ( ! in_array(
			$module_id,
			array(
				'simple_category',
				'advanced_category',
				'simple_product',
				self::CONVERT_AMOUNTS_ONLY,
			),
			true
		)
		) {
			if ( isset( $rule['from'] ) ) {
				$rule['from'] = $this->convert( $rule['from'] );
			}
			if ( isset( $rule['to'] ) ) {
				$rule['to'] = $this->convert( $rule['to'] );
			}
		}

		// Example: Simple Category - available_advanced_rulesets - set - blockrules.
		if ( isset( $rule['adjust'] ) ) {
			$rule['adjust'] = $this->convert( $rule['adjust'] );
		}

		if ( in_array(
			$rule['type'],
			array(
				'price_discount',
				'fixed_product',
				'fixed_price',
				'fixed_adjustment',
			),
			true
		)
		) {
			$rule['amount'] = $this->convert( $rule['amount'] );
		}
	}

	/**
	 * Convert modules when loaded.
	 *
	 * @param \WC_Dynamic_Pricing_Advanced_Base[] $modules Module objects.
	 *
	 * @return \WC_Dynamic_Pricing_Advanced_Base[]
	 */
	public function filter__load_modules( $modules ) {
		foreach ( $modules as &$module ) {
			$this->convert_module( $module );
		}

		return $modules;
	}

	/**
	 * Convert module.
	 *
	 * @param \WC_Dynamic_Pricing_Advanced_Base|\WC_Dynamic_Pricing_Advanced_Totals $module Module object.
	 *
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
	 */
	protected function convert_module( &$module ) {
		if ( ! empty( $module->available_advanced_rulesets ) ) {
			foreach ( $module->available_advanced_rulesets as &$set ) {
				$this->convert_set( $set, $module->module_id );
			}
		}

		if ( ! empty( $module->available_rulesets ) ) {
			foreach ( $module->available_rulesets as &$set ) {
				$this->convert_set( $set, $module->module_id );
			}
		}

		if ( ! empty( $module->adjustment_sets ) ) {
			foreach ( $module->adjustment_sets as &$set ) {
				$this->convert_set( $set, $module->module_id );
			}
		}
	}

	/**
	 * Filter the "price to discount".
	 *
	 * Called by {@see \WC_Dynamic_Pricing_Module_Base::get_price_to_discount}.
	 *
	 * The main function is to convert the price.
	 * If the product has custom pricing, take that into account
	 *
	 * @since        2.2.0
	 *
	 * @param bool|float|int|string $result           The price to filter.
	 * @param array                 $filter_cart_item The cart item.
	 * @param string                $cart_item_key    The cart item key (unused).
	 *
	 * @return bool|float|int|string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_dynamic_pricing_get_price_to_discount( $result, $filter_cart_item, $cart_item_key ) {

		if ( ! is_numeric( $result ) ) {
			// Cannot deal with those.
			return $result;
		}

		if ( isset( $filter_cart_item['discounts'] ) ) {
			// Let's assume that Dynamic Pricing knows what to do. Just convert.
			return $this->convert( $result );
		}

		// Check if this product has custom pricing for the selected currency.
		$custom_price_to_discount = $this->get_custom_price_to_discount( $filter_cart_item );
		if ( false !== $custom_price_to_discount ) {
			// Custom prices do not require conversion.
			return $custom_price_to_discount;
		}

		// Default is to convert the price.
		return $this->convert( $result );

	}

	/**
	 * Return the custom price or false if it's not set for the selected currency.
	 *
	 * @since        2.2.0
	 *
	 * @param array $filter_cart_item The cart item.
	 *
	 * @return string|false
	 */
	protected function get_custom_price_to_discount( $filter_cart_item ) {
		$product      = \wc_get_product( $filter_cart_item['data'] );
		$product_info = new Info( $product );

		if ( $product_info->is_custom_priced() ) {

			// Dynamic Pricing does that.
			\do_action( 'wc_memberships_discounts_disable_price_adjustments' );

			if ( \apply_filters( 'wc_dynamic_pricing_get_use_sale_price', true, $filter_cart_item['data'] ) ) {
				$result = $product->get_price();
			} else {
				$result = $product->get_regular_price();
			}

			\do_action( 'wc_memberships_discounts_enable_price_adjustments' );

			return $result;
		}

		// No custom pricing.
		return false;
	}
}
