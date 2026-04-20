<?php
defined('ABSPATH') || exit;

function krepling_fail_json($status, $message, $extra = [])
{
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode(array_merge([
        'status' => $status,
        'message' => $message,
    ], $extra));
    exit;
}

function krepling_service_proxy_or_error($path, array $payload)
{
    $response = krepling_services_json_request('POST', $path, $payload, true);

    if (is_wp_error($response)) {
        krepling_fail_json(502, $response->get_error_message(), [
            'error_data' => $response->get_error_data(),
        ]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($body)) {
        krepling_fail_json(502, 'Invalid response from krepling-services.');
    }

    return $body;
}

function krepling_service_extract_upstream_data(array $serviceBody)
{
    return (isset($serviceBody['data']) && is_array($serviceBody['data']))
        ? $serviceBody['data']
        : array();
}

function krepling_service_extract_status(array $upstream, $default = 500)
{
    return isset($upstream['status']) ? (int) $upstream['status'] : (int) $default;
}

function krepling_service_extract_message(array $upstream, $default = 'Request failed.')
{
    return !empty($upstream['message']) ? (string) $upstream['message'] : (string) $default;
}

function krepling_json_response(array $payload)
{
    return wp_json_encode($payload);
}

function krepling_service_status_message_response(array $upstream, $defaultMessage, array $extra = array())
{
    return krepling_json_response(array_merge(array(
        'status'  => krepling_service_extract_status($upstream, 500),
        'message' => krepling_service_extract_message($upstream, $defaultMessage),
    ), $extra));
}

function krepling_service_private_upstream($path, array $payload)
{
    $serviceBody = krepling_service_proxy_or_error($path, $payload);
    return krepling_service_extract_upstream_data($serviceBody);
}

function krepling_service_private_response($path, array $payload)
{
    return krepling_service_proxy_or_error($path, $payload);
}

function krepling_service_extract_array_value(array $upstream, $key, $default = array())
{
    return (isset($upstream[$key]) && is_array($upstream[$key])) ? $upstream[$key] : $default;
}

function krepling_store_authenticated_user_session_from_service(array $loginUpstream, $browserName, $deviceLocation, $fallbackEmail = '')
{
    $token = !empty($loginUpstream['token']) ? (string) $loginUpstream['token'] : '';
    $userDetail = krepling_service_extract_array_value($loginUpstream, 'user_detail', array());

    if ($token === '' || empty($userDetail)) {
        return false;
    }

    $loginEmail = sanitize_email((string) $fallbackEmail);

    if (
        isset($loginUpstream['userCheckoutVM']) &&
        is_array($loginUpstream['userCheckoutVM']) &&
        !empty($loginUpstream['userCheckoutVM']['email'])
    ) {
        $loginEmail = sanitize_email((string) $loginUpstream['userCheckoutVM']['email']);
    }

    if ($loginEmail === '') {
        return false;
    }

    krepling_regenerate_session_after_login();
    krepling_wc_session_set('token_info', $token);
    krepling_wc_session_set('current_browserName', $browserName);
    krepling_wc_session_set('current_deviceLocation', $deviceLocation);
    krepling_wc_session_set('login_data', array(
        'email' => $loginEmail,
    ));

    $userDetailObject = json_decode(wp_json_encode($userDetail));
    if ($userDetailObject) {
        krepling_store_user_detail_state($userDetailObject);
    }

    return true;
}

function krepling_verify_nonce_or_fail()
{
    $nonce = krepling_request_string('_wpnonce', 'POST');

    if ($nonce === '') {
        $nonce = krepling_request_string('_wpnonce', 'GET');
    }

    if (!$nonce || !wp_verify_nonce($nonce, 'krepling_action_nonce')) {
        krepling_fail_json(403, 'Security check failed. Please refresh the page and try again.');
    }
}

function krepling_cookie_options($expires)
{
    return [
        'expires' => $expires,
        'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'domain' => defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
        'secure' => krepling_request_is_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function krepling_set_cookie($name, $value, $expires)
{
    setcookie($name, $value, krepling_cookie_options($expires));
}

function krepling_clear_cookie($name)
{
    setcookie($name, '', krepling_cookie_options(time() - (86400 * 14)));
}

function krepling_clear_legacy_password_cookie()
{
    krepling_clear_cookie('kp_user_password');
}

function krepling_regenerate_session_after_login()
{
    $session = krepling_get_wc_session();

    if ($session && method_exists($session, 'set_customer_session_cookie')) {
        $session->set_customer_session_cookie(true);
    }
}

function krepling_destroy_current_session()
{
    krepling_clear_legacy_password_cookie();
    krepling_clear_plugin_session_state();
}

function krepling_store_user_detail_state($userDetail)
{
    krepling_wc_session_set('userDetail', $userDetail);
    krepling_wc_session_set('id_user', krepling_wc_session_user_id());

    if (
        is_object($userDetail) &&
        isset($userDetail->userVM) &&
        is_object($userDetail->userVM)
    ) {
        if (isset($userDetail->userVM->isPhoneVerified)) {
            krepling_wc_session_set('isPhoneVerified', $userDetail->userVM->isPhoneVerified);
        }

        if (isset($userDetail->userVM->isEmailVerified)) {
            krepling_wc_session_set('isEmailVerified', $userDetail->userVM->isEmailVerified);
        }

        if (isset($userDetail->userVM->phoneSetDefault)) {
            krepling_wc_session_set('phoneSetDefault', $userDetail->userVM->phoneSetDefault);
        }

        if (isset($userDetail->userVM->emailSetDefault)) {
            krepling_wc_session_set('emailSetDefault', $userDetail->userVM->emailSetDefault);
        }
    }
}

function krepling_validation_error($message, $extra = [], $status = 422)
{
    krepling_fail_json($status, $message, $extra);
}

function krepling_request_source($method = 'POST')
{
    global $krepling_request_get_data, $krepling_request_post_data;

    if (strtoupper((string) $method) === 'GET') {
        return is_array($krepling_request_get_data) ? $krepling_request_get_data : [];
    }

    return is_array($krepling_request_post_data) ? $krepling_request_post_data : [];
}

function krepling_sanitize_request_array($value)
{
    if (!is_array($value)) {
        return sanitize_text_field(wp_unslash((string) $value));
    }

    $sanitized = [];

    foreach ($value as $key => $item) {
        $sanitized_key = is_int($key) ? $key : sanitize_key((string) $key);
        $sanitized[$sanitized_key] = krepling_sanitize_request_array($item);
    }

    return $sanitized;
}

function krepling_request_param($key, $method = 'POST', $default = null)
{
    $source = krepling_request_source($method);

    if (!isset($source[$key])) {
        return $default;
    }

    $value = $source[$key];

    if (is_array($value)) {
        return krepling_sanitize_request_array($value);
    }

    return wp_unslash((string) $value);
}

function krepling_request_string($key, $method = 'POST', $default = '')
{
    $value = krepling_request_param($key, $method, $default);

    if (is_array($value)) {
        return $default;
    }

    return trim(sanitize_text_field((string) $value));
}

function krepling_request_raw_string($key, $method = 'POST', $default = '')
{
    $value = krepling_request_param($key, $method, $default);

    if (is_array($value)) {
        return $default;
    }

    return trim((string) $value);
}

function krepling_request_email($key, $method = 'POST', $default = '')
{
    return sanitize_email((string) krepling_request_string($key, $method, $default));
}

function krepling_request_digits($key, $method = 'POST', $default = '')
{
    return preg_replace('/\D+/', '', (string) krepling_request_string($key, $method, $default));
}

function krepling_request_bool($key, $method = 'POST', $default = false)
{
    $value = krepling_request_param($key, $method, null);

    if ($value === null) {
        return (bool) $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string) $value));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return (bool) $default;
}

function krepling_request_int($key, $method = 'POST', $default = 0)
{
    return absint(krepling_request_string($key, $method, (string) $default));
}

function krepling_request_int_array($key, $method = 'POST')
{
    $values = krepling_request_param($key, $method, []);

    if (!is_array($values)) {
        return [];
    }

    $normalized = array_map('absint', $values);

    return array_values(array_filter($normalized));
}

function krepling_is_valid_email_address($email)
{
    return !empty($email) && is_email($email);
}

function krepling_is_valid_name($name)
{
    return preg_match("/^[A-Za-z][A-Za-z' -]* [A-Za-z][A-Za-z' -]*$/", (string) $name) === 1;
}

function krepling_is_valid_phone_number($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    return (bool) preg_match('/^\d{7,15}$/', $digits);
}

function krepling_is_valid_country_dial_code($countryCode)
{
    return (bool) preg_match('/^\+?\d{1,4}$/', (string) $countryCode);
}

function krepling_is_valid_otp($otp)
{
    return (bool) preg_match('/^\d{6}$/', (string) $otp);
}

function krepling_is_valid_password($password)
{
    return (bool) preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%&*]).{6,}$/', (string) $password);
}

