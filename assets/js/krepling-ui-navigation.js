(function ($) {
$(document).on('click', '#krepling_settings',function(){
    $('.wallet_setting_page').show();
    $('#stepFourId').hide();
    $('#myHeader').hide();
});

$(document).on('click', '.back_wallet_setting', function() {
    $('.wallet_setting_page').hide();
    $('#stepFourId').show();
    $('#myHeader').show();
});

$(document).on('click', '.wallet_change_password',function(){
    $('.wallet_changePassword_page').show();
    $('.wallet_setting_page').hide();
});

$(document).on('click', '.back_change_password',function(){
    $('.wallet_changePassword_page').hide();
    $('.wallet_setting_page').show();
});

$(document).on('click', '.wallet_logout',function(){
    $('.wallet_logout_page').show();
    $('.wallet_setting_page').hide();
});

$(document).on('click', '.back_logout',function(){
    $('.wallet_logout_page').hide();
    $('.wallet_setting_page').show();
});

$(document).on('click', '.wallet_delete',function(){
    $('.wallet_delete_page').show();
    $('.wallet_setting_page').hide();
});

$(document).on('click', '.back_delete',function(){
    $('.wallet_delete_page').hide();
    $('.wallet_setting_page').show();
});

$(document).on('click', '.back_checkout_aadCard',function(){
    $('#addcard').hide();
    $('#stepFourId').show();
    $('#myHeader').show();
    $('.account_card_copyright').show();
});

$(document).on('click', '.wallet_authentication',function(){
    $('.wallet_authentication_page').show();
    $('.wallet_setting_page').hide();
});

$(document).on('click', '.back_authentication',function(){
    $('.wallet_authentication_page').hide();
    $('.wallet_setting_page').show();
});

$(document).on('click', '.wallet_loggedIn_devices',function(){
    $('.wallet_loggedIn_devices_page').show();
    $('.wallet_setting_page').hide();
});

$(document).on('click', '.back_loggedIn_devices',function(){
    $('.wallet_loggedIn_devices_page').hide();
    $('.wallet_setting_page').show();
});

$(document).on('click', '.wallet_login_alerts',function(){
    $('.wallet_login_alert_page').show();
    $('.wallet_loggedIn_devices_page').hide();
});

$(document).on('click', '.back_loginAlert',function(){
    $('.wallet_login_alert_page').hide();
    $('.review_devices_page').hide();
    $('.wallet_loggedIn_devices_page').show();
});

// update phone number
$('#update_phoneNumber').on('click',function(){
    $('.change_phonenumber_page').show();
    $('.wallet_authentication_page').hide();
    $('#otp_phoneNumber').removeAttr("style");
});

$('.back_to_authentication').on('click',function(){
    $('.change_phonenumber_page').hide();
    $('.change_emailaddress_page').hide();
    $('.wallet_authentication_page').show();
});

// update email address
$('#update_emailAddress').on('click',function(){
    $('.change_emailaddress_page').show();
    $('.wallet_authentication_page').hide();
});

$('.back_change_phonenumber').on('click',function(){
    $('.change_phonenumber_otp_page').hide();
    $('.change_phonenumber_page').show();
});

$('.back_change_emailaddress').on('click',function(){
    $('.change_emailaddress_otp_page').hide();
    $('.change_emailaddress_page').show();
});

// review devices
$('#review_devices').on('click',function(){
    $('.review_devices_page').show();
    $('.wallet_loggedIn_devices_page').hide();
});

$('.back_resend_otp').on('click',function(){
    $('.verify_resend_otp_page').hide();
    $('.wallet_authentication_page').show();
});

$(document).on('click', '#addinformation_button', function(){
    $("#additional_information").show();
    $('.add_additional_unitAddress').hide();
});

$(document).on('click', '#additional_shippingAddress', function(){
    $(".optional_shipping_address").show();
    $('.add_additional_shippingAddress').hide();
});

$(document).on('click', '#additional_billingAddress', function(){
    $(".optional_billing_address").show();
    $('.add_additional_billingAddress').hide();
});

$('#editaddressbtn, .close_edit_address').click(function(){
    $('.editmodal').hide();
});

$(document).on('click', '.additional_shippingAddress', function(){
    $(".optional_shipping_address").show();
    $('.add_additional_shippingAddress').hide();
});

$(document).on('click', '.additional_billingAddress', function(){
    $(".optional_billing_address").show();
    $('.add_additional_billingAddress').hide();
});

$('.billing_checkbox, #signupBillingCheckbox').on('click', function(){
    $('#signupBillingCheckbox').prop('checked', !($('#signupBillingCheckbox').is(':checked')));
    var is_sameBillingShipping = $('#signupBillingCheckbox').is(':checked');
    is_sameBillingShipping ? $('#billingAddressForm').hide() : $('#billingAddressForm').show();
});

})(window.jQuery);
