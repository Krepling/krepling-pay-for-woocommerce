<?php
defined('ABSPATH') || exit;

function krepling_dispatch_auth_action($requestAction)
{
    global $krepling_api_keys, $krepling_count_product;

    $actionResponse = null;

    switch ($requestAction) {

    case 'getSignupEmailOtp':
        $signupEmail = krepling_request_email('email');

        if (!krepling_is_valid_email_address($signupEmail)) {
            krepling_validation_error('Enter a valid email address');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/signup/send-email-otp',
            array(
                'Email' => str_replace(' ', '', $signupEmail),
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to send OTP.'
        );
        break;

    // Verify signup email address otp
    case 'verifyOTP':
        $otpEmail = krepling_request_email('email');

        if (
            !$otpEmail &&
            is_object(krepling_wc_session_user_detail()) &&
            isset(krepling_wc_session_user_detail()->userVM->email)
        ) {
            $otpEmail = sanitize_email((string) krepling_wc_session_user_detail()->userVM->email);
        }

        if (!krepling_is_valid_email_address($otpEmail)) {
            krepling_validation_error('Enter a valid email address');
        }

        $otpValue = krepling_request_digits('otp');

        if (!krepling_is_valid_otp($otpValue)) {
            krepling_validation_error('Enter 6-digit code');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/signup/verify-email-otp',
            array(
                'Email'      => $otpEmail,
                'OtpByEmail' => $otpValue,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Verification failed. Please try again.'
        );
        break;

    // First form of signup for basic details

    case 'newUserSignup':
        $signupValidationExtra = ['card_details_status' => 1];
        krepling_validate_wc_checkout_required_order_fields($signupValidationExtra);
        $signupEmail = krepling_request_email('email');
        $signupPhone = krepling_request_digits('mobile');
        $signupCountryCode = krepling_request_string('countryCode');
        $rememberEmail = krepling_request_bool('is_checked');
        $isSameBillingShipping = krepling_request_bool('is_sameBillingShipping');
        $companyName = krepling_request_string('companyName');
        $productCartName = krepling_request_string('productCartName');
        $cartAmounts = krepling_current_cart_amounts();

        if (!krepling_is_valid_email_address($signupEmail)) {
            krepling_validation_error('Enter a valid email address', $signupValidationExtra);
        }

        if (!krepling_is_valid_phone_number($signupPhone)) {
            krepling_validation_error('Enter a valid phone number', $signupValidationExtra);
        }

        if (!krepling_is_valid_country_dial_code($signupCountryCode)) {
            krepling_validation_error('Select your country code', $signupValidationExtra);
        }

        if ($krepling_api_keys['hide_Address'] != 'yes') {
            $fullName = krepling_request_string('first_last_name');
            $shippingAddress = krepling_normalize_address_payload(
                krepling_request_string('delivery_address_1'),
                krepling_request_string('delivery_address_2'),
                krepling_request_string('city'),
                krepling_request_string('state'),
                krepling_request_string('countryManually'),
                krepling_request_string('zip_code')
            );

            $billingAddressData = $isSameBillingShipping
                ? krepling_copy_billing_address_from_shipping($shippingAddress)
                : krepling_normalize_address_payload(
                    krepling_request_string('billingAddress'),
                    krepling_request_string('billingAddress1'),
                    krepling_request_string('billingCity'),
                    krepling_request_string('billingState'),
                    krepling_request_string('billingCountry'),
                    krepling_request_string('billingZip')
                );
        } else {
            $fullName = trim(WC()->customer->get_first_name() . ' ' . WC()->customer->get_last_name());
            $shippingAddress = krepling_wc_customer_address_payload('shipping');
            $billingAddressData = krepling_wc_customer_address_payload('billing');
        }

        if (!krepling_is_valid_name($fullName)) {
            krepling_validation_error('Enter your full name', $signupValidationExtra);
        }

        $shippingAddress = krepling_validate_address_payload($shippingAddress, 'shipping', $signupValidationExtra);
        $billingAddressData = krepling_validate_address_payload($billingAddressData, 'billing', $signupValidationExtra);

        $validatedCard = krepling_validate_card_payload(
            krepling_request_string('card_number'),
            krepling_request_string('card_validity_date'),
            krepling_request_string('card_cvv_number'),
            krepling_request_string('cardHolderName'),
            krepling_request_string('paymentCardType'),
            $signupValidationExtra
        );

        $getCvvDetails = '/Checkout/GetCVVNumberMaskingandRegularExpression?cardType=' . $validatedCard['card_type'];
        $cvvRes = json_decode(krepling_get_curl_request($getCvvDetails));
        $payment_card_status = isset($cvvRes->status) ? (int) $cvvRes->status : 0;

        if ($payment_card_status !== 1) {
            $actionResponse = json_encode([
                'status' => 422,
                'message' => 'We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.',
                'card_details_status' => $payment_card_status,
            ]);
            break;
        }

        $encryptionKey = hash('sha256', (string) $krepling_api_keys['merchant_id'], true);
        $encryptData = static function ($plaintext, $key) {
            $ivLength = openssl_cipher_iv_length('AES-256-CBC');
            $iv = openssl_random_pseudo_bytes($ivLength);
            $cipherText = openssl_encrypt((string) $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $cipherText);
        };

        $saveUserData = [
            "Id" => 0,
            "EmailId" => $signupEmail,
            "Phone" => $signupPhone,
            "CountryCode" => $signupCountryCode,
            "FullName" => $fullName,
            "CompanyFullName" => $companyName,
            "CardHolderName" => $validatedCard['card_holder_name'],
            "CardNumber" => $encryptData($validatedCard['number'], $encryptionKey),
            "CardValidityDate" => $encryptData($validatedCard['expiry'], $encryptionKey),
            "CardCVVNumber" => $encryptData($validatedCard['cvv'], $encryptionKey),
            "Password" => null,
            "MerchantId" => (string) $krepling_api_keys['merchant_id'],
            "SecretKey" => (string) $krepling_api_keys['secret_id'],
            "SwitchStatus" => $rememberEmail,
            "UserName" => null,
            "Address" => "",
            "Address2" => "",
            "Email" => null,
            "Currency" => krepling_wc_session_get('defaultCurrencyName'),
            "Amount" => (string) $cartAmounts['total'],
            "IsDefault" => false,
            "AddressId" => null,
            "CardType" => $validatedCard['card_type'],
            "Price" => (float) $cartAmounts['total'],
            "Size" => 0,
            "ProductName" => $productCartName,
            "Quantity" => (int) $krepling_count_product,
            "CheckoutAddress" => null,
            "StreetAddress1" => "",
            "SecondAddress1" => "",
            "Country1" => "",
            "StreetAddress2" => "",
            "SecondAddress2" => "",
            "Country2" => "",
            "StreetAddress3" => "",
            "SecondAddress3" => "",
            "Country3" => "",
            "CardHolderFullName" => $validatedCard['card_holder_name'],
            "SubTotal" => (float) $cartAmounts['subtotal'],
            "Shipping" => (float) $cartAmounts['shipping'],
            "Total" => (float) $cartAmounts['total'],
            "FromLogin" => "No",
            "DeliveryAddress1" => $shippingAddress['streetAddress1'],
            "DeliveryAddress2" => $shippingAddress['streetAddress2'],
            "City" => $shippingAddress['city'],
            "State" => $shippingAddress['state'],
            "ZipCode" => $shippingAddress['zipCode'],
            "Country" => $shippingAddress['country'],
            "BillingAddress" => $isSameBillingShipping,
            "BillingAddress1" => $billingAddressData['streetAddress1'],
            "BillingAddress2" => $billingAddressData['streetAddress2'],
            "BillingZip" => $billingAddressData['zipCode'],
            "BillingCity" => $billingAddressData['city'],
            "BillingState" => $billingAddressData['state'],
            "BillingCountry" => $billingAddressData['country'],
            "IsPhoneVerified" => false,
            "IsEmailVerified" => false,
            "PhoneSetDefault" => false,
            "EmailSetDefault" => false,
            "CurrencySymbol" => krepling_wc_session_get('defaultCurrencySymbol')
        ];

        $saveNewUserUrl = '/Checkout/SaveNewUser';

        $response = krepling_post_curl_request($saveNewUserUrl, $saveUserData);

        $decodedResponse = null;
        $responseCode = 500;
        $responseBody = '';

        if (!is_wp_error($response)) {
            $responseCode = wp_remote_retrieve_response_code($response);
            $responseBody = wp_remote_retrieve_body($response);

            $decodedResponse = json_decode($responseBody);
        }

        $apiSucceeded = (
            is_object($decodedResponse) &&
            isset($decodedResponse->status) &&
            (int) $decodedResponse->status === 1
        );

        if ($apiSucceeded && $rememberEmail) {
            krepling_set_cookie('kp_user_email', $signupEmail, time() + (86400 * 14));
            krepling_clear_legacy_password_cookie();
        }

        $placeOrderData = [
            'deliveryAddress' => $shippingAddress['streetAddress1'],
            'deliveryAddress1' => $shippingAddress['streetAddress2'],
            'deliveryZipCode' => $shippingAddress['zipCode'],
            'deliveryState' => $shippingAddress['state'],
            'deliveryCity' => $shippingAddress['city'],
            'deliveryCountry' => $shippingAddress['country'],
            'billingAddress' => $billingAddressData['streetAddress1'],
            'billingAddress1' => $billingAddressData['streetAddress2'],
            'billingZipCode' => $billingAddressData['zipCode'],
            'billingState' => $billingAddressData['state'],
            'billingCity' => $billingAddressData['city'],
            'billingCountry' => $billingAddressData['country'],
            'email' => $signupEmail,
            'phone_number' => $signupPhone,
            'country_code' => $signupCountryCode,
            'full_name' => $fullName
        ];

        $orderData = null;
        if ($apiSucceeded) {
            $orderData = krepling_create_custom_order($placeOrderData);
        }

        if ($apiSucceeded) {
            $result = [
                'status' => 1,
                'message' => !empty($decodedResponse->message) ? $decodedResponse->message : 'Payment Successful',
                'card_details_status' => $payment_card_status,
                'thankyou_url' => $orderData['thankyou_url'] ?? null
            ];
        } else {
            $result = [
                'status' => is_object($decodedResponse) && isset($decodedResponse->status)
                    ? (int) $decodedResponse->status
                    : ($responseCode ?: 500),
                'message' => is_object($decodedResponse) && !empty($decodedResponse->message)
                    ? $decodedResponse->message
                    : (!empty($responseBody) ? $responseBody : 'Payment failed'),
                'card_details_status' => $payment_card_status,
                'thankyou_url' => null
            ];
        }

        $actionResponse = json_encode($result);
        break;

    // login API
    case 'user_login':
        $loginEmail = krepling_request_email('user_email');
        $loginPassword = krepling_request_raw_string('user_password');
        $browserName = krepling_validate_browser_name(krepling_request_string('browser_name'));

        if (!krepling_is_valid_email_address($loginEmail)) {
            krepling_validation_error('Enter your valid email address');
        }

        if ($loginPassword === '') {
            krepling_validation_error('Enter your password');
        }

        $device_json = krepling_get_device_data(KREPLING_IP_ADDRESS_API);
        $device_data = json_decode($device_json);

        $localIP = getHostByName(getHostName());
        $device = array(
            'ip'       => $localIP,
            'city'     => isset($device_data->city) ? $device_data->city : '',
            'region'   => isset($device_data->region) ? $device_data->region : '',
            'country'  => isset($device_data->country) ? $device_data->country : '',
            'loc'      => isset($device_data->loc) ? $device_data->loc : '',
            'org'      => isset($device_data->org) ? $device_data->org : '',
            'postal'   => isset($device_data->postal) ? $device_data->postal : '',
            'timezone' => isset($device_data->timezone) ? $device_data->timezone : '',
        );

        $deviceLocationParts = array_filter(array(
            $device['city'],
            $device['region'],
            $device['country'],
        ));
        $deviceLocation = implode(', ', $deviceLocationParts);

        $upstream = krepling_service_private_upstream(
            '/account/login',
            array(
                'UserName'   => $loginEmail,
                'Password'   => $loginPassword,
                'RememberMe' => false,
                'Browser'    => $browserName,
                'Id'         => 0,
                'userAgent'  => wp_json_encode($device),
            )
        );

        if (krepling_service_extract_status($upstream, 500) === 1) {
            krepling_store_authenticated_user_session_from_service(
                $upstream,
                $browserName,
                $deviceLocation,
                $loginEmail
            );
        }

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to sign in.'
        );
        break;

    case 'userLogout':
        krepling_destroy_current_session();
        $actionResponse = json_encode(array(
            'status'  => 1,
            'message' => "You've been signed out",
        ));
        break;

    case 'getUserDetails':
        $auth = krepling_require_checkout_authentication();

        $upstream = krepling_service_private_upstream(
            '/account/user-details',
            array(
                'Email'     => $auth['login_email'],
                'UserToken' => $auth['token'],
            )
        );

        if (
            isset($upstream['status']) &&
            (int) $upstream['status'] === 200 &&
            !empty($upstream['user_detail'])
        ) {
            $userDetailObject = json_decode(wp_json_encode($upstream['user_detail']));
            if ($userDetailObject) {
                krepling_store_user_detail_state($userDetailObject);
            }

            $actionResponse = krepling_json_response(array(
                'status'   => 200,
                'userData' => krepling_wc_session_user_detail(),
            ));
        } else {
            $actionResponse = krepling_json_response(array(
                'status'  => isset($upstream['status']) ? (int) $upstream['status'] : 440,
                'message' => !empty($upstream['message'])
                    ? $upstream['message']
                    : 'You took too long on your account. Please sign in again',
            ));
        }
        break;

    case 'change_user_password':
        $auth = krepling_require_checkout_authentication();
        $oldPassword = krepling_request_raw_string('OldPassword');
        $newPassword = krepling_request_raw_string('NewPassword');
        $confirmPassword = krepling_request_raw_string('ReNewPassword');

        if ($oldPassword === '') {
            krepling_validation_error('Enter your old password');
        }

        if ($newPassword === '') {
            krepling_validation_error('Enter a new password');
        }

        if (!krepling_is_valid_password($newPassword)) {
            krepling_validation_error('Password contains atleast 6 characters, which have special character(!@#$%&*) and alphanumeric.');
        }

        if ($newPassword !== $confirmPassword) {
            krepling_validation_error("The new and confirmation password doesn't match");
        }

        $upstream = krepling_service_private_upstream(
            '/account/change-password',
            array(
                'Email'         => $auth['login_email'],
                'OldPassword'   => $oldPassword,
                'NewPassword'   => $newPassword,
                'ReNewPassword' => $confirmPassword,
                'UserToken'     => $auth['token'],
            )
        );

        if (krepling_service_extract_status($upstream, 500) === 1) {
            krepling_destroy_current_session();
        }

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to change password.'
        );
        break;

    case 'forgotAccountPassword':
        $forgotEmail = krepling_request_email('email');

        if (!krepling_is_valid_email_address($forgotEmail)) {
            krepling_validation_error('Enter your valid email address then click on forgot password link');
        }

        $upstream = krepling_service_private_upstream(
            '/account/forgot-password',
            array(
                'Email' => $forgotEmail,
            )
        );

        if (krepling_service_extract_status($upstream, 500) === 200) {
            krepling_destroy_current_session();
        }

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to send forgot password email.'
        );
        break;
    // Change Email address from  two factor authentication

        default:
            return null;
    }

    return $actionResponse;
}
