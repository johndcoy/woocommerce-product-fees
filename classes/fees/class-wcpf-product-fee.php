<?php
/**
 * WooCommerce Product Fees
 *
 * Get indivual product fee data.
 *
 * @class 	WCPF_Product_Fee
 * @author 	Caleb Burks
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCPF_Product_Fee extends WCPF_Fee {

	/** @var int Product ID */
	public $id;

	/** @var int Product Quantity */
	public $qty = 1;

	/** @var int Product Price */
	public $price = 0;

	/** @var int Main Cart Instance */
	public $cart;

	/**
	 * Constructor for the indivual product fee class.
	 *
	 * @access public
	 */
	public function __construct( $product_data, $cart ) {
		$this->id    = $product_data['id'];
		$this->qty   = $product_data['qty'];
		$this->price = $product_data['price'];
		$this->cart  = $cart;
	}

	/**
	 * Get fee data from the product settings.
	 *
	 * @access public
	 */
	public function get_product_fee_data() {
		// Product ID
		$id = $this->id;

		// Check for a fee name and fee amount in the product settings
		if ( get_post_meta( $id, 'product-fee-name', true ) != '' && get_post_meta( $id, 'product-fee-amount', true ) != '' ) {
			$fee = array(
				'name' => get_post_meta( $id, 'product-fee-name', true ),
				'amount' =>	get_post_meta( $id, 'product-fee-amount', true ),
				'multiplier' => get_post_meta( $id, 'product-fee-multiplier', true ),
				'product_id' => $id
			);

			return apply_filters( 'wcpf_indivual_product_fee_data',  $fee );
		} else {
			// Return false if the product has no fee.
			return false;
		}
	}

	/**
	 * Checks if the fee has a percentage in it, and then convert the fee from a percentage to a decimal.
	 *
	 * @access public
	 */
	public function percentage_conversion( $fee_amount ) {
		if ( strpos( $fee_amount, '%' ) ) {
			// Convert to Decimal
			$decimal = str_replace( '%', '', $fee_amount ) / 100;

			// Multiply by Product Price
			$fee_amount = $this->price * $decimal;
		}

		return $fee_amount;
	}

	/**
	 * Check if the fee should be multiplied by the quantity of the product in the cart.
	 *
	 * @access public
	 */
	public function quantity_multiply( $fee_data = '' ) {
		// Allow child classes to use this with their own fee data.
		if ( $fee_data == '' ) {
			$fee_data = $this->get_product_fee_data();
		}

		// Return if the product has no fee data.
		if ( ! $fee_data ) {
			return false;
		}

		// Replace with a standard decimal place for calculations.
		$decimal_separator = wc_get_price_decimal_separator();
		$fee_amount = str_replace( $decimal_separator, '.', $fee_data['amount'] );

		// Run the percentage check, and convert to a standard decimal place.
		$converted_amount = $this->percentage_conversion( $fee_amount );

		// Multiply the fee by the quantity if necessary.
		if ( $fee_data['multiplier'] == 'yes' ) {
			$fee_data['amount'] = $this->qty * $converted_amount;
		} else {
			$fee_data['amount'] = $converted_amount;
		}

		return $fee_data;
	}

	/**
	 * Return final fee data
	 *
	 * @access public
	 */
	public function return_fee() {
		$fee_data = $this->quantity_multiply();

		return parent::get_fee( $fee_data, $this->cart );
	}

}
