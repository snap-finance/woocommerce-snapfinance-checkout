<?php
/**
 * Checkout Order Receipt Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-receipt.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce-order" id="checkout">
	<div class="loader-box" >
		<div class="blockUI blockOverlay"></div>
	</div>
	<main>

		<div class="vertical-divider"></div>

		<div style="display: none;">
			<div class="payment-option-label">

				<label>
					<input type="radio" name="payment-option" value="snap" checked>
					<div id="snap-checkout-mark"></div>
				</label>
			</div>


			<div id="snap-checkout-button" class="payment-button"></div>

		</div>


		<div class="divider"><span></span></div>

	</main>
	<h3><span class="status-title">Status: </span><span class="status-no">Incomplete, please complete your Snap lease application or pick a different method of payment to complete your order.</span></h3>
	<div class="divider"></div>

<div id="placeorder" class="application_id_box">
	<div ><input id="applicationId"></div>
	<div id='snap-place-order-button'></div>
</div>
</div>
<ul class="order_details">
	<li class="order">
		<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
		<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
	</li>
	<li class="date">
		<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
		<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
	</li>
	<li class="total">
		<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
		<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
	</li>
	<?php if ( $order->get_payment_method_title() ) : ?>
		<li class="method">
			<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
			<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
		</li>
	<?php endif; ?>
</ul>

<?php do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order->get_id() ); ?>

<div class="clear"></div>
