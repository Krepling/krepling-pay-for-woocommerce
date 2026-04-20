<?php
defined('ABSPATH') || exit;

if (!function_exists('krepling_debug_log')) {
    function krepling_debug_log($label, $data = null)
    {
        return;
    }
}

if (!function_exists('krepling_mask_sensitive')) {
    function krepling_mask_sensitive($data)
    {
        if (is_array($data)) {
            $masked = array();
            foreach ($data as $key => $value) {
                $lower = strtolower((string) $key);
                if (
                    strpos($lower, 'cardnumber') !== false ||
                    strpos($lower, 'card_number') !== false ||
                    strpos($lower, 'cvv') !== false ||
                    strpos($lower, 'secret') !== false ||
                    strpos($lower, 'token') !== false ||
                    strpos($lower, 'password') !== false
                ) {
                    $masked[$key] = '***redacted***';
                } else {
                    $masked[$key] = krepling_mask_sensitive($value);
                }
            }
            return $masked;
        }

        if (is_object($data)) {
            return krepling_mask_sensitive((array) $data);
        }

        return $data;
    }
}

if (!function_exists('krepling_build_debug_curl')) {
    function krepling_build_debug_curl($url, $payload)
    {
        $json = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return "curl -i -X POST " . escapeshellarg($url) .
            " -H 'Content-Type: application/json' --data-binary " . escapeshellarg($json);
    }
}


/**
 * Execute all the curl request
 *
 * @param $apiUrl       Api Url for curl request
 * @param $params       All required params for curl
 *
 * @return array
 */
function krepling_post_curl_request($apiUrl, $params = null)
{
    $url = KREPLING_API_URL . $apiUrl;
    $response = wp_remote_post(
        $url,
        array(
            'method'  => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timeout' => 60,
        )
    );

    return $response;
}
//end krepling_post_curl_request

/**
 * Execute all the authentication required get and post curl request
 *
 * @param $apiUrl API Url for curl request
 * @param $params All required params for curl
 * @param $token  Bearer Token key used for authentication
 * @param $method API request method
 *
 * @return array
 */
function krepling_authentication_post_request($apiUrl, $token, $method, $params = null)
{
    if ($method == 'POST') {
        $response = wp_remote_post(
            KREPLING_API_URL . $apiUrl,
            array(
            'method'      => $method,
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ),
            'body'        => json_encode($params, JSON_UNESCAPED_UNICODE)
            )
        );
        if (is_wp_error($response)) {
            return (object) [
                'status' => 500,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        krepling_log_api_response('Krepling Auth API', $apiUrl, $code, $body);

        $response_data = json_decode($body);
        if ($response_data === null) {
            return (object) [
                'status' => $code ?: 500,
                'message' => 'Invalid or empty API response',
                'raw_body' => $body,
            ];
        }
    }

    if ($method == 'GET') {
        $response = wp_remote_get(
            KREPLING_API_URL . $apiUrl,
            array(
            'method'      => $method,
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token
            ))
        );
        if (is_wp_error($response)) {
            return (object) [
                'status' => 500,
                'message' => $response->get_error_message(),
            ];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        krepling_log_api_response('Krepling Auth API', $apiUrl, $statusCode, $body);

        $response_body = json_decode($body);
        if ($response_body === null) {
            return (object) [
                'status' => $statusCode ?: 500,
                'message' => 'Invalid or empty API response',
                'raw_body' => $body,
            ];
        }
        if (!empty(strpos($apiUrl, 'GetUserDetails'))) {
            $response_data = ['statusCode' => $statusCode, 'responseData' => $response_body];
        } else {
            $response_data = $response_body;
        }
    }
    return $response_data;
}
//end krepling_authentication_post_request

/**
 * Execute all the get curl request
 *
 * @param $apiUrl Api Url for curl request
 *
 * @return array
 */
function krepling_get_curl_request($apiUrl)
{
    $response = wp_remote_get(
        KREPLING_API_URL . $apiUrl,
        array(
        'method'      => 'GET',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array()
        )
    );
    return wp_remote_retrieve_body($response);
}
//end krepling_get_curl_request

/**
 * Create Custom Order aftre payment done
 *
 * @return array
 *
 * @since 1.0.0
 */
function krepling_create_custom_order($orderData)
{
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id[] = [
            'product_id' => $cart_item['product_id'],
            'qty'        => $cart_item['quantity'],
        ];
    }
    $name         = explode(' ', $orderData['full_name']);
    $deliveryStateCode = krepling_get_state_code($orderData['deliveryCountry'], $orderData['deliveryState']);
    $billingStateCode = krepling_get_state_code($orderData['billingCountry'], $orderData['billingState']);

    $deliveryAddress = [
        'first_name'  => $name[0],
        'last_name'   => $name[1],
        'email'       => $orderData['email'],
        'phone'       => '(' . $orderData['country_code'] . ') ' . $orderData['phone_number'],
        'address_1'   => $orderData['deliveryAddress'],
        'address_2'   => $orderData['deliveryAddress1'],
        'city'        => $orderData['deliveryCity'],
        'state'       => $deliveryStateCode,
        'postcode'    => $orderData['deliveryZipCode'],
        'country'     => $orderData['deliveryCountry'],
        'productData' => $product_id
    ];

    $billingAddress = [
        'first_name'  => $name[0],
        'last_name'   => $name[1],
        'email'       => $orderData['email'],
        'phone'       => '(' . $orderData['country_code'] . ') ' . $orderData['phone_number'],
        'address_1'   => $orderData['billingAddress'],
        'address_2'   => $orderData['billingAddress1'],
        'city'        => $orderData['billingCity'],
        'state'       => $billingStateCode,
        'postcode'    => $orderData['billingZipCode'],
        'country'     => $orderData['billingCountry'],
        'productData' => $product_id
    ];

    // shipping method or amount
    $shipping_item               = new WC_Order_Item_Shipping();
    $current_shipping_method = WC()->session->get('chosen_shipping_methods');
    if (!empty($current_shipping_method)) {
        $methodId         = explode(':', $current_shipping_method[0]);
        $selected_id      = $methodId[0];
        $shipping_methods = WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $shipping_method) {
            if ($selected_id == $shipping_method->id) {
                $shipping_method_title = $shipping_method->method_title;
            }
        }

        $shipping_item->set_method_title($shipping_method_title);
        $shipping_item->set_method_id($current_shipping_method[0]);
        $shipping_item->set_total(WC()->cart->shipping_total);
    }

    // Applied Taxes
    $tax_item = new WC_Order_Item_Fee();
    $tax_item->set_name('Tax');
    $tax_item->set_amount(WC()->cart->total_tax);
    $tax_item->set_total(WC()->cart->total_tax);

    // Now we create the order
    $order = wc_create_order();

    // add products for custom order
    foreach ($deliveryAddress['productData'] as $productData) {
        $order->add_product(
            get_product($productData['product_id']),
            $productData['qty']
        );
    }

    // show shipping only when applied
    if (!empty(WC()->cart->shipping_total)) {
        $order->add_item($shipping_item);
    }

    // show discount only when applied
    if (!empty(WC()->cart->get_applied_coupons()[0])) {
        $order->apply_coupon(WC()->cart->get_applied_coupons()[0]);
    }

    // show taxes only when applied
    if (!empty(WC()->cart->total_tax)) {
        $order->add_item($tax_item);
    }
    $order->set_address($deliveryAddress, 'shipping');
    $order->set_address($billingAddress, 'billing');
    $order->set_payment_method('krepling');
    $order->set_payment_method_title('Krepling Payment Gateway');
    $order->set_customer_id(get_current_user_id());
    $order->calculate_totals();
    $order->update_meta_data('_krepling_cart_hash', WC()->cart ? WC()->cart->get_cart_hash() : '');
    $order->update_meta_data('_krepling_order_payload_hash', md5(wp_json_encode($orderData)));
    $order->add_order_note('Krepling payment initiated. Awaiting confirmed payment response.');
    $order->set_status('pending');
    $order->save();

    $krepling_order = new WC_Order($order->get_id());
    $order_key      = $krepling_order->get_order_key();
    $orderData      = [
        'orderKey'     => $order_key,
        'orderId'      => $order->get_id(),
        'thankyou_url' => $order->get_checkout_order_received_url(),
    ];
    return $orderData;
}
//end krepling_create_custom_order

