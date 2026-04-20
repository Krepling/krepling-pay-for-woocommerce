<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('krepling_services_get_base')) {
    function krepling_services_get_base()
    {
        if (defined('KREPLING_SERVICES_BASE') && KREPLING_SERVICES_BASE) {
            return untrailingslashit(KREPLING_SERVICES_BASE);
        }

        $env = getenv('KREPLING_SERVICES_BASE');
        if ($env) {
            return untrailingslashit($env);
        }

        return '';
    }
}

if (!function_exists('krepling_services_get_gateway_settings')) {
    function krepling_services_get_gateway_settings()
    {
        $settings = get_option('woocommerce_krepling_settings');
        return is_array($settings) ? $settings : array();
    }
}

if (!function_exists('krepling_services_token_cache_key')) {
    function krepling_services_token_cache_key($merchant_id)
    {
        return 'krepling_service_token_' . md5((string) $merchant_id);
    }
}

if (!function_exists('krepling_services_public_maps_token_cache_key')) {
    function krepling_services_public_maps_token_cache_key($merchant_id)
    {
        return 'krepling_public_maps_token_' . md5((string) $merchant_id);
    }
}

if (!function_exists('krepling_services_get_token')) {
    function krepling_services_get_token($force_refresh = false)
    {
        $settings = krepling_services_get_gateway_settings();

        $merchant_id = isset($settings['merchant_id']) ? trim((string) $settings['merchant_id']) : '';
        $secret_id   = isset($settings['secret_id']) ? trim((string) $settings['secret_id']) : '';

        if ($merchant_id === '' || $secret_id === '') {
            return new WP_Error('krepling_missing_credentials', 'Merchant ID or Secret ID is missing.');
        }

        $cache_key = krepling_services_token_cache_key($merchant_id);

        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && !empty($cached['token']) && !empty($cached['expires_at'])) {
                if ((int) $cached['expires_at'] > (time() + 60)) {
                    return $cached['token'];
                }
            }
        }

        $base = krepling_services_get_base();
        if ($base === '') {
            return new WP_Error('krepling_missing_service_base', 'Krepling services base URL is not configured.');
        }

        $payload = array(
            'merchant_id'    => $merchant_id,
            'secret_key'     => $secret_id,
            'site_url'       => home_url('/'),
            'plugin_version' => defined('KREPLING_PLUGIN_VERSION') ? KREPLING_PLUGIN_VERSION : 'unknown',
        );

        $response = wp_remote_post(
            $base . '/auth/merchant-token',
            array(
                'timeout' => 20,
                'headers' => array(
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($payload),
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $decoded     = json_decode($body, true);

        if ($status_code >= 400) {
            return new WP_Error(
                'krepling_service_token_failed',
                'Failed to get service token.',
                array(
                    'status_code' => $status_code,
                    'body'        => $decoded ?: $body,
                )
            );
        }

        if (!is_array($decoded) || empty($decoded['token'])) {
            return new WP_Error(
                'krepling_invalid_service_token_response',
                'Invalid token response from krepling-services.',
                array(
                    'status_code' => $status_code,
                    'body'        => $decoded ?: $body,
                )
            );
        }

        $expires_at = !empty($decoded['expires_at'])
            ? (int) $decoded['expires_at']
            : (time() + 900);

        $ttl = max(60, $expires_at - time());

        set_transient($cache_key, array(
            'token'      => $decoded['token'],
            'expires_at' => $expires_at,
        ), $ttl);

        return $decoded['token'];
    }
}

if (!function_exists('krepling_services_get_public_maps_token')) {
    function krepling_services_get_public_maps_token($force_refresh = false)
    {
        $settings = krepling_services_get_gateway_settings();
        $merchant_id = isset($settings['merchant_id']) ? trim((string) $settings['merchant_id']) : '';

        if ($merchant_id === '') {
            return new WP_Error('krepling_missing_merchant_id', 'Merchant ID is missing.');
        }

        $cache_key = krepling_services_public_maps_token_cache_key($merchant_id);

        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && !empty($cached['token']) && !empty($cached['expires_at'])) {
                if ((int) $cached['expires_at'] > (time() + 30)) {
                    return $cached;
                }
            }
        }

        $response = krepling_services_json_request('POST', '/auth/public-maps-token', array(), true);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $decoded     = json_decode($body, true);

        if ($status_code >= 400) {
            return new WP_Error(
                'krepling_public_maps_token_failed',
                'Failed to get public maps token.',
                array(
                    'status_code' => $status_code,
                    'body'        => $decoded ?: $body,
                )
            );
        }

        if (!is_array($decoded) || empty($decoded['token'])) {
            return new WP_Error(
                'krepling_invalid_public_maps_token_response',
                'Invalid public maps token response from krepling-services.',
                array(
                    'status_code' => $status_code,
                    'body'        => $decoded ?: $body,
                )
            );
        }

        $expires_at = !empty($decoded['expires_at'])
            ? (int) $decoded['expires_at']
            : (time() + 300);

        $ttl = max(30, $expires_at - time());

        $bundle = array(
            'token'      => (string) $decoded['token'],
            'expires_at' => $expires_at,
        );

        set_transient($cache_key, $bundle, $ttl);

        return $bundle;
    }
}

if (!function_exists('krepling_services_json_request')) {
    function krepling_services_json_request($method, $path, array $payload = array(), $private = false)
    {
        $base = krepling_services_get_base();

        if ($base === '') {
            return new WP_Error('krepling_missing_service_base', 'Krepling services base URL is not configured.');
        }

        $headers = array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        );

        if ($private) {
            $token = krepling_services_get_token(false);
            if (is_wp_error($token)) {
                return $token;
            }
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $args = array(
            'method'  => strtoupper((string) $method),
            'timeout' => 25,
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
        );

        $url      = $base . '/' . ltrim($path, '/');
        $response = wp_remote_request($url, $args);

        if ($private && !is_wp_error($response)) {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 401) {
                $token = krepling_services_get_token(true);
                if (!is_wp_error($token)) {
                    $headers['Authorization'] = 'Bearer ' . $token;
                    $args['headers'] = $headers;
                    $response = wp_remote_request($url, $args);
                }
            }
        }

        return $response;
    }
}