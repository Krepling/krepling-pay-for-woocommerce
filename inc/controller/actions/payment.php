<?php
defined('ABSPATH') || exit;

function krepling_dispatch_payment_action($requestAction)
{
    global $krepling_api_keys, $krepling_count_product, $krepling_total, $krepling_shipping, $krepling_subtotal, $krepling_tax, $krepling_discount, $krepling_cart_data;

    $actionResponse = null;

    switch ($requestAction) {

    case 'addPaymentCard':
        $auth = krepling_require_checkout_authentication();

        $validatedCard = krepling_validate_card_payload(
            krepling_request_string('addCardNumber'),
            krepling_request_string('addCardExpiry'),
            krepling_request_string('addCardCvv'),
            krepling_request_string('cardFirstName'),
            krepling_request_string('paymentCardType')
        );

        $serviceBody = krepling_service_private_response(
            '/checkout/payment-card/add',
            [
                'UserToken'          => $auth['token'],
                'CardNumber'         => $validatedCard['number'],
                'CardValidityDate'   => $validatedCard['expiry'],
                'CardCVVNumber'      => $validatedCard['cvv'],
                'UserId'             => (string) $auth['user_id'],
                'CardHolderFullName' => $validatedCard['card_holder_name'],
                'CardType'           => $validatedCard['card_type'],
                'CardToken'          => null,
                'MaskedCardNumber'   => null,
                'MerchantId'         => (string) $krepling_api_keys['merchant_id'],
            ]
        );
        $upstream = krepling_service_extract_upstream_data($serviceBody);

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to add payment card.'
        );
        break;

    case 'deletCard':
        $auth = krepling_require_checkout_authentication();
        $cardId = krepling_request_string('card_id');

        krepling_require_payment_method_by_id(
            $auth['user_detail'],
            $cardId,
            'Invalid payment card selected.'
        );

        $serviceBody = krepling_service_private_response(
            '/checkout/payment-card/delete',
            [
                'UserToken' => $auth['token'],
                'UserId'    => $auth['user_id'],
                'cardId'    => $cardId,
            ]
        );
        $upstream = krepling_service_extract_upstream_data($serviceBody);

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to delete payment card.'
        );
        break;

    
    // getCurrency

    case 'typeCurrencyValue':
        $currencySymbol = get_woocommerce_currency_symbol();
        $currencyName = get_woocommerce_currency();
        $currency = $currencyName . '(' . $currencySymbol . ')';
        krepling_wc_session_set('defaultCurrencySymbol', $currencySymbol);
        krepling_wc_session_set('defaultCurrencyAndSymbol', $currency);
        krepling_wc_session_set('defaultCurrencyName', $currencyName);
        krepling_wc_session_set('cart_amount', [
            'total' => $krepling_total,
            'shipping' => $krepling_shipping,
            'subtotal' => $krepling_subtotal
        ]);
        
        $prioritiesCurrency = krepling_get_curl_request('/SMS/GetPriorityCurrencies');
        $allCurrency = krepling_get_curl_request('/SMS/GetCurrencyCheckOut');
        $priorityDecode           = json_decode($prioritiesCurrency, true);
        $allDecode                = json_decode($allCurrency, true);
        $totalCurrency            = [
        'first'  => $priorityDecode,
        'second' => $allDecode,
        ];
        krepling_wc_session_set('listCurrency', $totalCurrency);
        $actionResponse = json_encode($totalCurrency);
        break;

    // get Country List

    case 'changeCurrency':
        $convertedPrices = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product         = wc_get_product($cart_item['product_id']);
            $productPrices[] = $product->get_price();
        }

        $convertedPrices = [
        'subtotal'       => $krepling_subtotal,
        'shipping'       => $krepling_shipping,
        'tax'            => $krepling_tax,
        'total'          => $krepling_total,
        'discount'       => $krepling_discount,
        'product_prices' => $productPrices,
        ];

        $selectedCurrency = strtoupper(krepling_request_string('newCurrency'));
        $selectedSymbol = krepling_request_string('symbol');
        if ($selectedCurrency === '' || $selectedSymbol === '') {
            krepling_validation_error('Select a valid currency.');
        }
        $response = krepling_services_json_request(
            'GET',
            '/exchange/latest?base=USD',
            array(),
            true
        );

        if (is_wp_error($response)) {
            krepling_validation_error('Unable to load exchange rates.');
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_json = wp_remote_retrieve_body($response);

        if ($status_code >= 400 || $response_json === '') {
            krepling_validation_error('Unable to load exchange rates.');
        }

        // Continuing if we got a result
        if (false !== $response_json) {
            // Try/catch for json_decode operation
            try {
                // Decoding
                $response = json_decode($response_json);
                // Check for success
                if ('success' === $response->result) {
                    krepling_wc_session_set('defaultCurrencySymbol', $selectedSymbol);
                    krepling_wc_session_set('defaultCurrencyName', $selectedCurrency);
                    foreach ($convertedPrices as $keys => $priceValue) {
                        // Your price in USD
                        if ($keys == 'product_prices') {
                            foreach ($priceValue as $value) {
                                $newProductPrices[] = number_format((float) round((
                                    $value * $response->conversion_rates->$selectedCurrency
                                ), 2), 2, '.', '');
                            }
                        } else {
                            if (!empty($priceValue)) {
                                $resultAmount[$keys] = number_format((float) round((
                                    $priceValue * $response->conversion_rates->$selectedCurrency
                                ), 2), 2, '.', '');
                            }
                        }//end if
                    }//end foreach
                }//end if
                krepling_wc_session_set('cart_amount', [
                    'total' => $resultAmount['total'],
                    'shipping' => $resultAmount['shipping'],
                    'subtotal' => $resultAmount['subtotal']
                ]);
                $productDetails = [];
                $convertedAmount = $newProductPrices;
                $product_count = 0;
                foreach( $krepling_cart_data as $cart_item ) {
                    $product_id = $cart_item['product_id'];
                    $product = wc_get_product( $product_id );
                    $productData = [
                        'product_name' => $cart_item['data']->get_name(),
                        'product_image' => $product->get_image(),
                        'product_price' => $convertedAmount[$product_count],
                        'product_qty' => $cart_item['quantity']
                    ];
                    $productDetails[] = $productData;
                    $product_count++;
                }
                krepling_wc_session_set('product_details', $productDetails);

                $result = [
                'status'          => 'success',
                'convertedAmount' => $resultAmount,
                'symbol'          => $selectedSymbol,
                'product_convertedPrices' => $productDetails
                ];
                $actionResponse = json_encode($result);
            } catch (\Exception $e) {
                // Handle JSON parse error...
                $result           = [
                    'status'  => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $actionResponse = json_encode($result);
            }
        }//end if
        break;
    // change user account password
    case 'manageEnableFastKrepling':
        // Intentionally stays in the plugin.
        // This is a local convenience-cookie preference, not an upstream Krepling API action.
        $enableFastCheckout = krepling_request_bool('kreplingFast');

        if ($enableFastCheckout) {
            $auth = krepling_require_checkout_authentication();
            krepling_set_cookie('kp_user_email', $auth['login_email'], time() + (86400 * 14));
            krepling_clear_legacy_password_cookie();

            $actionResponse = json_encode(array(
                'status'  => 1,
                'message' => 'Email saved for convenience. For security, password is no longer stored in the browser.',
            ));
        } else {
            krepling_clear_cookie('kp_user_email');
            krepling_clear_legacy_password_cookie();

            $actionResponse = json_encode(array(
                'status'  => 0,
                'message' => "Automatic login disabled. You'll need to sign in again on future checkouts",
            ));
        }
        break;

    // Payment Complete

    case 'pay':
        $auth = krepling_require_checkout_authentication();
        krepling_validate_wc_checkout_required_order_fields();
        $selectedAddress = krepling_require_checkout_address_by_id(
            $auth['user_detail'],
            krepling_request_int('addressId'),
            'Select the delivery address to make payment'
        );

        $deliveryAddress = [
            'address1' => isset($selectedAddress->streetAddress1) ? trim((string) $selectedAddress->streetAddress1) : '',
            'address2' => isset($selectedAddress->streetAddress2) ? trim((string) $selectedAddress->streetAddress2) : '',
            'zipCode' => isset($selectedAddress->zipCode) ? trim((string) $selectedAddress->zipCode) : '',
            'state' => isset($selectedAddress->state) ? trim((string) $selectedAddress->state) : '',
            'city' => isset($selectedAddress->city) ? trim((string) $selectedAddress->city) : '',
            'country' => isset($selectedAddress->country) ? trim((string) $selectedAddress->country) : '',
        ];

        $billingAddress = [
            'address1' => isset($selectedAddress->billingAddress1) ? trim((string) $selectedAddress->billingAddress1) : '',
            'address2' => isset($selectedAddress->billingAddress2) ? trim((string) $selectedAddress->billingAddress2) : '',
            'zipCode' => isset($selectedAddress->billingZip) ? trim((string) $selectedAddress->billingZip) : '',
            'state' => isset($selectedAddress->billingState) ? trim((string) $selectedAddress->billingState) : '',
            'city' => isset($selectedAddress->billingCity) ? trim((string) $selectedAddress->billingCity) : '',
            'country' => isset($selectedAddress->billingCountry) ? trim((string) $selectedAddress->billingCountry) : '',
        ];

        if ($billingAddress['address1'] === '' || $billingAddress['city'] === '' || $billingAddress['country'] === '' || $billingAddress['zipCode'] === '' || $billingAddress['state'] === '') {
            $billingAddress = $deliveryAddress;
        }

        $orderData = ['thankyou_url' => null, 'orderId' => null];
        $placeOrderData = [
            'deliveryAddress'    => $deliveryAddress['address1'],
            'deliveryAddress1'   => $deliveryAddress['address2'],
            'deliveryZipCode'    => $deliveryAddress['zipCode'],
            'deliveryState'      => $deliveryAddress['state'],
            'deliveryCity'       => $deliveryAddress['city'],
            'deliveryCountry'    => $deliveryAddress['country'],
            'billingAddress'     => $billingAddress['address1'],
            'billingAddress1'    => $billingAddress['address2'],
            'billingZipCode'     => $billingAddress['zipCode'],
            'billingState'       => $billingAddress['state'],
            'billingCity'        => $billingAddress['city'],
            'billingCountry'     => $billingAddress['country'],
            'email'              => isset($auth['user_detail']->userVM->email) ? (string) $auth['user_detail']->userVM->email : '',
            'phone_number'       => isset($auth['user_detail']->userVM->phone) ? (string) $auth['user_detail']->userVM->phone : '',
            'country_code'       => isset($auth['user_detail']->userVM->countryCode) ? (string) $auth['user_detail']->userVM->countryCode : '',
            'full_name'          => isset($auth['user_detail']->userVM->fullName) ? (string) $auth['user_detail']->userVM->fullName : ''
        ];

        $orderData = krepling_get_or_create_pending_order($placeOrderData);

        // Call Payment API
        $cartTotals = krepling_current_cart_amounts();
        $totals = $cartTotals['total'];
        $savedCardId = krepling_request_string('cardIdNum');
        $cardCvv = krepling_request_digits('txtCVVNumberId');

        if (!preg_match('/^\d{3,4}$/', $cardCvv)) {
            krepling_validation_error('Fill in your security code');
        }

        $cardNumberForPayment = '';
        $cardExpiryForPayment = '';
        $cardTypeForPayment = null;
        $cardHolderFullName = krepling_request_string('cardName');

        if ($savedCardId !== '') {
            $selectedPaymentMethod = krepling_require_payment_method_by_id(
                $auth['user_detail'],
                $savedCardId,
                'Invalid payment card selected.'
            );

            if ($cardHolderFullName === '') {
                $cardHolderFullName = trim(
                    ((isset($selectedPaymentMethod->cardHolderFirstName) ? (string) $selectedPaymentMethod->cardHolderFirstName : '') . ' ' .
                    (isset($selectedPaymentMethod->cardHolderLastName) ? (string) $selectedPaymentMethod->cardHolderLastName : ''))
                );
            }

            if ($cardHolderFullName === '' && isset($auth['user_detail']->userVM->fullName)) {
                $cardHolderFullName = trim((string) $auth['user_detail']->userVM->fullName);
            }

            $cardExpiryForPayment = isset($selectedPaymentMethod->expiryDate)
                ? trim((string) $selectedPaymentMethod->expiryDate)
                : krepling_request_string('cardExpDate');
            $cardTypeForPayment = isset($selectedPaymentMethod->cardType)
                ? sanitize_key((string) $selectedPaymentMethod->cardType)
                : sanitize_key(krepling_request_string('paymentCardType'));
        } else {
            $validatedPaymentCard = krepling_validate_card_payload(
                krepling_request_string('customerCardNum'),
                krepling_request_string('cardExpDate'),
                $cardCvv,
                $cardHolderFullName,
                krepling_request_string('paymentCardType')
            );

            $cardNumberForPayment = $validatedPaymentCard['number'];
            $cardExpiryForPayment = $validatedPaymentCard['expiry'];
            $cardTypeForPayment = $validatedPaymentCard['card_type'];
            $cardHolderFullName = $validatedPaymentCard['card_holder_name'];
        }

        $cardHolderName = preg_split('/\s+/', trim((string) $cardHolderFullName), 2);
        $encryptionKey = hash('sha256', (string) $krepling_api_keys['merchant_id'], true);
        $encryptData = static function ($plaintext, $key) {
            $ivLength = openssl_cipher_iv_length('AES-256-CBC');
            $iv = openssl_random_pseudo_bytes($ivLength);
            $cipher = openssl_encrypt((string) $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $cipher);
        };
        $arrayVar = [
            'data' => [
                'id'            => null,
                'type'          => 'authorisations',
                'attributes'    => [
                    'amount'              => [
                        'currency' => krepling_wc_session_get('defaultCurrencyName') ?: get_woocommerce_currency(),
                        'value'    => $totals,
                        'symbol'   => krepling_wc_session_get('defaultCurrencySymbol') ?: get_woocommerce_currency_symbol(),
                    ],
                    'transactionDate'     => gmdate('Y-m-d\TH:i:s\Z'),
                    'expiryDate'          => !empty($cardExpiryForPayment)
                        ? $encryptData($cardExpiryForPayment, $encryptionKey)
                        : $encryptData('', $encryptionKey),
                    'transactionType'     => '00',
                    'externalReference'   => 'US123550023',
                    'eCom'                => [
                        'cvc2'                   => $cardCvv !== ''
                            ? $encryptData($cardCvv, $encryptionKey)
                            : null,
                        '_3dSecure'              => '00C782F8A993E2E889E391C782F8A993',
                        'xid'                    => '',
                        'securityLevelIndicator' => '212',
                    ],
                    'pos'                 => ['posCondition' => '59'],
                    'messageTypeId'       => '0200',
                    'cardNumber'          => !empty($cardNumberForPayment)
                        ? $encryptData($cardNumberForPayment, $encryptionKey)
                        : $encryptData('', $encryptionKey),
                    'avs'                 => [
                        'postCode'      => $placeOrderData['deliveryZipCode'],
                        'streetAddress' => $placeOrderData['deliveryAddress'],
                    ],
                    'CardHolderFirstName' => $cardHolderName[0] ?? '',
                    'CardHolderLastName'  => $cardHolderName[1] ?? '',
                    'Email'               => isset($auth['user_detail']->userVM->email) ? (string) $auth['user_detail']->userVM->email : '',
                    'Address'             => $placeOrderData['deliveryAddress'],
                    'Address1'            => $placeOrderData['deliveryAddress1'],
                    'UserId'              => (string) $auth['user_id'],
                    'AddressId'           => (string) absint(isset($selectedAddress->id) ? $selectedAddress->id : 0),
                    'CardType'            => $cardTypeForPayment ?: null,
                    'PaymentStatus'       => false,
                    'CardId'              => $savedCardId !== '' ? $savedCardId : '',
                    "CardToken"           => null,
                    "MaskedCardNumber"    => null,
                    "TransactionStatus"   => "Pending",
                    "TransactionId"       => null
                ],
                'links'         => null,
                'relationships' => [
                    'merchant'     => [
                        'data'  => [
                            'type' => 'merchants',
                            'id'   => $krepling_api_keys['merchant_id'],
                        ],
                        'links' => null,
                    ],
                    'originalAuth' => null,
                ],
            ]
        ];
        
        $paymentUrl = $savedCardId !== '' ? '/Authorizations/PostECommerce' : '/Checkout/AddcardAndPayment';
        $paymentResponse   = krepling_authentication_post_request(
            $paymentUrl,
            $auth['token'],
            'POST',
            $arrayVar
        );

        if (isset($paymentResponse->status) && (int) $paymentResponse->status === 1) {
            krepling_confirm_order_payment(
                $orderData['orderId'],
                'Krepling payment confirmed. Order moved from pending to paid.'
            );
            krepling_destroy_current_session();
        }

        $result = [
            'message'      => !empty($paymentResponse->message) ? $paymentResponse->message : 'Payment failed. Please try again.',
            'status'       => isset($paymentResponse->status) ? (int) $paymentResponse->status : 500,
            'thankyou_url' => $orderData['thankyou_url'],
            'order_id'     => isset($orderData['orderId']) ? absint($orderData['orderId']) : 0,
            'order_key'    => isset($orderData['orderKey']) ? sanitize_text_field((string) $orderData['orderKey']) : '',
        ];
        $actionResponse = json_encode($result);
        break;

    case 'verifyOrderPayment':
        $orderId  = krepling_request_int('order_id');
        $orderKey = krepling_request_string('order_key');

        if ($orderId <= 0 || $orderKey === '') {
            $actionResponse = wp_json_encode([
                'status'  => 422,
                'message' => 'Missing order verification data.',
                'paid'    => false,
            ]);
            break;
        }

        $order = wc_get_order($orderId);

        if (!($order instanceof WC_Order)) {
            $actionResponse = wp_json_encode([
                'status'  => 404,
                'message' => 'Order not found.',
                'paid'    => false,
            ]);
            break;
        }

        if (!hash_equals((string) $order->get_order_key(), (string) $orderKey)) {
            $actionResponse = wp_json_encode([
                'status'  => 403,
                'message' => 'Order verification failed.',
                'paid'    => false,
            ]);
            break;
        }

        $isPaid = $order->is_paid();

        $actionResponse = wp_json_encode([
            'status'       => $isPaid ? 1 : 409,
            'message'      => $isPaid ? 'Order payment verified.' : 'Order is not marked as paid yet.',
            'paid'         => $isPaid,
            'thankyou_url' => $isPaid ? $order->get_checkout_order_received_url() : null,
        ]);
        break;

    // Get Review devices or unrecognized devices data

        default:
            return null;
    }

    return $actionResponse;
}
