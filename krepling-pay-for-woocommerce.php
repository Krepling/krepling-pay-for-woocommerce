<?php
/*
 * Plugin Name: Krepling Pay for WooCommerce
 * Plugin URI: https://krepling.com/
 * Description: Enable fast and secure transactions with Krepling's customer friendly checkout. Store payment information safely for card-not-present transactions.
 * Version: 1.0.0
 * Author: Krepling
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 8.2
 * Text Domain: krepling-pay-for-woocommerce
 * Domain Path: /languages
 *
 * @package WooCommerce
 */

defined('ABSPATH') || exit;

if (!defined('KREPLING_PLUGIN_VERSION')) {
    define('KREPLING_PLUGIN_VERSION', '1.0.0');
}

if (!defined('KREPLING_PLUGIN_FILE')) {
    define('KREPLING_PLUGIN_FILE', __FILE__);
}

if (!defined('KREPLING_PLUGIN_DIR')) {
    define('KREPLING_PLUGIN_DIR', plugin_dir_path(KREPLING_PLUGIN_FILE));
}

if (!defined('KREPLING_PLUGIN_URL')) {
    define('KREPLING_PLUGIN_URL', plugin_dir_url(KREPLING_PLUGIN_FILE));
}

require_once KREPLING_PLUGIN_DIR . 'krepling-services-auth.php';
require_once KREPLING_PLUGIN_DIR . 'fastapi-order-endpoint.php';
require_once KREPLING_PLUGIN_DIR . 'krepling-session.php';

if (!defined('KREPLING_SERVICES_BASE')) {
    define('KREPLING_SERVICES_BASE', 'https://services.krepling.com');
}

function krepling_get_dynamic_css_vars()
{
    $config_data = get_option('woocommerce_krepling_settings', []);

    $button_color = !empty($config_data['plugin-btn-color']) ? sanitize_hex_color($config_data['plugin-btn-color']) : '#9117f2';
    $bg_color     = !empty($config_data['plugin-bg-color']) ? sanitize_hex_color($config_data['plugin-bg-color']) : '#ffffff';
    $text_color   = !empty($config_data['plugin-text-color']) ? sanitize_hex_color($config_data['plugin-text-color']) : '#000000';
    $input_color  = !empty($config_data['plugin-input-color']) ? sanitize_hex_color($config_data['plugin-input-color']) : '#ffffff';

    if (!$button_color) {
        $button_color = '#9117f2';
    }
    if (!$bg_color) {
        $bg_color = '#ffffff';
    }
    if (!$text_color) {
        $text_color = '#000000';
    }
    if (!$input_color) {
        $input_color = '#ffffff';
    }

    $border_color = $button_color . '7F';

    return ":root {\n" .
        "  --kp-button-color: {$button_color};\n" .
        "  --kp-background-color: {$bg_color};\n" .
        "  --kp-text-color: {$text_color};\n" .
        "  --kp-input-color: {$input_color};\n" .
        "  --kp-border-color: {$border_color};\n" .
        "}\n";
}

function krepling_get_frontend_script_data()
{
    $config_data = get_option('woocommerce_krepling_settings', []);

    return [
        'base_url'                      => esc_url_raw(plugin_dir_url(KREPLING_PLUGIN_FILE)),
        'ajax_url'                      => esc_url_raw(admin_url('admin-ajax.php')),
        'ajax_action'                   => 'krepling_dispatch',
        'services_base'                 => esc_url_raw(krepling_services_get_base()),
        'public_service_token_endpoint' => esc_url_raw(rest_url('krepling/v1/public-service-token')),
        'hide_Address'                  => !empty($config_data['hide_Address']) ? sanitize_text_field((string) $config_data['hide_Address']) : '',
        'kp_button_color'               => !empty($config_data['plugin-btn-color']) ? sanitize_hex_color($config_data['plugin-btn-color']) : '#7700e2',
        'csrf_nonce'                    => wp_create_nonce('krepling_action_nonce'),
        'krepling_nonce'                => wp_create_nonce('krepling_action_nonce'),
    ];
}

function krepling_ajax_dispatch_bridge()
{
    require_once KREPLING_PLUGIN_DIR . 'inc/controller/bootstrap.php';
    require_once KREPLING_PLUGIN_DIR . 'inc/controller/legacy-api.php';
    require_once KREPLING_PLUGIN_DIR . 'inc/controller/actions/migrated.php';
    require_once KREPLING_PLUGIN_DIR . 'inc/controller/dispatcher.php';

    $response = krepling_dispatch_action($krepling_request_action);

    if ($response === false || $response === null) {
        wp_send_json([
            'status'  => 400,
            'message' => __('Invalid Krepling action.', 'krepling-pay-for-woocommerce'),
        ], 400);
    }

    if (is_string($response)) {
        echo wp_json_encode(
            json_decode($response, true) ?? $response
        );
        wp_die();
    }

    wp_send_json($response);
}