function krepling_get_or_create_pending_order($orderData)
{
    $pendingOrderId = absint(krepling_wc_session_get('krepling_pending_order_id', 0));
    $currentCartHash = WC()->cart ? WC()->cart->get_cart_hash() : '';
    $currentPayloadHash = md5(wp_json_encode($orderData));

    if ($pendingOrderId > 0) {
        $pendingOrder = wc_get_order($pendingOrderId);
        if (
            $pendingOrder instanceof WC_Order &&
            'krepling' === $pendingOrder->get_payment_method() &&
            'pending' === $pendingOrder->get_status() &&
            $pendingOrder->get_meta('_krepling_cart_hash', true) === $currentCartHash &&
            $pendingOrder->get_meta('_krepling_order_payload_hash', true) === $currentPayloadHash
        ) {
            return [
                'orderKey' => $pendingOrder->get_order_key(),
                'orderId' => $pendingOrder->get_id(),
                'thankyou_url' => $pendingOrder->get_checkout_order_received_url(),
            ];
        }

        krepling_wc_session_forget('krepling_pending_order_id');
    }

    $createdOrder = krepling_create_custom_order($orderData);
    krepling_wc_session_set('krepling_pending_order_id', $createdOrder['orderId']);

    return $createdOrder;
}

function krepling_confirm_order_payment($orderId, $orderNote = '')
{
    $order = wc_get_order(absint($orderId));
    if (!($order instanceof WC_Order)) {
        return false;
    }

    $order->payment_complete();

    if (!empty($orderNote)) {
        $order->add_order_note($orderNote);
    }

    // Empty the cart only after payment is actually confirmed.
    if (function_exists('WC') && WC()->cart) {
        WC()->cart->empty_cart();
    }

    krepling_wc_session_forget('krepling_pending_order_id');

    return true;
}

/**
 * Fetch state code from state name
 *
 * @param $country Country code to fetch their states
 * @param $state   State name to fetch their state code
 *
 * @return string
 */
function krepling_get_state_code($country, $state)
{
    $country_states = WC()->countries->get_states($country);
    foreach ($country_states as $code => $name) {
        if ($name == $state) {
            $stateCode = $code;
            break;
        }
    }
    return isset($stateCode) ? $stateCode : $state;
}
//end krepling_get_state_code

/**
 * Retrive the state name from the country code and state code
 *
 * @param $country Country code to fetch their states
 * @param $state State code to get their name
 *
 * @return string
 */
function krepling_get_state_name($country, $state)
{
    if (! empty($country) && ! empty($state)) {
        $country_states   = WC()->countries->get_states($country);
        return isset($country_states[$state]) ? $country_states[$state] : $state;
    }
}
// end krepling_get_state_name

/**
 * Fetch the current device information
 *
 * @param $apiUrl IP address API url
 *
 * @return array
 */
function krepling_get_device_data($apiUrl)
{
    $response = wp_remote_get($apiUrl);
    return wp_remote_retrieve_body($response);
}
//end krepling_get_device_data
