<?php

defined('ABSPATH') || exit;

if (!function_exists('krepling_get_wc_session')) {
    function krepling_get_wc_session()
    {
        if (!function_exists('WC')) {
            return null;
        }

        $woocommerce = WC();
        if (!$woocommerce) {
            return null;
        }

        if ((!isset($woocommerce->session) || !is_object($woocommerce->session)) && method_exists($woocommerce, 'initialize_session')) {
            $woocommerce->initialize_session();
            $woocommerce = WC();
        }

        if ((!isset($woocommerce->session) || !is_object($woocommerce->session)) && function_exists('wc_load_cart')) {
            wc_load_cart();
            $woocommerce = WC();
        }

        if (!isset($woocommerce->session) || !is_object($woocommerce->session)) {
            return null;
        }

        return $woocommerce->session;
    }
}

if (!function_exists('krepling_plugin_session_keys')) {
    function krepling_plugin_session_keys()
    {
        return [
            'token_info',
            'current_browserName',
            'current_deviceLocation',
            'login_data',
            'userDetail',
            'id_user',
            'isPhoneVerified',
            'isEmailVerified',
            'phoneSetDefault',
            'emailSetDefault',
            'defaultCurrencySymbol',
            'defaultCurrencyAndSymbol',
            'defaultCurrencyName',
            'cart_amount',
            'listCurrency',
            'product_details',
            'reviewDeviceData',
            'krepling_pending_order_id',
        ];
    }
}

if (!function_exists('krepling_wc_session_get')) {
    function krepling_wc_session_get($key, $default = null)
    {
        $session = krepling_get_wc_session();
        if (!$session) {
            return $default;
        }

        $value = $session->get($key, null);

        return $value === null ? $default : $value;
    }
}

if (!function_exists('krepling_wc_session_has')) {
    function krepling_wc_session_has($key)
    {
        return krepling_wc_session_get($key, null) !== null;
    }
}

if (!function_exists('krepling_wc_session_set')) {
    function krepling_wc_session_set($key, $value)
    {
        $session = krepling_get_wc_session();
        if (!$session) {
            return;
        }

        $session->set($key, $value);
    }
}

if (!function_exists('krepling_wc_session_forget')) {
    function krepling_wc_session_forget($key)
    {
        $session = krepling_get_wc_session();
        if (!$session) {
            return;
        }

        if (method_exists($session, '__unset')) {
            $session->__unset($key);
            return;
        }

        $session->set($key, null);
    }
}

if (!function_exists('krepling_clear_plugin_session_state')) {
    function krepling_clear_plugin_session_state()
    {
        foreach (krepling_plugin_session_keys() as $key) {
            krepling_wc_session_forget($key);
        }
    }
}

if (!function_exists('krepling_wc_session_login_data')) {
    function krepling_wc_session_login_data()
    {
        $loginData = krepling_wc_session_get('login_data', []);

        return is_array($loginData) ? $loginData : [];
    }
}

if (!function_exists('krepling_wc_session_login_email')) {
    function krepling_wc_session_login_email()
    {
        $loginData = krepling_wc_session_login_data();

        return isset($loginData['email']) ? sanitize_email((string) $loginData['email']) : '';
    }
}

if (!function_exists('krepling_wc_session_user_detail')) {
    function krepling_wc_session_user_detail()
    {
        return krepling_wc_session_get('userDetail');
    }
}

if (!function_exists('krepling_wc_session_user_id')) {
    function krepling_wc_session_user_id()
    {
        $userDetail = krepling_wc_session_user_detail();

        if (
            is_object($userDetail) &&
            isset($userDetail->userVM) &&
            is_object($userDetail->userVM) &&
            isset($userDetail->userVM->userId)
        ) {
            return absint($userDetail->userVM->userId);
        }

        return absint(krepling_wc_session_get('id_user', 0));
    }
}

if (!function_exists('krepling_wc_session_cart_amount')) {
    function krepling_wc_session_cart_amount()
    {
        $cartAmount = krepling_wc_session_get('cart_amount', []);

        return is_array($cartAmount) ? $cartAmount : [];
    }
}

if (!function_exists('krepling_wc_session_list_currency')) {
    function krepling_wc_session_list_currency()
    {
        $listCurrency = krepling_wc_session_get('listCurrency', []);

        return is_array($listCurrency) ? $listCurrency : [];
    }
}

if (!function_exists('krepling_wc_session_review_devices')) {
    function krepling_wc_session_review_devices()
    {
        $reviewDevices = krepling_wc_session_get('reviewDeviceData', []);

        if (is_array($reviewDevices)) {
            return $reviewDevices;
        }

        return empty($reviewDevices) ? [] : [$reviewDevices];
    }
}

if (!function_exists('krepling_cookie_value')) {
    function krepling_cookie_value($key, $default = '')
    {
        if (!isset($_COOKIE[$key]) || is_array($_COOKIE[$key])) {
            return $default;
        }

        return sanitize_text_field(wp_unslash((string) $_COOKIE[$key]));
    }
}