function krepling_detect_card_type($cardNumber)
{
    $cardNumber = preg_replace('/\D+/', '', (string) $cardNumber);

    $patterns = [
        'visa' => '/^4\d{12}(\d{3})?$/',
        'mastercard' => '/^(5[1-5]\d{14}|2(2(2[1-9]|[3-9]\d)|[3-6]\d{2}|7([01]\d|20))\d{12})$/',
        'discover' => '/^6(?:011|5\d{2})\d{12}$/',
        'amex' => '/^3[47]\d{13}$/',
        'diners' => '/^3(?:0[0-5]|[68]\d)\d{11}$/',
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
    ];

    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $cardNumber)) {
            return $type;
        }
    }

    return '';
}

function krepling_passes_luhn($cardNumber)
{
    $cardNumber = preg_replace('/\D+/', '', (string) $cardNumber);
    $sum = 0;
    $alt = false;

    for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
        $digit = (int) $cardNumber[$i];

        if ($alt) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }

        $sum += $digit;
        $alt = !$alt;
    }

    return $cardNumber !== '' && $sum % 10 === 0;
}

function krepling_is_valid_expiry($expiry)
{
    $expiry = trim((string) $expiry);
    $matches = [];

    if (!preg_match('/^(\d{2})(?:\/)?(\d{2})$/', $expiry, $matches)) {
        return false;
    }

    $month = (int) $matches[1];
    $year = (int) $matches[2];
    $currentMonth = (int) gmdate('n');
    $currentYear = (int) gmdate('y');
    $maxYear = $currentYear + 25;

    if ($month < 1 || $month > 12) {
        return false;
    }

    if ($year < $currentYear || $year > $maxYear) {
        return false;
    }

    if ($year === $currentYear && $month <= $currentMonth) {
        return false;
    }

    return true;
}

