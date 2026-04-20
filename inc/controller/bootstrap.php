<?php

defined('ABSPATH') || exit;

function krepling_request_is_secure()
{
    if (function_exists('is_ssl')) {
        return is_ssl();
    }

    $https = isset($_SERVER['HTTPS']) && !is_array($_SERVER['HTTPS'])
        ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTPS']))
        : null;
    $server_port = isset($_SERVER['SERVER_PORT']) && !is_array($_SERVER['SERVER_PORT'])
        ? absint($_SERVER['SERVER_PORT'])
        : null;
    $forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !is_array($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_X_FORWARDED_PROTO']))
        : null;
    $forwarded_proto = is_string($forwarded_proto) ? trim(strtolower(strtok($forwarded_proto, ','))) : '';

    if (!empty($https) && strtolower((string) $https) !== 'off') {
        return true;
    }

    if (!empty($server_port) && 443 === (int) $server_port) {
        return true;
    }

    return 'https' === $forwarded_proto;
}

function krepling_env_flag($name, $default = false)
{
    if (defined($name)) {
        return (bool) constant($name);
    }

    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__, 2) . '/krepling-services-auth.php';
require_once dirname(__DIR__, 2) . '/krepling-session.php';

global $krepling_request_get_data, $krepling_request_post_data;

// Request data is captured at the controller/bootstrap layer; nonce verification for
// sensitive actions happens below once the requested action has been identified.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing
$krepling_request_get_data = isset($_GET) && is_array($_GET) ? wp_unslash($_GET) : [];
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing
$krepling_request_post_data = isset($_POST) && is_array($_POST) ? wp_unslash($_POST) : [];

if (!defined('KREPLING_API_URL')) {
    define('KREPLING_API_URL', 'https://api.krepling.com/api');
}

if (!defined('KREPLING_DEBUG_API_LOGS')) {
    define('KREPLING_DEBUG_API_LOGS', false);
}

if (!defined('KREPLING_IP_ADDRESS_API')) {
    define('KREPLING_IP_ADDRESS_API', 'https://ipinfo.io/json');
}

if (!defined('KREPLING_SERVICES_BASE')) {
    define('KREPLING_SERVICES_BASE', 'https://services.krepling.com');
}

$krepling_api_keys = get_option('woocommerce_krepling_settings');
$krepling_request_action = '';
$krepling_request_action_candidate = krepling_request_string('krepling_action', 'POST');

if ($krepling_request_action_candidate === '') {
    $krepling_request_action_candidate = krepling_request_string('krepling_action', 'GET');
}

if ($krepling_request_action_candidate === '') {
    $krepling_request_action_candidate = krepling_request_string('action', 'POST');
}

if ($krepling_request_action_candidate === '') {
    $krepling_request_action_candidate = krepling_request_string('action', 'GET');
}

if ($krepling_request_action_candidate === 'krepling_dispatch') {
    $krepling_request_action_candidate = '';
}

if ($krepling_request_action_candidate !== '') {
    $krepling_request_action = preg_replace('/[^A-Za-z0-9_]/', '', $krepling_request_action_candidate);
}

$krepling_action_response = '';

$krepling_sensitive_actions = [
    'getSignupEmailOtp',
    'verifyOTP',
    'user_login',
    'newUserSignup',
    'addAddress',
    'removeAddress',
    'updateAddress',
    'setDefaultAddress',
    'addPaymentCard',
    'deletCard',
    'change_user_password',
    'deleteUserAccount',
    'forgotAccountPassword',
    'changeEmailAddress',
    'verifyOtpEmailAddress',
    'changePhoneNumber',
    'verifyOtpPhoneNumber',
    'manageEnableFastKrepling',
    'pay',
    'verifyOrderPayment',
    'logoutSelectedDevices',
    'resend_smsOtp_action',
    'setDefaultAction',
    'thisDeviceWasMe',
    'smsLoginAlerts',
];

if (in_array($krepling_request_action, $krepling_sensitive_actions, true)) {
    krepling_verify_nonce_or_fail();
}

$krepling_wc_cart = null;

if (function_exists('WC')) {
    $krepling_wc = WC();

    if ($krepling_wc && isset($krepling_wc->cart) && $krepling_wc->cart) {
        $krepling_wc_cart = $krepling_wc->cart;
    }
}

$krepling_total = '0.00';
$krepling_subtotal = '0.00';
$krepling_shipping = '0.00';
$krepling_discount = '0.00';
$krepling_tax = '0.00';
$krepling_cart_data = array();
$krepling_count_product = 0;

if ($krepling_wc_cart) {
    $krepling_total = number_format((float) $krepling_wc_cart->total, 2, '.', '');
    $krepling_subtotal = number_format((float) $krepling_wc_cart->subtotal, 2, '.', '');
    $krepling_shipping = number_format((float) $krepling_wc_cart->shipping_total, 2, '.', '');
    $krepling_discount = number_format((float) $krepling_wc_cart->get_cart_discount_total(), 2, '.', '');
    $krepling_tax = number_format((float) $krepling_wc_cart->total_tax, 2, '.', '');
    $krepling_cart_data = $krepling_wc_cart->get_cart();
    $krepling_count_product = $krepling_wc_cart->get_cart_contents_count();
}
