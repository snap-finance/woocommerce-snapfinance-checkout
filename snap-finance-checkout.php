<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              snap_finance.com
 * @since             1.0.0
 * @package           Woocommerce_Gateway_Snap_Finance
 *
 * @wordpress-plugin
 * Plugin Name:       Snap Finance Checkout
 * Plugin URI:        https://snapfinance.com/
 * Description:       Get approved through Snap Finance for up to $3000 with bad credit or no credit.
 * Version:           1.0.0
 * Author:            Snap Finance
 * Author URI:        https://snapfinance.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-gateway-snap-finance
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOOCOMMERCE_GATEWAY_SNAP_FINANCE_VERSION', '1.0.0');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'snap_finance_add_gateway_class');

function snap_finance_add_gateway_class($gateways) {
    $gateways[] = 'WC_snap_finance_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'snap_finance_init_gateway_class');

function snap_finance_add_notes() {
    if ($_REQUEST['orderId']) {
        update_post_meta($_REQUEST['orderId'], 'full_error', $_REQUEST['full_error'], true);
        $order = new WC_Order($_REQUEST['orderId']);
        $order->add_order_note($_REQUEST['message']);
        $order->save();
    }
    wp_send_json($_REQUEST);
}

function snap_finance_complete_payment() {
    if ($_REQUEST['orderId']) {
        update_post_meta($_REQUEST['orderId'], 'applicationId', $_REQUEST['applicationId']);
        update_post_meta($_REQUEST['orderId'], 'application', $_REQUEST['application'], true);
        $order = new WC_Order($_REQUEST['orderId']);
        $order->update_status('processing', 'order_note');
        $order->save();
    }
    wp_send_json(array('Done'));
}

function snap_finance_complete_order($order_id) {
    $application_id = get_post_meta($order_id, 'applicationId', true);
    $snap_finance_setting = get_option('woocommerce_snap_finance_settings');
    $snap_finance_token = get_snap_finance_token();

    $curl = curl_init();
    $authorization = "Authorization: Bearer " . $snap_finance_token;
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($snap_finance_setting['snap_finance_mode'] == 'sandbox') {
        curl_setopt($curl, CURLOPT_URL, "https://api-sandbox.snapfinance.com/checkout/v2/internal/application/complete/" . $application_id);
    } else {
        curl_setopt($curl, CURLOPT_URL, "https://api.snapfinance.com/checkout/v2/internal/application/complete/" . $application_id);
    }

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($curl);
    curl_close($curl);
    update_post_meta($order_id, 'final_responce', $response);
}

function wc_snap_finance_payment_template($located, $template_name, $args, $template_path, $default_path) {
    if (in_array($template_name, apply_filters('woocommerce_pretty_emails_templates_array', array('checkout/thankyou.php')))):

        if (isset($_GET['payment_method'])) {
            if ($_GET['payment_method'] == 'snap_finance') {
                $template_name = 'checkout/thankyou.php';
                return plugin_dir_path(__FILE__) . $template_name;
            }
        }
    endif;
    return $located;
}

function get_snap_finance_token() {
    $api_url = $client_id = $client_secret = $audience_url = "";
    $snap_finance_setting = get_option('woocommerce_snap_finance_settings');
    if (!isset($snap_finance_setting['snap_finance_mode'])) {
        $snap_finance_setting['snap_finance_mode'] = 'sandbox';
    }
    if (empty($snap_finance_setting['snap_finance_mode'])) {
        $snap_finance_setting['snap_finance_mode'] = 'sandbox';
    }
    if ($snap_finance_setting['snap_finance_mode'] == 'sandbox') {
        $client_id = $snap_finance_setting['snap_finance_client_sandbox_id'];
        $client_secret = $snap_finance_setting['snap_finance_client_sandbox_secret'];
        $api_url = 'https://auth-sandbox.snapfinance.com/oauth/token';
        $audience_url = 'https://api-sandbox.snapfinance.com/checkout/v2';
    } else {
        $client_id = $snap_finance_setting['snap_finance_client_live_id'];
        $client_secret = $snap_finance_setting['snap_finance_client_live_secret'];
        $api_url = 'https://auth.snapfinance.com/oauth/token';
        $audience_url = 'https://api.snapfinance.com/v2/internal';
    }

    $snap_finance_token = false;
    // Checking last updated token data in database.
    if (WP_DEBUG or false === ( $snap_finance_token = get_transient('snap_finance_token') )) {
        $curl = curl_init();

        // Calling to get token from snap API.
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"client_id\":\"" . $client_id . "\",\"client_secret\":\"" . $client_secret . "\",\"audience\":\"" . $audience_url . "\",\"grant_type\":\"client_credentials\"}",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $response = json_decode($response);
        //print_r($response);
        if (isset($response->access_token)) {
            $snap_finance_token = $response->access_token;
        } else {
            $snap_finance_token = false;
        }
        // Add new updated token in database.
        if ($snap_finance_token) {
            set_transient('snap_finance_token', $snap_finance_token, 600);
        }
    }
    return $snap_finance_token;
}

function snap_finance_init_gateway_class() {
    add_filter('wc_get_template', 'wc_snap_finance_payment_template', 999, 5);
    add_action('wp_ajax_snap_finance_complete_payment', 'snap_finance_complete_payment');
    add_action('wp_ajax_nopriv_snap_finance_complete_payment', 'snap_finance_complete_payment');
    add_action('wp_ajax_snap_finance_add_notes', 'snap_finance_add_notes');
    add_action('wp_ajax_nopriv_snap_finance_add_notes', 'snap_finance_add_notes');
    add_action("woocommerce_admin_order_data_after_shipping_address", "wc_snap_details_woocommerce_data", 10, 1);
    add_action('wp_enqueue_scripts', 'wc_snap_finance_style');
    add_action('admin_enqueue_scripts', 'wc_admin_snap_finance_script');
    add_action('woocommerce_order_status_completed', 'snap_finance_complete_order');
    add_filter('woocommerce_get_order_item_totals', 'snap_finance_application_id_details', 20, 3);
    add_filter('the_title', 'snap_finance_title_change');
    add_action("wp_ajax_reset_token", "snap_finance_reset_token");

    function snap_finance_title_change($title) {
        if (isset($_GET['payment_method'])) {
            if (function_exists('is_order_received_page') &&
                    is_order_received_page() && $_GET['payment_method'] == 'snap_finance' && strpos($title, 'Order received') !== false) {
                $title = 'Click on "checkout with snap" to proceed';
            }
        }
        return $title;
    }

    function snap_finance_application_id_details($total_rows, $order, $tax_display) {
        $new_total_rows = array();
        $application_id = get_post_meta($order->get_id(), 'applicationId', true);
        if ($application_id) {
            if ($total_rows) {
                foreach ($total_rows as $total_row_key => $total_row_value) {
                    if ($total_row_key == 'payment_method') {
                        $new_total_rows[$total_row_key] = $total_row_value;
                        $new_total_rows['payment_applicationid'] = array(
                            'label' => 'Application Id',
                            'value' => $application_id
                        );
                    } else {
                        $new_total_rows[$total_row_key] = $total_row_value;
                    }
                }
                $total_rows = $new_total_rows;
            }
        }
        return $total_rows;
    }

    function wc_snap_finance_style() {
        if (isset($_GET['payment_method'])) {
            if ($_GET['payment_method'] == 'snap_finance') {
                wp_enqueue_style('woocommerce-gateway-snap-finance', plugin_dir_url(__FILE__) . '/assets/css/snap-finance-checkout.css', array(), '1.0.0', 'all');
            }
        }
    }

    function wc_admin_snap_finance_script() {
        if (isset($_GET['section']) && $_GET['section'] == 'snap_finance') {
            wp_enqueue_script('woocommerce-gateway-snap-finance', plugin_dir_url(__FILE__) . '/assets/js/snap-finance-checkout.js', array('jquery'), time(), true);
            wp_localize_script('woocommerce-gateway-snap-finance', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        }
    }

    function wc_snap_details_woocommerce_data($order) {
        $application_id = get_post_meta($order->id, 'applicationId', true);
        $final_responce = get_post_meta($order->id, 'final_responce', true);
        if ($application_id) {
            echo '<p><strong>Application ID: </strong>' . $application_id . '</p>';
        }
        $final_message = '';
        if ($final_responce) {
            $final_responce = json_decode($final_responce);
            $final_message = $final_responce->data->message . ' ( At merchant portal )';
        } else if ($application_id) {
            $final_message = 'Still not updated. ( At merchant portal )';
        }
        if ($final_message) {
            echo '<p><strong>Application Status: </strong>' . $final_message . '</p>';
        }
    }
    
    function snap_finance_reset_token(){
        delete_transient('snap_finance_token');
    }

    class WC_snap_finance_Gateway extends WC_Payment_Gateway {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct() {

            $this->id = 'snap_finance'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Snap Finance Checkout';
            $this->method_description = 'No Credit Needed Financing Up to $3,000. Easy to Apply. Approvals up to 80%. Get Fast, Flexible Financing for the Things You Need.'; // will be displayed on the options page
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();
            $this->order_button_text = __('Review Order', 'woocommerce');
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->snap_finance_mode = $this->get_option('snap_finance_mode');

            if (empty($this->snap_finance_mode)) {
                $this->snap_finance_mode = 'sandbox';
            }
            if ($this->snap_finance_mode == 'sandbox') {
                $this->snap_finance_client_id = $this->get_option('snap_finance_client_sandbox_id');
                $this->snap_finance_client_secret = $this->get_option('snap_finance_client_sandbox_secret');
                $this->snap_finance_api_url = 'https://auth-sandbox.snapfinance.com/oauth/token';
                $this->snap_finance_api_audience_url = 'https://api-sandbox.snapfinance.com/checkout/v2';
            } else {
                $this->snap_finance_client_id = $this->get_option('snap_finance_client_live_id');
                $this->snap_finance_client_secret = $this->get_option('snap_finance_client_live_secret');
                $this->snap_finance_api_url = 'https://auth.snapfinance.com/oauth/token';
                $this->snap_finance_api_audience_url = 'https://api.snapfinance.com/v2/internal';
            }


            $snap_finance_token = get_snap_finance_token();
            $this->snap_finance_token = $snap_finance_token;


            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Snap Finance Checkout',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Snap Finance Checkout',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'No Credit Needed Financing Up to $3,000. Easy to Apply. Approvals up to 80%. Get Fast, Flexible Financing for the Things You Need.',
                ),
                'snap_finance_mode' => array(
                    'title' => 'Environment',
                    'type' => 'select',
                    'options' => array('sandbox' => 'Sandbox', 'live' => 'Live'),
                    'description' => '',
                    'default' => 'sandbox',
                ),
                'snap_finance_client_sandbox_id' => array(
                    'title' => 'Client Sandbox ID',
                    'type' => 'text'
                ),
                'snap_finance_client_sandbox_secret' => array(
                    'title' => 'Client Secret Sandbox Key',
                    'type' => 'text'
                ),
                'snap_finance_client_live_id' => array(
                    'title' => 'Client Live ID',
                    'type' => 'text'
                ),
                'snap_finance_client_live_secret' => array(
                    'title' => 'Client Secret Live Key',
                    'type' => 'text'
                ),
                'snap_finance_client_color' => array(
                    'title' => 'Checkout Button Color',
                    'type' => 'select',
                    'options' => array('light' => 'Light', 'dark' => 'Dark'),
                    'default' => 'dark',
                ),
                'snap_finance_client_shape' => array(
                    'title' => 'Checkout Button Shape',
                    'type' => 'select',
                    'options' => array('rect' => 'Rect', 'rounded' => 'Rounded', 'pill' => 'Pill'),
                    'default' => 'pill',
                ),
                'snap_finance_client_height' => array(
                    'title' => 'Checkout Button Height',
                    'type' => 'number',
                    'default' => '55',
                ),
            );
        }

        public function get_icon() {
            $icon_url = plugin_dir_url(__FILE__) . '/assets/images/logo.png';
            $icon_html = '<img style="max-height:2.6em;" src="' . esc_attr($icon_url) . '" alt="' . esc_attr__('Snap finance mark', 'woocommerce') . '" />';
            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields() {
// ok, let's display some description before the payment form
            if ($this->description) {

                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;border:0 !important;">';

            // Add this action hook if you want your custom payment gateway to support it
            do_action('woocommerce_credit_card_form_start', $this->id);


            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */

        public function payment_scripts() {

// we need JavaScript to process a token only on cart/checkout pages, right?
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            // no reason to enqueue JavaScript if API keys are not set
            if (empty($this->private_key) || empty($this->publishable_key)) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!$this->testmode && !is_ssl()) {
                return;
            }
        }

        /*
         * Fields validation, more in Step 5
         */

        public function validate_fields() {






            return true;
        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */

        public function process_payment($order_id) {

            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);

            $order->add_order_note('Hey, your order is On hold! Thank you!', true);
            $order->reduce_order_stock();
            // Empty cart
            $woocommerce->cart->empty_cart();

            // Redirect to the thank you page
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order) . '&payment_method=snap_finance'
            );
            /*
             * Array with parameters for API interaction
             */
            $args = array(
            );

            /*
             * Your API interaction could be built with wp_remote_post()
             */
            $response = wp_remote_post('{payment processor endpoint}', $args);


            if (!is_wp_error($response)) {

                $body = json_decode($response['body'], true);

                // it could be different depending on your payment processor
                if ($body['response']['responseCode'] == 'APPROVED') {

                    // we received the payment
                    //$order->payment_complete();
                    //$order->reduce_order_stock();
                    // some notes to customer (replace true with false to make it private)
                } else {
                    wc_add_notice('Please try again.', 'error');
                    return;
                }
            } else {
                wc_add_notice('Connection error.', 'error');
                return;
            }
        }

        /*
         * In case you need a webhook, like PayPal IPN etc
         */

        public function webhook() {

            $order = wc_get_order($_GET['id']);
            $order->payment_complete();
            $order->reduce_order_stock();

            update_option('webhook_debug', $_GET);
        }

    }

}