function krepling_validate_address_payload($address, $label = 'shipping', $extra = [])
{
    $prefix = strtolower((string) $label) === 'billing' ? 'billing' : 'shipping';

    if (empty($address['streetAddress1'])) {
        krepling_validation_error($prefix === 'billing' ? 'Enter your billing address' : 'Enter your shipping address', $extra);
    }

    if (empty($address['city'])) {
        krepling_validation_error('Enter your city', $extra);
    }

    if (empty($address['country'])) {
        krepling_validation_error('Select your country', $extra);
    }

    if (empty($address['zipCode'])) {
        krepling_validation_error('Enter your zip code', $extra);
    }

    if (empty($address['state'])) {
        krepling_validation_error('Select your state', $extra);
    }

    return $address;
}

function krepling_validate_card_payload($cardNumber, $expiry, $cvv, $cardHolderName, $providedCardType = '', $extra = [])
{
    $normalizedNumber = preg_replace('/\D+/', '', (string) $cardNumber);
    $normalizedCvv = preg_replace('/\D+/', '', (string) $cvv);
    $providedCardType = sanitize_key((string) $providedCardType);

    if ($normalizedNumber === '') {
        krepling_validation_error('Enter a valid card number', $extra);
    }

    if (!krepling_is_valid_expiry($expiry)) {
        krepling_validation_error('Enter a valid expiration date', $extra);
    }

    if (!preg_match('/^\d{3,4}$/', $normalizedCvv)) {
        krepling_validation_error('Enter a valid CVV or security code', $extra);
    }

    if (!krepling_is_valid_name($cardHolderName)) {
        krepling_validation_error("Enter your name exactly as it's written on your card", $extra);
    }

    $detectedCardType = krepling_detect_card_type($normalizedNumber);
    if ($detectedCardType === '' || !krepling_passes_luhn($normalizedNumber)) {
        krepling_validation_error('We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.', $extra);
    }

    if ($providedCardType !== '' && $providedCardType !== $detectedCardType) {
        krepling_validation_error('We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.', $extra);
    }

    return [
        'number' => $normalizedNumber,
        'expiry' => trim((string) $expiry),
        'cvv' => $normalizedCvv,
        'card_holder_name' => trim((string) $cardHolderName),
        'card_type' => $detectedCardType,
    ];
}

