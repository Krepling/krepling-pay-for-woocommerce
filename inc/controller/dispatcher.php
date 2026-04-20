<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/actions/auth.php';
require_once __DIR__ . '/actions/addresses.php';
require_once __DIR__ . '/actions/payment.php';
require_once __DIR__ . '/actions/misc.php';

function krepling_dispatch_action($requestAction)
{
    $migratedResponse = krepling_dispatch_migrated_action($requestAction);
    if ($migratedResponse !== null) {
        return $migratedResponse;
    }

    $dispatchers = array(
        'krepling_dispatch_auth_action',
        'krepling_dispatch_addresses_action',
        'krepling_dispatch_payment_action',
        'krepling_dispatch_misc_action',
    );

    foreach ($dispatchers as $dispatcher) {
        $response = $dispatcher($requestAction);
        if ($response !== null) {
            return $response;
        }
    }

    return false;
}
