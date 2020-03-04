<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined('ABSPATH') || exit;
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
    <?php if ($order) :

        do_action('woocommerce_before_thankyou', $order->get_id()); ?>
        <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
        <?php do_action('woocommerce_thankyou', $order->get_id()); ?>

        <?php else : ?>

            <p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters('woocommerce_thankyou_order_received_text', esc_html__('Thank you. Your order has been received.', 'woocommerce'), null); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

        <?php endif; ?>

        <div class="divider"></div>

        <div id="placeorder" class="application_id_box">
            <div ><input id="applicationId"></div>
            <div id='snap-place-order-button'></div>
        </div>

    </div>
<!--  *** MERCHANT SITE IMPLEMENTATION CODE ENDS HERE *** -->