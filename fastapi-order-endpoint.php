<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('krepling/v1', '/public-service-token', array(
        'methods'  => 'POST',
        'callback' => 'krepling_public_service_token_endpoint',
        'permission_callback' => 'krepling_verify_public_service_token_request',
    ));
});

function krepling_verify_public_service_token_request(WP_REST_Request $request)
{
    if (strtoupper((string) $request->get_method()) !== 'POST') {
        return new WP_Error(
            'krepling_method_not_allowed',
            'Method not allowed.',
            array('status' => 405)
        );
    }

    $origin  = '';
    $referer = '';

    if (isset($_SERVER['HTTP_ORIGIN']) && !is_array($_SERVER['HTTP_ORIGIN'])) {
        $origin = trim(esc_url_raw(wp_unslash((string) $_SERVER['HTTP_ORIGIN'])));
    }

    if (isset($_SERVER['HTTP_REFERER']) && !is_array($_SERVER['HTTP_REFERER'])) {
        $referer = trim(esc_url_raw(wp_unslash((string) $_SERVER['HTTP_REFERER'])));
    }

    $home_url   = home_url();
    $home_host  = wp_parse_url($home_url, PHP_URL_HOST);
    $origin_host = $origin ? wp_parse_url($origin, PHP_URL_HOST) : '';
    $referer_host = $referer ? wp_parse_url($referer, PHP_URL_HOST) : '';

    $origin_ok  = !empty($origin_host) && !empty($home_host) && strtolower($origin_host) === strtolower($home_host);
    $referer_ok = !empty($referer_host) && !empty($home_host) && strtolower($referer_host) === strtolower($home_host);

    if (!$origin_ok && !$referer_ok) {
        return new WP_Error(
            'krepling_forbidden_origin',
            'Forbidden origin.',
            array('status' => 403)
        );
    }

    return true;
}

function krepling_public_service_token_endpoint(WP_REST_Request $request)
{
    $bundle = krepling_services_get_public_maps_token(false);

    if (is_wp_error($bundle)) {
        return new WP_REST_Response(array(
            'status'     => 502,
            'message'    => $bundle->get_error_message(),
            'error_data' => $bundle->get_error_data(),
        ), 502);
    }

    return new WP_REST_Response(array(
        'token'      => (string) $bundle['token'],
        'expires_at' => (int) $bundle['expires_at'],
        'maps_base'  => trailingslashit(krepling_services_get_base()) . 'maps',
    ), 200);
}
