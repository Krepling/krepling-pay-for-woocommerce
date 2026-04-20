<?php

defined('ABSPATH') || exit;

require_once __DIR__ . '/inc/controller/bootstrap.php';
require_once __DIR__ . '/inc/controller/legacy-api.php';
require_once __DIR__ . '/inc/controller/actions/migrated.php';
require_once __DIR__ . '/inc/controller/dispatcher.php';

$krepling_action_response = krepling_dispatch_action($krepling_request_action);

header('Content-Type: application/json; charset=utf-8');
if (is_string($krepling_action_response)) {
    $krepling_decoded_response = json_decode($krepling_action_response, true);
    echo wp_json_encode(
        JSON_ERROR_NONE === json_last_error() ? $krepling_decoded_response : $krepling_action_response
    );
} else {
    echo wp_json_encode($krepling_action_response);
}
exit;