function krepling_validate_wc_checkout_required_order_fields($extra = [])
{
    $requiredFields = [
        'billing_first_name' => [
            'getter' => 'get_billing_first_name',
            'message' => 'First name is a required field',
        ],
        'billing_last_name' => [
            'getter' => 'get_billing_last_name',
            'message' => 'Last name is a required field',
        ],
        'billing_email' => [
            'getter' => 'get_billing_email',
            'message' => 'Email address is a required field',
        ],
        'billing_country' => [
            'getter' => 'get_billing_country',
            'message' => 'Country is a required field',
        ],
        'billing_address_1' => [
            'getter' => 'get_billing_address_1',
            'message' => 'Street address is a required field',
        ],
        'billing_city' => [
            'getter' => 'get_billing_city',
            'message' => 'Town / City is a required field',
        ],
        'billing_state' => [
            'getter' => 'get_billing_state',
            'message' => 'Province / State is a required field',
        ],
        'billing_postcode' => [
            'getter' => 'get_billing_postcode',
            'message' => 'Postcode / ZIP is a required field',
        ],
        'billing_phone' => [
            'getter' => 'get_billing_phone',
            'message' => 'Phone is a required field',
        ],
    ];

    $customer = null;
    if (function_exists('WC')) {
        $wc = WC();
        if ($wc && isset($wc->customer) && is_object($wc->customer)) {
            $customer = $wc->customer;
        }
    }

    foreach ($requiredFields as $requestKey => $fieldConfig) {
        $value = krepling_request_string($requestKey);
        if ($value === 'undefined') {
            $value = '';
        }

        if (
            $value === '' &&
            $customer &&
            method_exists($customer, $fieldConfig['getter'])
        ) {
            $value = trim((string) $customer->{$fieldConfig['getter']}());
        }

        if (trim((string) $value) === '') {
            krepling_validation_error($fieldConfig['message'], $extra);
        }
    }
}

function krepling_require_checkout_authentication($extra = [])
{
    $userDetail = krepling_wc_session_user_detail();
    $token = (string) krepling_wc_session_get('token_info', '');
    $loginEmail = krepling_wc_session_login_email();

    if (
        empty($token) ||
        empty($loginEmail) ||
        !is_object($userDetail) ||
        !isset($userDetail->userVM) ||
        !is_object($userDetail->userVM) ||
        empty($userDetail->userVM->userId)
    ) {
        krepling_validation_error('Your session has expired. Please sign in again.', $extra, 401);
    }

    return [
        'token' => $token,
        'login_email' => $loginEmail,
        'user_detail' => $userDetail,
        'user_id' => absint($userDetail->userVM->userId),
    ];
}

function krepling_find_checkout_address($userDetail, $addressId)
{
    if (
        !is_object($userDetail) ||
        !isset($userDetail->userVM) ||
        !is_object($userDetail->userVM) ||
        empty($userDetail->userVM->checkoutAddress) ||
        !is_array($userDetail->userVM->checkoutAddress)
    ) {
        return null;
    }

    foreach ($userDetail->userVM->checkoutAddress as $address) {
        if (isset($address->id) && absint($address->id) === absint($addressId)) {
            return $address;
        }
    }

    return null;
}

function krepling_find_payment_method($userDetail, $cardId)
{
    if (
        !is_object($userDetail) ||
        !isset($userDetail->paymentMethodVM) ||
        empty($userDetail->paymentMethodVM) ||
        !is_array($userDetail->paymentMethodVM)
    ) {
        return null;
    }

    foreach ($userDetail->paymentMethodVM as $paymentMethod) {
        if (isset($paymentMethod->cardId) && (string) $paymentMethod->cardId === (string) $cardId) {
            return $paymentMethod;
        }
    }

    return null;
}

function krepling_normalize_address_payload($streetAddress1, $streetAddress2, $city, $state, $country, $zipCode)
{
    return [
        'streetAddress1' => trim(sanitize_text_field((string) $streetAddress1)),
        'streetAddress2' => trim(sanitize_text_field((string) $streetAddress2)),
        'city' => trim(sanitize_text_field((string) $city)),
        'state' => trim(sanitize_text_field((string) $state)),
        'country' => strtoupper(trim(sanitize_text_field((string) $country))),
        'zipCode' => trim(sanitize_text_field((string) $zipCode)),
    ];
}

function krepling_copy_billing_address_from_shipping($shippingAddress)
{
    return [
        'streetAddress1' => $shippingAddress['streetAddress1'],
        'streetAddress2' => $shippingAddress['streetAddress2'],
        'city' => $shippingAddress['city'],
        'state' => $shippingAddress['state'],
        'country' => $shippingAddress['country'],
        'zipCode' => $shippingAddress['zipCode'],
    ];
}