add_action('wp_ajax_krepling_dispatch', 'krepling_ajax_dispatch_bridge');
add_action('wp_ajax_nopriv_krepling_dispatch', 'krepling_ajax_dispatch_bridge');

function krepling_get_maps_script_url()
{
    $cache_key = 'krepling_maps_script_url';
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_get(krepling_services_get_base() . '/config/maps-js-url', [
        'timeout'     => 15,
        'redirection' => 3,
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => [
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return '';
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body        = (string) wp_remote_retrieve_body($response);
    $data        = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        return '';
    }

    if (!is_array($data) || empty($data['script_url'])) {
        return '';
    }

    $script_url = esc_url_raw($data['script_url']);

    set_transient($cache_key, $script_url, 12 * HOUR_IN_SECONDS);

    return $script_url;
}

/*
 * This action hook activate your payment gateway
 */
register_activation_hook(KREPLING_PLUGIN_FILE, 'krepling_init_gateway_class');

/*
 * This action hook uninstall/delete your payment gateway
 */
register_uninstall_hook(KREPLING_PLUGIN_FILE, 'krepling_method_uninstall');

function krepling_method_uninstall()
{
    delete_option('woocommerce_krepling_settings');
    delete_option('woocommerce_krepling_logo');
    delete_transient('krepling_maps_script_url');
}

/* 
  * Declare the incompatibility of plugin with WooCommerce
  * A message will be shown in the Cart & Checkout blocks settings sidebar letting users know
  * Check the readme.txt file to change the block checkout to classic checkout theme
*/

add_action( 'before_woocommerce_init', function() {
  if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', KREPLING_PLUGIN_FILE, false);
  }
} );

/*
 * This action hook registers our PHP class as a Krepling payment gateway
 */
add_filter('woocommerce_payment_gateways', 'krepling_add_gateway_class');
function krepling_add_gateway_class($gateways)
{
    $gateways[] = 'Krepling_Pay_Gateway';
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'krepling_init_gateway_class');
function krepling_init_gateway_class()
{
    // If WooCommerce is not installed, do nothing.
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    if (class_exists('Krepling_Pay_Gateway')) {
        return;
    }

    class Krepling_Pay_Gateway extends WC_Payment_Gateway
    {
      /**
       * Class constructor, more about it in Step 3
       *
       * Constructor for initializing the payment gateway
       */
        public function __construct()
        {
            $this->id = 'krepling'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = __('Krepling Checkout', 'krepling-pay-for-woocommerce');
            $this->method_description = __("Enable fast and secure transactions with Krepling's customer friendly checkout. Store payment information safely for card-not-present transactions.", 'krepling-pay-for-woocommerce'); // will be displayed on the options WC_Payment_Gateway
            $this->init_form_fields(); // setting defines
            $this->init_settings();  // Load the settings
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->merchant_key = $this->get_option('merchant_id');
            $this->secret_key = $this->get_option('secret_id');
          // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
          // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array( $this, 'payment_scripts' ));
          // add custom css in admin head section
            add_action('admin_head', array( $this, 'krepling_admin_style'));
          // add color picker js script in admin
            add_action('admin_enqueue_scripts', array( $this, 'krepling_admin_scripts'));
          // enable only webp if needed; do not allow svg uploads
            add_filter('upload_mimes', array( $this, 'wp_mime_types'));
          // add meta tag to disable zoom in/out on checkout page
            add_action('wp_head', array( $this, 'add_meta_tags' ), 1);
        }

      /**
       * Plugin options, we deal with it in Step 3 too
       */
        public function init_form_fields()
        {
          $this->form_fields = array(
            'enabled' => array(
            'title'       => __('Enable/Disable', 'krepling-pay-for-woocommerce'),
            'label'       => __('Enable Krepling Checkout', 'krepling-pay-for-woocommerce'),
            'type'        => 'checkbox',
            'default'     => 'no'
            ),
            'title' => array(
            'title'       => __('Title', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'krepling-pay-for-woocommerce'),
            'default'     => __('Krepling Checkout', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            ),
            'merchant_id' => array(
            'title'       => __('Merchant Id *', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'description' => __('This merchant id is required before use this payment method.', 'krepling-pay-for-woocommerce')
            ),
            'secret_id' => array(
            'title'       => __('Secret Id *', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'description' => __('This secret id is required before use this payment method.', 'krepling-pay-for-woocommerce')
            ),
            'hide_Address' => array(
            'title'       => __('Hide Address', 'krepling-pay-for-woocommerce'),
            'type'        => 'checkbox',
            'default'     => 'no'
            ),
            'plugin-btn-color' => array(
            'title'       => __('Button Color', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'default' => '#7700e2',
            'description' => __('Default button color - #7700e2', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            ),
            'plugin-bg-color' => array(
            'title'       => __('Background Color', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'default' => '#ffffff',
            'description' => __('Default background color - #ffffff', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            ),
            'plugin-text-color' => array(
            'title'       => __('Text Color', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'default' => '#000000',
            'description' => __('Default text color - #000000', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            ),
            'plugin-input-color' => array(
            'title'       => __('Input Field Color', 'krepling-pay-for-woocommerce'),
            'type'        => 'text',
            'default' => '#efefef',
            'description' => __('Default input field color - #efefef', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            ),
            'plugin_logo' => array(
            'title'       => __('Plugin Logo', 'krepling-pay-for-woocommerce'),
            'type'        => 'file',
            'description' => __('[Allow extensions only - jpg, jpeg, png, webp, gif]', 'krepling-pay-for-woocommerce'),
            'desc_tip'    => true
            )
          );
        }

      /**
       * Validate the merchant id which is required
       */
        public function validate_merchant_id_field($key, $value)
        {
            if (empty($value)) {
                WC_Admin_Settings::add_error('The merchant id is required.');
                $value = ''; // empty it because it is not correct
            }
            return $value;
        }

      /**
       * Validate the secret id which is required
       */
        public function validate_secret_id_field($key, $value)
        {
            if (empty($value)) {
                WC_Admin_Settings::add_error('The secret id is required.');
                $value = ''; // empty it because it is not correct
            }
            return $value;
        }

      /**
       * Add the custom css in admin head section
       */
        public function krepling_admin_style()
        {
            wp_enqueue_style(
                'krepling_admin_style',
                KREPLING_PLUGIN_URL . 'assets/css/krepling-admin-style.css',
                [],
                file_exists(KREPLING_PLUGIN_DIR . 'assets/css/krepling-admin-style.css')
                    ? filemtime(KREPLING_PLUGIN_DIR . 'assets/css/krepling-admin-style.css')
                    : KREPLING_PLUGIN_VERSION
            );
        }
      /**
       * Add the custom js in admin footer section
       */
        function krepling_admin_scripts()
        {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            $admin_color_picker_init = <<<'JS'
jQuery(function($) {
    $('#woocommerce_krepling_plugin-btn-color').wpColorPicker();
    $('#woocommerce_krepling_plugin-bg-color').wpColorPicker();
    $('#woocommerce_krepling_plugin-text-color').wpColorPicker();
    $('#woocommerce_krepling_plugin-input-color').wpColorPicker();
});
JS;

            wp_add_inline_script('wp-color-picker', $admin_color_picker_init, 'after');
        }

      /**
       * You will need it if you want your custom credit card form, Step 4 is about it
       */
      public function payment_fields()
      {
          if (!empty($this->merchant_key) && !empty($this->secret_key)) {
              include_once('payment.php');
          } else {
              echo "<i class='card_details_error'>Kindly check all the required payment configuration details in admin before use this method.</i>";
          }
      }

      /*
       * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
       */
        public function payment_scripts()
        {
            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            if (is_checkout()) {
                $js_base_path  = KREPLING_PLUGIN_DIR . 'assets/js/';
                $css_base_path = KREPLING_PLUGIN_DIR . 'assets/css/';

                wp_enqueue_style(
                    'krepling_slick',
                    KREPLING_PLUGIN_URL . 'assets/css/slick.min.css',
                    [],
                    file_exists($css_base_path . 'slick.min.css')
                        ? filemtime($css_base_path . 'slick.min.css')
                        : KREPLING_PLUGIN_VERSION
                );

                wp_enqueue_style(
                    'krepling_icons',
                    KREPLING_PLUGIN_URL . 'assets/css/krepling-icons.css',
                    [],
                    file_exists($css_base_path . 'krepling-icons.css')
                        ? filemtime($css_base_path . 'krepling-icons.css')
                        : KREPLING_PLUGIN_VERSION
                );

                wp_enqueue_style(
                    'krepling-bootstrap-scoped',
                    plugin_dir_url(__FILE__) . 'assets/css/krepling-bootstrap-scoped.css',
                    [],
                    filemtime(plugin_dir_path(__FILE__) . 'assets/css/krepling-bootstrap-scoped.css')
                );

                wp_enqueue_style(
                    'krepling-style',
                    KREPLING_PLUGIN_URL . 'assets/css/krepling-style.css',
                    ['krepling-bootstrap-scoped'],
                    file_exists($css_base_path . 'krepling-style.css')
                        ? filemtime($css_base_path . 'krepling-style.css')
                        : KREPLING_PLUGIN_VERSION
                );

                wp_enqueue_style(
                    'krepling-form-clean',
                    plugin_dir_url(__FILE__) . 'assets/css/krepling-form-clean.css',
                    array('krepling-style'),
                    filemtime(plugin_dir_path(__FILE__) . 'assets/css/krepling-form-clean.css')
                );

                wp_enqueue_style(
                    'krepling-responsive',
                    KREPLING_PLUGIN_URL . 'assets/css/krepling-responsive.css',
                    ['krepling-style'],
                    file_exists($css_base_path . 'krepling-responsive.css')
                        ? filemtime($css_base_path . 'krepling-responsive.css')
                        : KREPLING_PLUGIN_VERSION
                );

                wp_enqueue_style(
                    'krepling-intl-tel-input',
                    KREPLING_PLUGIN_URL . 'assets/vendor/intl-tel-input/css/intlTelInput.css',
                    array(),
                    KREPLING_PLUGIN_VERSION
                );



                wp_enqueue_script(
                    'krepling-intl-tel-input',
                    KREPLING_PLUGIN_URL . 'assets/vendor/intl-tel-input/js/intlTelInput.min.js',
                    array(),
                    KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling-phone',
                    KREPLING_PLUGIN_URL . 'assets/js/krepling-phone.js',
                    array('krepling-intl-tel-input'),
                    KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_localize_script(
                    'krepling-phone',
                    'kreplingPhoneConfig',
                    array(
                        'utilsUrl' => KREPLING_PLUGIN_URL . 'assets/vendor/intl-tel-input/js/utils.js',
                        'initialCountry' => 'ca',
                    )
                );

                wp_add_inline_style('krepling-style', krepling_get_dynamic_css_vars());

                wp_enqueue_script(
                    'krepling_inputmask',
                    KREPLING_PLUGIN_URL . 'assets/js/inputmask.bundle.min.js',
                    ['jquery'],
                    file_exists($js_base_path . 'inputmask.bundle.min.js')
                        ? filemtime($js_base_path . 'inputmask.bundle.min.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling_search_address',
                    KREPLING_PLUGIN_URL . 'assets/js/searchAddress.js',
                    ['jquery'],
                    file_exists($js_base_path . 'searchAddress.js')
                        ? filemtime($js_base_path . 'searchAddress.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling_slick_js',
                    KREPLING_PLUGIN_URL . 'assets/js/slick.min.js',
                    ['jquery'],
                    file_exists($js_base_path . 'slick.min.js')
                        ? filemtime($js_base_path . 'slick.min.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling_country_address_label',
                    KREPLING_PLUGIN_URL . 'assets/js/country_address_label.js',
                    ['jquery'],
                    file_exists($js_base_path . 'country_address_label.js')
                        ? filemtime($js_base_path . 'country_address_label.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling_utils',
                    KREPLING_PLUGIN_URL . 'assets/js/utils.js',
                    ['jquery'],
                    file_exists($js_base_path . 'utils.js')
                        ? filemtime($js_base_path . 'utils.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                wp_enqueue_script(
                    'krepling_main',
                    KREPLING_PLUGIN_URL . 'assets/js/krepling.js',
                    [
                        'jquery',
                        'krepling_inputmask',
                        'krepling_search_address',
                        'krepling_slick_js',
                        'krepling_country_address_label',
                        'krepling_utils',
                    ],
                    file_exists($js_base_path . 'krepling.js')
                        ? filemtime($js_base_path . 'krepling.js')
                        : KREPLING_PLUGIN_VERSION,
                    true
                );

                $krepling_helpers_path = KREPLING_PLUGIN_DIR . 'assets/js/krepling-helpers.js';
                if (file_exists($krepling_helpers_path)) {
                    wp_add_inline_script(
                        'krepling_main',
                        'window.kreplingConfig = ' . wp_json_encode(krepling_get_frontend_script_data()) . ';',
                        'before'
                    );

                    wp_add_inline_script(
                        'krepling_main',
                        file_get_contents($krepling_helpers_path),
                        'before'
                    );
                }

                $krepling_ui_navigation_path = KREPLING_PLUGIN_DIR . 'assets/js/krepling-ui-navigation.js';
                if (file_exists($krepling_ui_navigation_path)) {
                    wp_add_inline_script(
                        'krepling_main',
                        file_get_contents($krepling_ui_navigation_path),
                        'before'
                    );
                }

                $krepling_ui_inputs_path = KREPLING_PLUGIN_DIR . 'assets/js/krepling-ui-inputs.js';
                if (file_exists($krepling_ui_inputs_path)) {
                    wp_add_inline_script(
                        'krepling_main',
                        file_get_contents($krepling_ui_inputs_path),
                        'before'
                    );
                }

                $checkout_sync_script = <<<'JS'
        jQuery(function($) {
            var kreplingPhoneDebugObserverStarted = false;

            function startPhoneDebugObserver() {
                if (kreplingPhoneDebugObserverStarted) {
                    return;
                }

                var target = document.body;
                if (!target) {
                    return;
                }

                kreplingPhoneDebugObserverStarted = true;

                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.removedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && (
                                (node.id && node.id === 'phoneNumberIds') ||
                                (node.classList && node.classList.contains('iti'))
                            )) {
                                console.log('[Krepling phone] removed node', node);
                            }
                        });

                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && (
                                (node.id && node.id === 'phoneNumberIds') ||
                                (node.classList && node.classList.contains('iti'))
                            )) {
                                console.log('[Krepling phone] added node', node);
                            }
                        });
                    });
                });

                observer.observe(target, { childList: true, subtree: true });
                console.log('[Krepling phone] MutationObserver started');
            }

            startPhoneDebugObserver();


            function kreplingSyncPaymentBox() {
                var $checkedMethod = $('form.checkout').find('input[name="payment_method"]:checked');

                if (!$checkedMethod.length) {
                    return;
                }

                var selectedMethod = $checkedMethod.val();
                var $allBoxes = $('.woocommerce-checkout-payment .payment_box');
                var $selectedBox = $('.woocommerce-checkout-payment .payment_box.payment_method_' + selectedMethod);

                if (!$allBoxes.length || !$selectedBox.length) {
                    return;
                }

                $allBoxes.not($selectedBox).stop(true, true).slideUp(0);
                $selectedBox.stop(true, true).slideDown(0);
            }


            function bindDelegatedEvents() {
                $(document)
                    .off('focusout.kreplingFix', '#emailAddress')
                    .on('focusout.kreplingFix', '#emailAddress', function() {
                        if (typeof getSignupOtp === 'function') {
                            getSignupOtp();
                        }
                    });

                $(document)
                    .off('click.kreplingFix', '#sendSmsOtpAgain')
                    .on('click.kreplingFix', '#sendSmsOtpAgain', function() {
                        if (typeof getSignupOtp === 'function') {
                            getSignupOtp();
                        }
                    });

                $(document)
                    .off('keyup.kreplingFix', '#verifyEmailOtp')
                    .on('keyup.kreplingFix', '#verifyEmailOtp', function() {
                        var verifyEmail = $('#emailAddress').val();
                        var otpInputs = document.querySelectorAll('#SMSArea input[type=tel]');
                        var otp = Array.from(otpInputs).map(function(x) { return x.value; }).join('');

                        if (otp.length !== 6) {
                            return;
                        }

                        $.ajax({
                            url: (window.parseData ? (window.parseData.ajax_url || (window.parseData.base_url + 'controller.php')) : ''),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'verifyOTP',
                                email: verifyEmail,
                                otp: otp
                            },
                            success: function(response) {
                                if (response.status == 1) {
                                    $('#btnFirstStep').removeClass('disabled');
                                    $('#stepThirdId').hide();
                                    $('.otpverified_section').removeClass('hideclass');
                                    $('.otpverified_section .common_toster').addClass('success_toster_messsage');
                                } else {
                                    $('#SMSArea input[type=tel]').addClass('addErrorBorder');
                                    $('.sendotp_section').removeClass('hideclass');
                                    $('.sendotp_section .common_toster').addClass('error_toster_messsage');
                                    $('.sendotp_section .toster_message').text(response.message);
                                }

                                if (typeof removeValidationError === 'function') {
                                    removeValidationError();
                                }
                            }
                        });
                    });
            }

            function restoreOriginalOtpBackend() {
                if (typeof window.kreplingUseServicesForAction === 'function') {
                    window.kreplingUseServicesForAction = function() {
                        return false;
                    };
                }
            }

            function initPhoneInputs() {
                $('#phoneNumberIds, #otp_phoneNumber').each(function() {
                    var $input = $(this);

                    console.log('[Krepling phone] initPhoneInputs called for', this.id, {
                        hasElement: !!$input.length,
                        hasWrapper: $input.closest('.iti').length > 0,
                        hasPluginFn: typeof $input.intlTelInput === 'function',
                        currentValue: $input.val(),
                        currentStyle: $input.attr('style') || '(none)'
                    });

                    if (!$input.length) {
                        console.log('[Krepling phone] skipping - no input found', this.id);
                        return;
                    }

                    if ($input.closest('.iti').length) {
                        console.log('[Krepling phone] skipping - already wrapped', this.id, $input.closest('.iti')[0]);
                        return;
                    }

                    if (typeof $input.intlTelInput !== 'function') {
                        console.log('[Krepling phone] skipping - intlTelInput jQuery function missing', this.id);
                        return;
                    }

                    console.log('[Krepling phone] initializing intlTelInput for', this.id);

                    $input.intlTelInput({
                        autoHideDialCode: true,
                        autoPlaceholder: 'ON',
                        dropdownContainer: document.body,
                        formatOnDisplay: true,
                        nationalMode: true,
                        placeholderNumberType: 'MOBILE',
                        preferredCountries: [],
                        separateDialCode: true,
                        initialCountry: (window.kreplingDetectCountryCode ? window.kreplingDetectCountryCode() : 'us')
                    });

                    setTimeout(function() {
                        console.log('[Krepling phone] post-init check for', $input.attr('id'), {
                            wrapperExists: $input.closest('.iti').length > 0,
                            styleNow: $input.attr('style') || '(none)',
                            valueNow: $input.val()
                        });

                        $input.trigger('countrychange');
                    }, 50);
                });
            }

            function initKreplingFixes(triggerSource) {
                var $phone = $('#phoneNumberIds');

                console.log('[Krepling phone] initKreplingFixes start', {
                    triggerSource: triggerSource || 'unknown',
                    phoneExists: $phone.length > 0,
                    phoneStyleBefore: $phone.attr('style') || '(none)',
                    phoneHasWrapperBefore: $phone.closest('.iti').length > 0,
                    phoneValueBefore: $phone.val()
                });

                restoreOriginalOtpBackend();
                kreplingSyncPaymentBox();
                bindDelegatedEvents();
                initPhoneInputs();

                console.log('[Krepling phone] initKreplingFixes end', {
                    triggerSource: triggerSource || 'unknown',
                    phoneStyleAfter: $phone.attr('style') || '(none)',
                    phoneHasWrapperAfter: $phone.closest('.iti').length > 0,
                    phoneValueAfter: $phone.val(),
                    wrapperHtml: $phone.closest('.iti').length ? $phone.closest('.iti')[0].outerHTML : '(no wrapper)'
                });
            }

            $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                console.log('[Krepling phone] payment method changed');
                $(document.body).trigger('update_checkout');
                setTimeout(function() { initKreplingFixes('payment_method_change'); }, 0);
            });

            $(document.body).on('updated_checkout', function() {
                console.log('[Krepling phone] WooCommerce event: updated_checkout');
                setTimeout(function() { initKreplingFixes('updated_checkout'); }, 0);
            });

            $(document.body).on('init_checkout', function() {
                console.log('[Krepling phone] WooCommerce event: init_checkout');
                setTimeout(function() { initKreplingFixes('init_checkout'); }, 0);
            });

            setTimeout(function() { initKreplingFixes('timeout_0'); }, 0);
            setTimeout(function() { initKreplingFixes('timeout_300'); }, 300);

            $(window).on('load', function() {
                console.log('[Krepling phone] window load');
                initKreplingFixes('window_load');
            });
        });
        JS;

                wp_add_inline_script('krepling_main', $checkout_sync_script, 'after');
            }
        }
      /*
       * Fields validation, more in Step 5
       */
        public function validate_fields()
        {
            if (!WC()->cart || WC()->cart->is_empty()) {
                wc_add_notice(__('Your cart is empty.', 'krepling-pay-for-woocommerce'), 'error');
                return false;
            }

            $merchant_id = trim((string) $this->get_option('merchant_id'));
            $secret_id   = trim((string) $this->get_option('secret_id'));

            if ($merchant_id === '' || $secret_id === '') {
                wc_add_notice(__('Krepling payment is not configured correctly. Please contact the store administrator.', 'krepling-pay-for-woocommerce'), 'error');
                return false;
            }

            return true;
        }

      /*
       * We're processing the payments here, everything about it is in Step 5
       */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            if (!($order instanceof WC_Order)) {
                wc_add_notice(__('Unable to start Krepling checkout. Please try again.', 'krepling-pay-for-woocommerce'), 'error');
                return [
                    'result' => 'failure',
                ];
            }

            if ((float) $order->get_total() <= 0) {
                $order->payment_complete();

                return [
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                ];
            }

            // Do not empty the cart and do not redirect to thank-you yet.
            // The order should remain pending until the remote Krepling API confirms payment.
            $order->update_status('pending', __('Awaiting confirmed Krepling payment response.', 'krepling-pay-for-woocommerce'));

            return [
                'result'   => 'success',
                'redirect' => wc_get_checkout_url(),
            ];
        }

      /*
       * In case you need a webhook, like PayPal IPN etc
       */
        public function webhook()
        {
        }

      /*
       * Customize the payment admin options of gateway
       */
        public function process_admin_options()
        {
            if (!current_user_can('manage_woocommerce')) {
                WC_Admin_Settings::add_error(__('You are not allowed to update Krepling settings.', 'krepling-pay-for-woocommerce'));
                return false;
            }

            check_admin_referer('woocommerce-settings');

            if (
                isset($_FILES['woocommerce_krepling_plugin_logo']) &&
                is_array($_FILES['woocommerce_krepling_plugin_logo']) &&
                !empty($_FILES['woocommerce_krepling_plugin_logo']['name'])
            ) {
                $this->upload_krepling_logo();
            }
            $delete_image = false;
            if (isset($_POST['delete_image']) && !is_array($_POST['delete_image'])) {
                $delete_image = rest_sanitize_boolean(wp_unslash((string) $_POST['delete_image']));
            }

            if ($delete_image) {
                delete_option('woocommerce_krepling_logo');
            }
            $saved = parent::process_admin_options();
            return $saved;
        }

      /*
       * Customize the upload payment method logo which will show on checkout page
       */
      public function upload_krepling_logo()
      {
          if (!current_user_can('manage_woocommerce')) {
              WC_Admin_Settings::add_error(__('You are not allowed to upload a logo.', 'krepling-pay-for-woocommerce'));
              return;
          }

          require_once(ABSPATH . 'wp-admin/includes/image.php');
          require_once(ABSPATH . 'wp-admin/includes/file.php');
          require_once(ABSPATH . 'wp-admin/includes/media.php');

          // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified in process_admin_options(); raw file uploads are validated below before use.
          if (empty($_FILES['woocommerce_krepling_plugin_logo']) || !is_array($_FILES['woocommerce_krepling_plugin_logo'])) {
              WC_Admin_Settings::add_error('No file was uploaded.');
              return;
          }

          // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified in process_admin_options(); raw file uploads are validated below before use.
          $uploaded_file = $_FILES['woocommerce_krepling_plugin_logo'];

          if (!empty($uploaded_file['error'])) {
              WC_Admin_Settings::add_error('Upload failed. Please try again.');
              return;
          }

          if (empty($uploaded_file['tmp_name']) || !is_uploaded_file($uploaded_file['tmp_name'])) {
              WC_Admin_Settings::add_error('Invalid upload source.');
              return;
          }

          // Optional size cap: 2 MB
          $max_file_size = 2 * 1024 * 1024;
          if (!empty($uploaded_file['size']) && (int) $uploaded_file['size'] > $max_file_size) {
              WC_Admin_Settings::add_error('Logo must be 2 MB or smaller.');
              return;
          }

          // Only allow safe raster image types. SVG is intentionally blocked.
          $allowed_mimes = [
              'jpg|jpeg|jpe' => 'image/jpeg',
              'png'          => 'image/png',
              'webp'         => 'image/webp',
              'gif'          => 'image/gif',
          ];

          $check = wp_check_filetype_and_ext(
              $uploaded_file['tmp_name'],
              $uploaded_file['name'],
              $allowed_mimes
          );

          if (
              empty($check['ext']) ||
              empty($check['type']) ||
              !in_array($check['type'], array_values($allowed_mimes), true)
          ) {
              WC_Admin_Settings::add_error('Invalid file type. Please upload JPG, PNG, WEBP, or GIF.');
              return;
          }

          // Verify actual image content, not just extension/MIME.
          $image_info = @getimagesize($uploaded_file['tmp_name']);
          if ($image_info === false) {
              WC_Admin_Settings::add_error('Uploaded file is not a valid image.');
              return;
          }

          $image_mime = isset($image_info['mime']) ? $image_info['mime'] : '';
          if (!in_array($image_mime, array_values($allowed_mimes), true)) {
              WC_Admin_Settings::add_error('Image content does not match an allowed format.');
              return;
          }

          $safe_name = sanitize_file_name($uploaded_file['name']);
          $uploadedfile = [
              'name'     => $safe_name,
              'type'     => $check['type'],
              'tmp_name' => $uploaded_file['tmp_name'],
              'error'    => $uploaded_file['error'],
              'size'     => $uploaded_file['size'],
          ];

          $upload_overrides = [
              'test_form' => false,
              'mimes'     => $allowed_mimes,
          ];

          $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

          if ($movefile && !isset($movefile['error']) && !empty($movefile['url']) && !empty($movefile['file'])) {
              $real_mime = function_exists('wp_get_image_mime') ? wp_get_image_mime($movefile['file']) : '';
              if (!in_array($real_mime, array_values($allowed_mimes), true)) {
                  wp_delete_file($movefile['file']);
                  WC_Admin_Settings::add_error('Uploaded file failed final validation.');
                  return;
              }

              update_option('woocommerce_krepling_logo', esc_url_raw($movefile['url']));
              WC_Admin_Settings::add_message('Logo has been uploaded');
              return;
          }

          WC_Admin_Settings::add_error(!empty($movefile['error']) ? $movefile['error'] : 'Upload failed.');
      }
      
      /*
       * Show the uploaded logo image under upload logo field
       */
        public function admin_options()
        {
            if (!current_user_can('manage_woocommerce')) {
                return;
            }

            parent::admin_options();
            $logo_file = get_option('woocommerce_krepling_logo');
            if (isset($logo_file) && !empty($logo_file)) {
                $url = $logo_file;
            } else {
                $url = KREPLING_PLUGIN_URL . 'assets/images/krepling-logo.svg';
            }
            ?>
        <img src="<?php echo esc_url($url); ?>" class="krepling_uploaded_logo_image" alt="<?php echo  esc_attr(basename((string) $logo_file)); ?>"></br>
        <input type="checkbox" name="delete_image" class="delete_uploaded_image">Delete Image
            <?php
        }

      /*
       * Enable the upload functionality of svg+xml, webp file type images
       */
        public function wp_mime_types($mimes)
        {
            $mimes['webp'] = 'image/webp';
            unset($mimes['svg']);
            return $mimes;
        }

        public function add_meta_tags()
        {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">';
        }
    }
}

/*
 * Remove default place order button for Krepling payment method and apply validations
 */
add_filter('woocommerce_order_button_html', 'krepling_remove_place_order_button_for_specific_payments');
function krepling_remove_place_order_button_for_specific_payments($button)
{
    // Here define your targeted payment(s) method(s) in the array
    $targeted_payments_methods = array('krepling');
    $chosen_payment_method     = WC()->session->get('chosen_payment_method'); // The chosen payment
    // For matched payment(s) method(s), we remove place order button (on checkout page)
    if (in_array($chosen_payment_method, $targeted_payments_methods) && ! is_wc_endpoint_url()) {
        $button = '';
        wc_clear_notices();
        if (empty(WC()->customer->get_billing_first_name())) {
            wc_add_notice('First name is required field', 'error');
        } elseif (empty(WC()->customer->get_billing_last_name())) {
            wc_add_notice('Last Name is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_country())) {
            wc_add_notice('Country is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_address_1())) {
            wc_add_notice('Street Address is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_city())) {
            wc_add_notice('Town / City is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_state())) {
            wc_add_notice('Province / State is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_postcode())) {
            wc_add_notice('Postcode / ZIP is a required field', 'error');
        } elseif (empty(WC()->customer->get_billing_phone())) {
            wc_add_notice('Phone is a required field', 'error');
        }
    }
    return $button;
}

/*
 * Update checkout on payment method change
 */
add_action('wp_footer', 'krepling_custom_checkout_jquery_script');
function krepling_custom_checkout_jquery_script()
{
    return;
}
