<?php
defined('ABSPATH') || exit;

function krepling_dispatch_misc_action($requestAction)
{
    global $krepling_api_keys, $krepling_count_product;

    $actionResponse = null;

    switch ($requestAction) {

    case 'deleteUserAccount':
        $auth = krepling_require_checkout_authentication();

        $upstream = krepling_service_private_upstream(
            '/account/delete',
            array(
                'Id' => $auth['user_id'],
            )
        );

        if (krepling_service_extract_status($upstream, 500) === 1) {
            krepling_destroy_current_session();
        }

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to delete account.'
        );
        break;

    case 'getReviewDevices':
        $auth = krepling_require_checkout_authentication();

        $upstream = krepling_service_private_upstream(
            '/account/review-devices',
            array(
                'Id' => $auth['user_id'],
            )
        );

        if (!empty($upstream['logoutDevices'])) {
            $reviewDevices = json_decode(wp_json_encode($upstream['logoutDevices']));
            krepling_wc_session_set('reviewDeviceData', $reviewDevices);

            $actionResponse = krepling_json_response(array(
                'logoutDevices' => $reviewDevices,
            ));
        } else {
            $actionResponse = krepling_json_response(array(
                'status'  => isset($upstream['status']) ? (int) $upstream['status'] : 500,
                'message' => !empty($upstream['message'])
                    ? $upstream['message']
                    : 'Unable to load review devices.',
            ));
        }
        break;

    // Logout unrecognized devices

        default:
            return null;
    }

    return $actionResponse;
}