function krepling_wc_customer_address_payload($type = 'shipping')
{
    $type = strtolower((string) $type) === 'billing' ? 'billing' : 'shipping';
    $country = $type === 'billing'
        ? WC()->customer->get_billing_country()
        : WC()->customer->get_shipping_country();
    $state = $type === 'billing'
        ? WC()->customer->get_billing_state()
        : WC()->customer->get_shipping_state();

    return krepling_normalize_address_payload(
        $type === 'billing' ? WC()->customer->get_billing_address_1() : WC()->customer->get_shipping_address_1(),
        $type === 'billing' ? WC()->customer->get_billing_address_2() : WC()->customer->get_shipping_address_2(),
        $type === 'billing' ? WC()->customer->get_billing_city() : WC()->customer->get_shipping_city(),
        krepling_get_state_name($country, $state),
        $country,
        $type === 'billing' ? WC()->customer->get_billing_postcode() : WC()->customer->get_shipping_postcode()
    );
}

function krepling_require_checkout_address_by_id($userDetail, $addressId, $message, $extra = [])
{
    $addressId = absint($addressId);
    if ($addressId < 1) {
        krepling_validation_error($message, $extra);
    }

    $address = krepling_find_checkout_address($userDetail, $addressId);
    if (!$address) {
        krepling_validation_error($message, $extra, 403);
    }

    return $address;
}

function krepling_require_payment_method_by_id($userDetail, $cardId, $message, $extra = [])
{
    $cardId = trim((string) $cardId);
    if ($cardId === '') {
        krepling_validation_error($message, $extra);
    }

    $paymentMethod = krepling_find_payment_method($userDetail, $cardId);
    if (!$paymentMethod) {
        krepling_validation_error($message, $extra, 403);
    }

    return $paymentMethod;
}

function krepling_current_cart_amounts()
{
    $sessionTotals = krepling_wc_session_cart_amount();

    $cartTotal = '0.00';
    $cartShipping = '0.00';
    $cartSubtotal = '0.00';

    if (function_exists('WC')) {
        $wc = WC();
        if ($wc && isset($wc->cart) && $wc->cart) {
            $cartTotal = number_format((float) $wc->cart->total, 2, '.', '');
            $cartShipping = number_format((float) $wc->cart->shipping_total, 2, '.', '');
            $cartSubtotal = number_format((float) $wc->cart->subtotal, 2, '.', '');
        }
    }

    return [
        'total' => isset($sessionTotals['total']) && is_numeric($sessionTotals['total'])
            ? number_format((float) $sessionTotals['total'], 2, '.', '')
            : $cartTotal,
        'shipping' => isset($sessionTotals['shipping']) && is_numeric($sessionTotals['shipping'])
            ? number_format((float) $sessionTotals['shipping'], 2, '.', '')
            : $cartShipping,
        'subtotal' => isset($sessionTotals['subtotal']) && is_numeric($sessionTotals['subtotal'])
            ? number_format((float) $sessionTotals['subtotal'], 2, '.', '')
            : $cartSubtotal,
    ];
}

function krepling_validate_browser_name($browserName, $extra = [])
{
    $browserName = trim((string) $browserName);

    if ($browserName === '') {
        krepling_validation_error('Unable to detect your browser. Please refresh and try again.', $extra);
    }

    return sanitize_text_field($browserName);
}

function krepling_log_body_should_redact($key)
{
    $key = strtolower((string) $key);

    foreach ([
        'token',
        'password',
        'secret',
        'card',
        'cvv',
        'cvc',
        'email',
        'phone',
        'address',
        'merchant',
        'postal',
        'postcode',
        'zip',
        'otp',
        'useragent',
        'loc',
        'org',
    ] as $sensitiveFragment) {
        if (strpos($key, $sensitiveFragment) !== false) {
            return true;
        }
    }

    return false;
}

function krepling_redact_log_value($value, $key = '')
{
    if (krepling_log_body_should_redact($key)) {
        return '[REDACTED]';
    }

    if (is_array($value)) {
        $redacted = [];
        foreach ($value as $childKey => $childValue) {
            $redacted[$childKey] = krepling_redact_log_value($childValue, (string) $childKey);
        }
        return $redacted;
    }

    if (is_object($value)) {
        $redacted = [];
        foreach (get_object_vars($value) as $childKey => $childValue) {
            $redacted[$childKey] = krepling_redact_log_value($childValue, (string) $childKey);
        }
        return $redacted;
    }

    return $value;
}

function krepling_redact_log_body($body)
{
    $decoded = json_decode((string) $body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '[non-json response omitted]';
    }

    return wp_json_encode(krepling_redact_log_value($decoded));
}

function krepling_log_api_response($prefix, $apiUrl, $statusCode, $body)
{
    return;
}
