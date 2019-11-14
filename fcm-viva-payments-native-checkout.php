<?php

/*
Plugin Name: Viva Payments' Native Checkout v.2.0 (Unofficial - FCM)
Description: Native Checkout v2.0 from Viva Payments, allows merchants to accept payments natively on their ecommerce store. The card details are harvested natively on your site and are send to Viva Payments directly - without touching your server! SCA, PSD2 and 3D-Secure compatible.
Version: 1.4.3
Author: FCM
Author URI: https://www.full-circle-marketing.co.uk
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 */


if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '/lib/functions.php';
add_action('plugins_loaded', 'FCM_VivaPayments_NativeCheckout_2_Gateway_init', 0);

function FCM_VivaPayments_NativeCheckout_2_Gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class FCM_VivaPayments_NativeCheckout_2_Gateway extends WC_Payment_Gateway
    {

        public function __construct() {
            global $woocommerce;

            $this->id = 'FCM_VivaPayments_NativeCheckout_2_Gateway';
            $this->icon = apply_filters('woocommerce_vivaway_icon', plugins_url('/assets/img/cards_no_amex.png', __FILE__));
            $this->has_fields = true;
            $this->notify_url = WC()->api_request_url('FCM_VivaPayments_NativeCheckout_2_Gateway');
            $this->method_title = 'Viva Payments | Native Checkout v2.0';
            $this->method_description = __('Native Checkout v2.0 from Viva Payments, allows merchants to accept payments natively on their ecommerce store. The card details are harvested natively on your site and are send to Viva Payments directly - without touching your server! SCA, PSD2 and 3D-Secure compatible.', 'viva-woocommerce-payment-gateway');

            // Load the form fields.
            $this->init_form_fields();


            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->VivaPaymentsMerchantId = htmlspecialchars_decode($this->get_option('VivaPaymentsMerchantId'));
            $this->VivaPaymentsAPIKey = htmlspecialchars_decode($this->get_option('VivaPaymentsAPIKey'));
            $this->VivaPaymentsClientID = htmlspecialchars_decode($this->get_option('VivaPaymentsClientID'));
            $this->VivaPaymentsClientSecret = htmlspecialchars_decode($this->get_option('VivaPaymentsClientSecret'));
			$this->VivaPaymentsTextsObj= json_decode($this->get_option('VivaPaymentsTextsObj'));
            $this->VivaPaymentsPaymentSource= $this->get_option('VivaPaymentsPaymentSource');
            $this->VivaPaymentsCustomCSS= $this->get_option('VivaPaymentsCustomCSS');
            $this->VivaPaymentsRequestURL = 'https://www.vivapayments.com';

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            //Pass-down the credentials to functions.php
            FCM_NativeCheckout2::Initialize(array(
                'MERCHANT_ID' => $this->VivaPaymentsMerchantId,
                'API_KEY' => $this->VivaPaymentsAPIKey,
                'CLIENT_ID' => $this->VivaPaymentsClientID,
                'CLIENT_SECRET' => $this->VivaPaymentsClientSecret,
                'PAYMENT_SOURCE' => $this->VivaPaymentsPaymentSource
            ));

        }

        /**
         * Admin Panel Options
         * */
        public function admin_options() {
            echo '<h3>' . __('Viva Payments | Native Checkout v2.0 (Unofficial) by FCM', 'viva-woocommerce-payment-gateway') . '</h3>';
            echo '<p>' . __('<br>This plugin is in BETA. There will be many updates and bugs so please be patient. I suggest you enable autoupdate.</b><br>Your Merchant ID and API Key, can be found in your Viva Wallet Dashboard, under Settings > API Access. Make sure to enable "Enable Native/Pay with Viva Wallet checkout". <br>Client_ID and Client_Secret are part of the new API v.2.0. To find yours, head to the <a href="https://developer.vivawallet.com/authentication-methods/#oauth-2-token-generation" target="_blank">appropriate section in the documentation</a>.', 'viva-woocommerce-payment-gateway') . '</p><br>';
			echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }

        /**
         * Initialise Gateway Settings Form Fields
         * */
        function init_form_fields() {
            $this->form_fields = array(
                'title' => array(
                    'title' => __('Title', 'viva-woocommerce-payment-gateway'),
                    'type' => 'text',
                    'description' => __('', 'viva-woocommerce-payment-gateway'),
                    'desc_tip' => false,
                    'default' => __('Pay via Card', 'viva-woocommerce-payment-gateway'),
                ),
                'VivaPaymentsMerchantId' => array(
                    'title' => __('Merchant ID', 'viva-woocommerce-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter your Merchant ID. This can be sourced from your account page, when you login on Viva Payments.', 'viva-woocommerce-payment-gateway'),
                    'default' => '',
                    'desc_tip' => false,
                ),
                'VivaPaymentsAPIKey' => array(
                    'title' => __('API key', 'viva-woocommerce-payment-gateway'),
                    'type' => 'password',
                    'description' => __('Enter your API key. This can be sourced from your account page, when you login on Viva Payments.', 'viva-woocommerce-payment-gateway'),
                    'default' => '',
                    'desc_tip' => false,
                ),
                'VivaPaymentsClientID' => array(
                    'title' => __('Client ID', 'viva-woocommerce-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter your Client ID (API v2.0 Credentials). ', 'viva-woocommerce-payment-gateway'),
                    'default' => '',
                    'desc_tip' => false,
                ),
                'VivaPaymentsClientSecret' => array(
                    'title' => __('Client Secret', 'viva-woocommerce-payment-gateway'),
                    'type' => 'password',
                    'description' => __('Enter your Client Secret (API v2.0 Credentials). ', 'viva-woocommerce-payment-gateway'),
                    'default' => '',
                    'desc_tip' => false,
                ),
                'VivaPaymentsPaymentSource' => array(
                    'title' => __('Payment Source Code', 'viva-woocommerce-payment-gateway'),
                    'type' => 'text',
                    'description' => __('You can create a new payment source, under Sales > Online Payments > Websites / Apps.<br> Keep in mind that your website will need to pass validation from Viva Payments. ', 'viva-woocommerce-payment-gateway'),
                    'default' => 'Default',
                    'desc_tip' => false,
                ),
                    'VivaPaymentsCustomCSS' => array(
                        'title' => __('Custom CSS', 'viva-woocommerce-payment-gateway'),
                        'type' => 'textarea',
                        'description' => __('Enter additional CSS.','viva-woocommerce-payment-gateway'),
                        'default' => '',
                        'desc_tip' => false,

                    ),
                    'VivaPaymentsTextsObj' => array(
                        'title' => __('Change Texts/Translate', 'viva-woocommerce-payment-gateway'),
                        'type' => 'textarea',
                        'description' => __('Translate or change the texts shown at the customer.<br><b>CHANGE ONLY THE TEXTS INSIDE THE DOUBLE APOSTROPHES. DO NOT USE THE CHARACHTER " (double apostrophe) IN YOUR TEXTS. DO NOT CHANGE THE LEFT-HANDSIDE VALUES!</b>','viva-woocommerce-payment-gateway'),
                        'default' => '{
                            "card_holder"  :  "Cardholder name",
                            "card_number"  :  "Card number",
                            "month"  :  "MM",
                            "year"  :  "YY",
                            "cvv"  :  "CVV",
                            "payment_failed" : "Your card was declined. Please check your card details and/or your available balance."
                            }',
                        'desc_tip' => false,

                    )
            );
        }

        //Generate the payment form, load javascripts and css files
        function Initialize_Payment_Form() {
                $TEXTS = $this->VivaPaymentsTextsObj;

                wp_enqueue_style('form',plugin_dir_url( __FILE__ ) . 'assets/css/form.css');

            wp_enqueue_script('cleave',plugin_dir_url( __FILE__ ) . 'assets/js/cleave.js');
            wp_enqueue_script('creditCardValidator',plugin_dir_url( __FILE__ ) . 'assets/js/creditCardValidator.js');
            wp_enqueue_script('vivapayments', $this->VivaPaymentsRequestURL.'/web/checkout/v2/js', array('jquery'));
            wp_enqueue_script('FCM',plugin_dir_url( __FILE__ ) . 'assets/js/fcm.js');

                $result = '
                            <form class="viva-credit-card-form" id="viva-credit-card-form">
                                <div class="card-js " data-capture-name="true">
                                <input type="text" data-vp="cardholder" size="20" name="cardholder" class="viva-input cardholder" placeholder="'.esc_attr($TEXTS->card_holder).'">
                                <input class="cardnumber viva-input" name="cardnumber" onchange="Validate_Card_Form()" maxlength="19" placeholder="'.esc_attr($TEXTS->card_number).'" data-vp="cardnumber" style="background: url('.plugin_dir_url( __FILE__ ).'/assets/img/credit-card.svg) no-repeat 95% center">
                                <input class="expiry expiry-month viva-input" type="number" maxlength="2" name="expiry-month" placeholder="'.esc_attr($TEXTS->month).'" data-vp="month" >
                                &#x2f;
                                <input class="expiry expiry-year viva-input" type="number" maxlength="2" name="expiry-year" placeholder="'.esc_attr($TEXTS->year).'" data-vp="year">
                                <input class="cvv viva-input" maxlength="4" type="text" name="cvv" placeholder="'.esc_attr($TEXTS->cvv).'" data-vp="cvv">
                                </div>
                            </form>

                                <script>
                                    $ = jQuery;
                                    FCM_Plugin_Image_Base_Url = "'.plugin_dir_url( __FILE__ ).'/assets/img/"
                                </script>

                                <style>
                                    '.wp_filter_nohtml_kses($this->VivaPaymentsCustomCSS).'
                                </style>

                                ';
                     return $result;

        }

        function payment_fields() {
            echo $this->Initialize_Payment_Form();

            return 0;

        }

        //WooCommerce API: process_payment runs everytime the "Place Order" button is pressed.

        function process_payment($order_id) {
            global $woocommerce;

            $order = new WC_Order($order_id);
            $orderNo = $order->get_order_number();
            $orderFName = $order->get_billing_first_name();
            $orderLName = $order->get_billing_last_name();
            $orderEmail = $order->get_billing_email();
            $orderTel = $order->get_billing_phone();

            if(!empty($_POST['charge_token']))
            {
                //Viva Payments requires the merchant to create an order with a desired amount
                //and then "fulfill" the order with a card charge (the card charge is $TRANSACTION)
                $ORDER_CODE = FCM_NativeCheckout2::Create_Order(array(
                    'AMOUNT' => $order->get_total()*100,
                    'ORDER_NO' => $orderNo,
                    'ORDER_F_NAME' => $orderFName,
                    'ORDER_L_NAME' => $orderLName,
                    'ORDER_EMAIL' => $orderEmail,
                    'ORDER_TEL' => $orderTel
                ));

                $TRANSACTION = FCM_NativeCheckout2::Transactions(array(
                    'CHARGE_TOKEN' => sanitize_text_field($_POST['charge_token']),
                    'ORDER_CODE' => $ORDER_CODE
                ));

                if($TRANSACTION['WAS_SUCCESSFUL']){
                    FCM_NativeCheckout2::Complete_Woo_Order(array(
                        'ORDER_ID' => $order_id,
                        'TRANSACTION_ID' => $TRANSACTION['TRANSACTION_ID'],
                        'ERROR_CODE' => $TRANSACTION['ERROR_CODE'],
                        'ERROR_TEXT' => $TRANSACTION['ERROR_TEXT'],
                        'STATUS_ID' => $TRANSACTION['STATUS_ID'],
                        'EVENT_ID' => $TRANSACTION['EVENT_ID']
                    ));

                    echo(json_encode(array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    )));

                    exit;

                } else {

                    // 05112019 - Adding failed order to order table

                    FCM_NativeCheckout2::Fail_Woo_Order(array(
                        'ORDER_ID' => $order_id,
                        'ERROR_CODE' => $TRANSACTION['ERROR_CODE'],
                        'ERROR_TEXT' => $TRANSACTION['ERROR_TEXT'],
                        'STATUS_ID' => $TRANSACTION['STATUS_ID'],
                        'EVENT_ID' => $TRANSACTION['EVENT_ID']
                    ));

                    echo(json_encode(array(
                            'result' => 'failure',
                            'refresh' => 'false',
                            'messages' => "<div><script type=\"text/javascript\">"
                            . "$('#charge_token').val(''); alert('".htmlspecialchars($this->VivaPaymentsTextsObj->payment_failed)."');</script></div>"
                        )));

                        exit;
                }
            } else {
                    echo(json_encode(array(
                        'result' => 'failure',
                        'refresh' => 'false',
                        'messages' => "<div><script type=\"text/javascript\">"
                        ."if ($('form#viva-credit-card-form .cardholder').val() !== '' && $('form#viva-credit-card-form .cardnumber').val() !== '' && $('form#viva-credit-card-form .expiry-month').val() !== '' && $('form#viva-credit-card-form .expiry-year').val() !== '' && $('form#viva-credit-card-form .cvv').val() !== '') {

                            //Open the 3DS container with the loading circle.
                            $('.three-ds-container').show();
                            var Timeout = setTimeout(function(){ $('.three-ds-container').hide() },30000);

                            //Pass-down the access token and declare the 3DS container

                            VivaPayments.cards.setup({
                                authToken: '" . FCM_NativeCheckout2::Get_Access_Token($this->VivaPaymentsClientID,$this->VivaPaymentsClientSecret) . "',
                                baseURL: 'https://api.vivapayments.com',
                                cardHolderAuthOptions: {
                                    cardHolderAuthPlaceholderId: 'three-ds-popup',
                                    cardHolderAuthInitiated: o=> {
                                        $('.lds-dual-ring').hide();
                                        clearTimeout(Timeout);
                                    },
                                    cardHolderAuthFinished: o=> {
                                        $('.lds-dual-ring').show();
                                    }
                                }
                            });

                            //Request a charge token from Viva Payments.
                            VivaPayments.cards.requestToken({
                                amount: " . $order->get_total()*100 . ",
                                authenticateCardholder: true
                            }).done((responseData) => {

                                $('.three-ds-container').hide(); //Hides the 3DS container
                                $('#charge_token').val(responseData.chargeToken);
                                $('.woocommerce-checkout').submit(); //Re-submit the order, this time with the charge token
                            })
                        } else {
                            $('.viva-credit-card-form').css({
                                'border': '1px solid #ff0022'
                            });

                            LoadVivaPaymentsForm();

                        }</script></div>"
                    )));

                    exit;
                }


            }


        }

    /**
     * Add VivaPayments Gateway to WC
     * */
    function woocommerce_add_FCM_VivaPayments_NativeCheckout_2_Gateway($methods)
    {
        $methods[] = 'FCM_VivaPayments_NativeCheckout_2_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_FCM_VivaPayments_NativeCheckout_2_Gateway');

    /**
     * Add Settings link to the plugin entry in the plugins menu for WC below 2.1
     * */
    if (version_compare(WOOCOMMERCE_VERSION, "2.1") <= 0) {

        add_filter('plugin_action_links', 'FCM_VivaPayments_NativeCheckout_2_Gateway_plugin_action_links', 10, 2);
        function FCM_VivaPayments_NativeCheckout_2_Gateway_plugin_action_links($links, $file)
        {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=FCM_VivaPayments_NativeCheckout_2_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
    /**
     * Add Settings link to the plugin entry in the plugins menu for WC 2.1 and above
     * */
    else {
        add_filter('plugin_action_links', 'FCM_VivaPayments_NativeCheckout_2_Gateway_plugin_action_links', 10, 2);

        function FCM_VivaPayments_NativeCheckout_2_Gateway_plugin_action_links($links, $file)
        {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=FCM_VivaPayments_NativeCheckout_2_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
}
