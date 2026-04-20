<?php
defined('ABSPATH') || exit;

function krepling_dispatch_migrated_action($requestAction)
{
    $actionResponse = null;

    switch ($requestAction) {

    case 'changeEmailAddress':
        $auth = krepling_require_checkout_authentication();
        $updatedEmail = krepling_request_email('email');

        if (!krepling_is_valid_email_address($updatedEmail)) {
            krepling_validation_error('Enter your valid email address');
        }

        if (strcasecmp($updatedEmail, $auth['login_email']) === 0) {
            krepling_validation_error('Enter a new email address');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/profile/change-email/request-otp',
            array(
                'OldEmail' => $auth['login_email'],
                'EmailId'  => $updatedEmail,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to send email verification code.'
        );
        break;

    case 'verifyOtpEmailAddress':
        $verifyEmail = krepling_request_email('email');
        $verifyOtp   = krepling_request_digits('otp');

        if (!krepling_is_valid_email_address($verifyEmail)) {
            krepling_validation_error('Enter your valid email address');
        }

        if (!krepling_is_valid_otp($verifyOtp)) {
            krepling_validation_error('Enter 6-digit code');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/profile/change-email/verify-otp',
            array(
                'Email' => $verifyEmail,
                'OTP'   => (int) $verifyOtp,
            )
        );

        if (krepling_service_extract_status($upstream, 500) === 1) {
            krepling_destroy_current_session();
        }

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Email verification failed.'
        );
        break;

    case 'changePhoneNumber':
        $auth = krepling_require_checkout_authentication();
        $updatedPhone = krepling_request_digits('phone');
        $updatedCountryCode = krepling_request_string('countryCode');

        if (!krepling_is_valid_phone_number($updatedPhone)) {
            krepling_validation_error('Enter your phone number');
        }

        if (!krepling_is_valid_country_dial_code($updatedCountryCode)) {
            krepling_validation_error('Enter your country code');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/profile/change-phone/request-otp',
            array(
                'ID'           => $auth['user_id'],
                'PhoneNumber'  => $updatedPhone,
                'CountryCode'  => $updatedCountryCode,
                'Resendsms'    => false,
                'EmailAddress' => $auth['user_detail']->userVM->email,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to send phone verification code.'
        );
        break;

    case 'verifyOtpPhoneNumber':
        $verifyPhone = krepling_request_digits('phone');
        $verifyPhoneOtp = krepling_request_digits('otp');
        $verifyPhoneCountryCode = krepling_request_string('countryCode');

        if (!krepling_is_valid_phone_number($verifyPhone)) {
            krepling_validation_error('Enter your phone number');
        }

        if (!krepling_is_valid_country_dial_code($verifyPhoneCountryCode)) {
            krepling_validation_error('Enter your country code');
        }

        if (!krepling_is_valid_otp($verifyPhoneOtp)) {
            krepling_validation_error('Enter 6-digit code');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/profile/change-phone/verify-otp',
            array(
                'PhoneNumber' => $verifyPhone,
                'OTP'         => (int) $verifyPhoneOtp,
                'CountryCode' => $verifyPhoneCountryCode,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Phone verification failed.'
        );
        break;

    case 'logoutSelectedDevices':
        krepling_require_checkout_authentication();
        $deviceIdArray = krepling_request_int_array('devices', 'GET');

        if (empty($deviceIdArray)) {
            krepling_validation_error('Select at least one device.');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/devices/logout',
            array(
                'MyNum' => $deviceIdArray,
            )
        );

        $actionResponse = krepling_json_response(array(
            'logoutDevices' => $upstream,
        ));
        break;

    case 'resend_smsOtp_action':
        $auth = krepling_require_checkout_authentication();
        $userData = krepling_wc_session_user_detail();

        $upstream = krepling_service_private_upstream(
            '/checkout/profile/change-phone/request-otp',
            array(
                'ID'           => $auth['user_id'],
                'PhoneNumber'  => $userData->userVM->phone,
                'CountryCode'  => $userData->userVM->countryCode,
                'Resendsms'    => true,
                'EmailAddress' => $userData->userVM->email,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to resend OTP.',
            array(
                'resendOtpData' => $userData->userVM->countryCode . ' ' . $userData->userVM->phone,
            )
        );
        break;

    case 'setDefaultAction':
        krepling_require_checkout_authentication();
        $userData = krepling_wc_session_user_detail();

        $phone = $userData->userVM->emailSetDefault == 1 ? $userData->userVM->phone : null;
        $countryCode = $userData->userVM->emailSetDefault == 1 ? $userData->userVM->countryCode : null;
        $email = $userData->userVM->phoneSetDefault == 1 ? $userData->userVM->email : null;

        $upstream = krepling_service_private_upstream(
            '/checkout/preferences/set-default',
            array(
                'Status'      => 0,
                'Message'     => null,
                'CountryCode' => $countryCode,
                'PhoneNumber' => $phone,
                'Resendsms'   => true,
                'Id'          => $userData->userVM->userId,
                'Email'       => $email,
            )
        );

        $actionResponse = krepling_json_response($upstream);
        break;

    case 'thisDeviceWasMe':
        krepling_require_checkout_authentication();
        $deviceIdArray = krepling_request_int_array('devices', 'GET');

        if (empty($deviceIdArray)) {
            krepling_validation_error('Select at least one device.');
        }

        $upstream = krepling_service_private_upstream(
            '/checkout/devices/this-was-me',
            array(
                'MyNum' => $deviceIdArray,
            )
        );

        $actionResponse = krepling_json_response(array(
            'logoutDevices' => $upstream,
        ));
        break;

    case 'smsLoginAlerts':
        $auth = krepling_require_checkout_authentication();

        $upstream = krepling_service_private_upstream(
            '/checkout/preferences/sms-login-alerts',
            array(
                'SmsLoginAlert' => krepling_request_bool('login_alert_status'),
                'Email'         => $auth['user_detail']->userVM->email,
            )
        );

        $actionResponse = krepling_service_status_message_response(
            $upstream,
            'Unable to update login alert preference.'
        );
        break;

    default:
        return null;
    }

    return $actionResponse;
}
