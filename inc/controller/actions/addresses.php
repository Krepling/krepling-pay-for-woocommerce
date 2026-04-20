<?php
defined('ABSPATH') || exit;

function krepling_dispatch_addresses_action($requestAction)
{
    global $krepling_api_keys, $krepling_count_product;

    $actionResponse = null;

    switch ($requestAction) {

    case 'addAddress':
        $auth = krepling_require_checkout_authentication();

        if (krepling_request_bool('isDefaultStatus')) {
            $shippingAddress = krepling_validate_address_payload(
                krepling_wc_customer_address_payload('shipping'),
                'shipping'
            );
            $billingAddressData = krepling_validate_address_payload(
                krepling_wc_customer_address_payload('billing'),
                'billing'
            );

            $addressStreet = [
                'UserToken'       => $auth['token'],
                'AddressId'       => 0,
                'streetAddress1'  => $shippingAddress['streetAddress1'],
                'streetAddress2'  => $shippingAddress['streetAddress2'],
                'IsDefault'       => true,
                'UserId'          => $auth['user_id'],
                'City'            => $shippingAddress['city'],
                'State'           => $shippingAddress['state'],
                'Country'         => $shippingAddress['country'],
                'ZipCode'         => $shippingAddress['zipCode'],
                'IsDeleted'       => false,
                'BillingAddress'  => false,
                'BillingAddress1' => $billingAddressData['streetAddress1'],
                'BillingAddress2' => $billingAddressData['streetAddress2'],
                'BillingZipCode'  => $billingAddressData['zipCode'],
                'BillingCountry'  => $billingAddressData['country'],
                'BillingCity'     => $billingAddressData['city'],
                'BillingState'    => $billingAddressData['state'],
            ];
        } else {
            $shippingAddress = krepling_validate_address_payload(
                krepling_normalize_address_payload(
                    krepling_request_string('StreetAddress1'),
                    krepling_request_string('StreetAddress2'),
                    krepling_request_string('newCity'),
                    krepling_request_string('newState'),
                    krepling_request_string('newCountry'),
                    krepling_request_string('newZip')
                ),
                'shipping'
            );

            $billingAddressData = krepling_request_bool('is_sameBillingShipping')
                ? krepling_copy_billing_address_from_shipping($shippingAddress)
                : krepling_validate_address_payload(
                    krepling_normalize_address_payload(
                        krepling_request_string('billingAddress'),
                        krepling_request_string('billingAddress1'),
                        krepling_request_string('billingCity'),
                        krepling_request_string('billingState'),
                        krepling_request_string('billingCountry'),
                        krepling_request_string('billingZip')
                    ),
                    'billing'
                );

            $addressStreet = [
                'UserToken'       => $auth['token'],
                'AddressId'       => 0,
                'streetAddress1'  => $shippingAddress['streetAddress1'],
                'streetAddress2'  => $shippingAddress['streetAddress2'],
                'IsDefault'       => false,
                'UserId'          => $auth['user_id'],
                'City'            => $shippingAddress['city'],
                'State'           => $shippingAddress['state'],
                'Country'         => $shippingAddress['country'],
                'ZipCode'         => $shippingAddress['zipCode'],
                'IsDeleted'       => false,
                'BillingAddress'  => krepling_request_bool('is_sameBillingShipping'),
                'BillingAddress1' => $billingAddressData['streetAddress1'],
                'BillingAddress2' => $billingAddressData['streetAddress2'],
                'BillingZipCode'  => $billingAddressData['zipCode'],
                'BillingCountry'  => $billingAddressData['country'],
                'BillingCity'     => $billingAddressData['city'],
                'BillingState'    => $billingAddressData['state'],
            ];
        }

        $serviceBody = krepling_service_private_response('/checkout/address/add', $addressStreet);
        $upstream = krepling_service_extract_upstream_data($serviceBody);

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to add address.'
        );
        break;

    case 'removeAddress':
        $auth = krepling_require_checkout_authentication();
        $addressId = krepling_request_int('addressID');

        krepling_require_checkout_address_by_id(
            $auth['user_detail'],
            $addressId,
            'Invalid address selected.'
        );

        $serviceBody = krepling_service_private_response(
            '/checkout/address/remove',
            [
                'UserToken' => $auth['token'],
                'AddressId' => $addressId,
                'UserId'    => $auth['user_id'],
            ]
        );
        $upstream = krepling_service_extract_upstream_data($serviceBody);

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to remove address.'
        );
        break;

    case 'updateAddress':
        try {
            $auth = krepling_require_checkout_authentication();
            $addressId = krepling_request_int('addressID');

            krepling_require_checkout_address_by_id(
                $auth['user_detail'],
                $addressId,
                'Invalid address selected.'
            );

            $shippingAddress = krepling_validate_address_payload(
                krepling_normalize_address_payload(
                    krepling_request_string('newstreetaddress1'),
                    krepling_request_string('newstreetaddress2'),
                    krepling_request_string('ucity'),
                    krepling_request_string('ustate'),
                    krepling_request_string('ucountry'),
                    krepling_request_string('uzip')
                ),
                'shipping'
            );

            $billingAddressData = krepling_request_bool('is_sameBillingShipping')
                ? krepling_copy_billing_address_from_shipping($shippingAddress)
                : krepling_validate_address_payload(
                    krepling_normalize_address_payload(
                        krepling_request_string('billingAddress'),
                        krepling_request_string('billingAddress1'),
                        krepling_request_string('billingCity'),
                        krepling_request_string('billingState'),
                        krepling_request_string('billingCountry'),
                        krepling_request_string('billingZip')
                    ),
                    'billing'
                );

            $address = trim($shippingAddress['streetAddress1'] . ' ' . $shippingAddress['streetAddress2']);

            $serviceBody = krepling_service_private_response(
                '/checkout/address/update',
                [
                    'UserToken'       => $auth['token'],
                    'AddressId'       => $addressId,
                    'streetAddress1'  => $shippingAddress['streetAddress1'],
                    'streetAddress2'  => $shippingAddress['streetAddress2'],
                    'City'            => $shippingAddress['city'],
                    'Country'         => $shippingAddress['country'],
                    'State'           => $shippingAddress['state'],
                    'ZipCode'         => $shippingAddress['zipCode'],
                    'IsDefault'       => false,
                    'UserId'          => $auth['user_id'],
                    'IsDeleted'       => false,
                    'objtblUser'      => null,
                    'BillingAddress'  => krepling_request_bool('is_sameBillingShipping'),
                    'BillingAddress1' => $billingAddressData['streetAddress1'],
                    'BillingAddress2' => $billingAddressData['streetAddress2'],
                    'BillingZipCode'  => $billingAddressData['zipCode'],
                    'BillingCountry'  => $billingAddressData['country'],
                    'BillingCity'     => $billingAddressData['city'],
                    'BillingState'    => $billingAddressData['state'],
                ]
            );
            $upstream = krepling_service_extract_upstream_data($serviceBody);

            $actionResponse = krepling_service_status_message_response(
                $upstream,
                'Unable to update address.',
                [
                    'address' => $address,
                ]
            );
        } catch (\Exception $e) {
            $actionResponse = json_encode([
                'status'  => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
        }
        break;

    case 'setDefaultAddress':
        $auth = krepling_require_checkout_authentication();
        $addressId = krepling_request_int('addressID');

        krepling_require_checkout_address_by_id(
            $auth['user_detail'],
            $addressId,
            'Invalid address selected.'
        );

        $serviceBody = krepling_service_private_response(
            '/checkout/address/set-default',
            [
                'UserToken' => $auth['token'],
                'Id'        => $addressId,
                'UserId'    => $auth['user_id'],
                'IsDefault' => false,
                'IsDeleted' => false,
            ]
        );
        $upstream = krepling_service_extract_upstream_data($serviceBody);

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to set default address.'
        );
        break;

    case 'reqCountry':
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_allowed_countries();
        $country_data = [];

        foreach ($countries as $key => $value) {
            $country_data[] = [
                'countryCode' => $key,
                'countryName' => $value,
            ];
        }

        $device_json = krepling_get_device_data(KREPLING_IP_ADDRESS_API);
        $device_data = json_decode($device_json);
        $actionResponse = json_encode([
            'country' => $country_data,
            'defaultCountry' => $device_data->country,
            'defaultState' => $device_data->region
        ]);
        break;

    case 'getStates':
        $countries_obj = new WC_Countries();
        $selected_country_states = $countries_obj->get_states(strtoupper(krepling_request_string('country')));
        $actionResponse = json_encode($selected_country_states);
        break;

    case 'getZipcodePlaceholder':
        $placeholderUrl = "/MerchantWeb/GetCountry";
        $actionResponse = krepling_get_curl_request($placeholderUrl);
        break;

    default:
        return null;
    }

    return $actionResponse;
}
