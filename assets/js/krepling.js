var $ = window.jQuery;
var parseData = window.kreplingConfig || {};

function kreplingNormalizeAjaxData(data) {
    if (typeof data === 'string') {
        var normalized = {};

        if (data) {
            data.split('&').forEach(function(part) {
                if (!part) {
                    return;
                }

                var pieces = part.split('=');
                var key = decodeURIComponent((pieces.shift() || '').replace(/\+/g, ' '));
                var value = decodeURIComponent((pieces.join('=') || '').replace(/\+/g, ' '));

                if (key) {
                    normalized[key] = value;
                }
            });
        }

        return normalized;
    }

    return $.extend({}, data || {});
}

$.ajaxPrefilter(function(options) {
    var method = (options.type || options.method || 'GET').toUpperCase();
    var url = options.url || '';
    var originalWasString = typeof options.data === 'string';

    if (method !== 'POST') {
        return;
    }

    if (typeof url !== 'string' || url.indexOf('controller.php') === -1) {
        return;
    }

    var payload = kreplingNormalizeAjaxData(options.data);
    var originalAction = payload.krepling_action || payload.action || '';

    if (originalAction && originalAction !== (parseData.ajax_action || 'krepling_dispatch')) {
        payload.krepling_action = originalAction;
    }

    payload.action = parseData.ajax_action || 'krepling_dispatch';

    if (!payload._wpnonce && parseData.csrf_nonce) {
        payload._wpnonce = parseData.csrf_nonce;
    }

    options.url = parseData.ajax_url || url;
    options.data = originalWasString ? $.param(payload) : payload;
});

function verifyKreplingOrderAndRedirect(response) {
    if (
        !response ||
        parseInt(response.status, 10) !== 1 ||
        !response.order_id ||
        !response.order_key
    ) {
        if (response && response.thankyou_url) {
            window.location.assign(response.thankyou_url);
        }
        return;
    }

    $.ajax({
        url: kreplingControllerUrl(),
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'verifyOrderPayment',
            order_id: response.order_id,
            order_key: response.order_key,
            _wpnonce: parseData.krepling_nonce || parseData.csrf_nonce
        },
        success: function (verifyResponse) {
            if (
                verifyResponse &&
                parseInt(verifyResponse.status, 10) === 1 &&
                verifyResponse.paid &&
                verifyResponse.thankyou_url
            ) {
                window.location.assign(verifyResponse.thankyou_url);
                sessionStorage.clear();
                return;
            }

            $('.payment_section').removeClass('hideclass');
            $('.payment_section .common_toster').addClass('error_toster_messsage');
            $('.payment_section .toster_message').text(
                (verifyResponse && verifyResponse.message) ? verifyResponse.message : 'Payment verification failed.'
            );
            $('#saveCard_payment, #complete_payment, #btnFirstStep').show();
        },
        error: function () {
            $('.payment_section').removeClass('hideclass');
            $('.payment_section .common_toster').addClass('error_toster_messsage');
            $('.payment_section .toster_message').text('Unable to verify payment status. Please refresh and check your order.');
            $('#saveCard_payment, #complete_payment, #btnFirstStep').show();
        }
    });
}

function kreplingControllerUrl() {
    return parseData.ajax_url || (parseData.base_url + 'controller.php');
}

function showKreplingCheckoutSectionError(sectionSelector, message) {
    var $section = $(sectionSelector);

    if (!$section.length) {
        return;
    }

    $section.removeClass('hideclass');
    $section.find('.common_toster').removeClass().addClass('common_toster error_toster_messsage');
    $section.find('.toster_message').text(message);
}

function getKreplingWooCheckoutFieldPayload() {
    function readValue(selector) {
        var $field = $(selector).first();

        if (!$field.length) {
            return '';
        }

        var value = $field.val();
        if (Array.isArray(value)) {
            value = value[0] || '';
        }

        value = $.trim(String(value == null ? '' : value));
        return value === 'undefined' ? '' : value;
    }

    return {
        billing_first_name: readValue('#billing_first_name'),
        billing_last_name: readValue('#billing_last_name'),
        billing_email: readValue('#billing_email'),
        billing_country: readValue('#billing_country'),
        billing_address_1: readValue('#billing_address_1'),
        billing_city: readValue('#billing_city'),
        billing_state: readValue('#billing_state'),
        billing_postcode: readValue('#billing_postcode'),
        billing_phone: readValue('#billing_phone')
    };
}

function highlightKreplingWooCheckoutField(selector) {
    var $field = $(selector).first();

    if (!$field.length) {
        return;
    }

    $field.addClass('addErrorBorder');

    if ($field.hasClass('select2-hidden-accessible')) {
        $field.next('.select2').find('.select2-selection').addClass('addErrorBorder');
    }

    var $scrollTarget = $field.hasClass('select2-hidden-accessible') ? $field.next('.select2') : $field;
    if ($scrollTarget.length && $scrollTarget.offset()) {
        $('html, body').stop(true).animate({
            scrollTop: Math.max($scrollTarget.offset().top - 120, 0)
        }, 150);
    }

    if ($field.is(':visible')) {
        $field.trigger('focus');
    }
}

function validateKreplingWooCheckoutFields(sectionSelector) {
    var payload = getKreplingWooCheckoutFieldPayload();
    var requiredFields = [
        { key: 'billing_first_name', selector: '#billing_first_name', message: 'First name is a required field' },
        { key: 'billing_last_name', selector: '#billing_last_name', message: 'Last name is a required field' },
        { key: 'billing_email', selector: '#billing_email', message: 'Email address is a required field' },
        { key: 'billing_country', selector: '#billing_country', message: 'Country is a required field' },
        { key: 'billing_address_1', selector: '#billing_address_1', message: 'Street address is a required field' },
        { key: 'billing_city', selector: '#billing_city', message: 'Town / City is a required field' },
        { key: 'billing_state', selector: '#billing_state', message: 'Province / State is a required field' },
        { key: 'billing_postcode', selector: '#billing_postcode', message: 'Postcode / ZIP is a required field' },
        { key: 'billing_phone', selector: '#billing_phone', message: 'Phone is a required field' }
    ];

    for (var i = 0; i < requiredFields.length; i++) {
        var field = requiredFields[i];
        if (!payload[field.key]) {
            highlightKreplingWooCheckoutField(field.selector);
            showKreplingCheckoutSectionError(sectionSelector, field.message);

            return {
                valid: false,
                payload: payload
            };
        }
    }

    return {
        valid: true,
        payload: payload
    };
}

function kreplingAjax(options) {
    return $.ajax(options);
}

function updateKreplingCardArrows($context) {
    var $scope = $context && $context.length ? $context : $(document);

    $scope.find('.heroSlider-fixed').each(function () {
        var $hero = $(this);
        var cardCount = $hero.find('.DCcard-img.img-wrap').length;

        if (cardCount <= 2) {
            $hero.find('.prev, .next').hide();
        } else {
            $hero.find('.prev, .next').show();
        }
    });
}

function initializeKreplingSavedCardsSlider() {
    if (typeof $.fn.slick !== 'function') {
        return false;
    }

    $('.responsive.checkout_slider').each(function () {
        var $slider = $(this);
        var $hero = $slider.closest('.heroSlider-fixed');
        var $prev = $hero.find('.prev');
        var $next = $hero.find('.next');

        if (!$slider.length) {
            return;
        }

        if ($slider.hasClass('slick-initialized')) {
            $slider.slick('setPosition');
            return;
        }

        $slider.slick({
            dots: false,
            slidesToShow: 3,
            variableWidth: true,
            prevArrow: $prev,
            nextArrow: $next,
            infinite: false,
            speed: 300,
            responsive: [
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    });

    updateKreplingCardArrows($('.heroSlider-fixed'));
    return true;
}
function toggleKreplingSavedCardPayButton() {
    var hasCvv = $.trim($('.getcvvval').val() || '').length > 0;

    $('#complete_payment').toggleClass('disabled', !hasCvv);

    // Keep the original plugin behavior: visual disabled state only.
    // This prevents the browser from swallowing the click entirely.
    if (hasCvv) {
        $('#complete_payment').prop('disabled', false);
    } else {
        $('#complete_payment').prop('disabled', false);
    }
}

function initializeKreplingCheckoutUi() {
    initializeKreplingSavedCardsSlider();
    toggleKreplingSavedCardPayButton();
}

function bootKreplingCheckoutUiWithRetry() {
    var attempts = 0;
    var maxAttempts = 20;

    function tryBoot() {
        attempts += 1;

        var hasSlider = $('.checkout_slider').length > 0;
        var hasSlick = (typeof $.fn.slick === 'function');

        if (!hasSlider) {
            if (attempts < maxAttempts) {
                setTimeout(tryBoot, 150);
            }
            return;
        }

        initializeKreplingCheckoutUi();

        if (!$('.checkout_slider').hasClass('slick-initialized') && attempts < maxAttempts) {
            setTimeout(tryBoot, 150);
        }
    }

    tryBoot();
}

$(document).ready(function () {
    bootKreplingCheckoutUiWithRetry();
});

$(window).on('load', function () {
    bootKreplingCheckoutUiWithRetry();
    setTimeout(bootKreplingCheckoutUiWithRetry, 250);
});

$("#emailAddress").on('focusout', function(){
    getSignupOtp();
});

$('#sendSmsOtpAgain').on('click', function(){
    getSignupOtp();
});

function getSignupOtp(){
    var email = $('#emailAddress').val();
    var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    $('#btnFirstStep').addClass('disabled');
    $('.smsCode').val('');
    
    if(typeof email === 'undefined' || email == ''){
        $('#emailAddress').addClass('addErrorBorder');	
        $('#registerEmailError').text('Enter a valid email address');
        $('#registerEmailError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!email_regex.test(email)){
        $('#emailAddress').addClass('addErrorBorder');	
        $('#registerEmailError').text("This email address doesn't exist");
        $('#registerEmailError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else{
        $('.email_otp_loader').show();
        $('#emailAddress').attr('disabled', 'disabled');
        kreplingAjax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
                action:'getSignupEmailOtp',
                email : email
            },
            success: function(response){
                $('.email_otp_loader').hide();
                if(response.status == 1){
                    sendAgainTimer();
                    $('#stepThirdId').show();
                    $('.sendotp_section').removeClass('hideclass');
                    $('.sendotp_section .common_toster').addClass('success_toster_messsage');
                    $('.sendotp_section .toster_message').text(response.message);
                } else if(response.status == 2){
                    sendAgainTimer();
                    $('#stepThirdId').show();
                    $('.sendotp_section').removeClass('hideclass');
                    $('.sendotp_section .common_toster').addClass('success_toster_messsage');
                    $('.sendotp_section .toster_message').text('New verification code sent');
                } else if(response.status == 3){
                    $('#emailAddress').removeAttr('disabled');
                    $('#btnFirstStep').removeClass('disabled');
                    $('.email_section').removeClass('hideclass');
                    $('#stepThirdId').hide();
                    $('.email_section .common_toster').addClass('success_toster_messsage');
                    $('.email_section .toster_message').text(response.message);
                } else if(response.status == 4){
                    $('#emailAddress').removeAttr('disabled');
                    $('.email_section').removeClass('hideclass');
                    $('#stepThirdId').hide();
                    $('.email_section .common_toster').addClass('error_toster_messsage');
                    $('.email_section .toster_message').text(response.message);
                } else {
                    $('#emailAddress').removeAttr('disabled');
                    $('.email_section').removeClass('hideclass');
                    $('#stepThirdId').hide();
                    $('.email_section .common_toster').addClass('error_toster_messsage');
                    $('.email_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }
}

$('#verifyEmailOtp').keyup(function(){
    var verifyEmail = $('#emailAddress').val();
    var signup_otp = Array.from(document.querySelector("#SMSArea").querySelectorAll("input[type=tel]")).map(x => x.value);
    var otp = signup_otp.toString().split(",").join("");
    if(otp.length == 6){
          kreplingAjax({
              url: parseData.base_url+'controller.php',
              type:"POST",
              dataType:'json',
              data: {
                  action:'verifyOTP',
                  email : verifyEmail,
                  otp : otp
              },
            success: function(response){
                if(response.status == 1){
                    $('#btnFirstStep').removeClass('disabled');
                    $('#stepThirdId').hide();
                    $('.otpverified_section').removeClass('hideclass');
                    $('.otpverified_section .common_toster').addClass('success_toster_messsage');
                }else{
                    $('#SMSArea input[type=tel]').addClass('addErrorBorder');
                    $('.sendotp_section').removeClass('hideclass');
                    $('.sendotp_section .common_toster').addClass('error_toster_messsage');
                    $('.sendotp_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }else{
        $('#SMSArea input[type=tel]').addClass('addErrorBorder');
        $('#verifySignupOtpError').text('Enter 6-digit code');
        $('#verifySignupOtpError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});
 
$(document).on('click', '#btnFirstStep', function() {
    var is_checked = $('#iskreplingFastId').is(':checked');
    var firstLastName = $('#first_last').val();
    var cardNumber = $('#card').val().replace(/\s+/g, '');
    var cardValidityDate = $('#cardvalidityDateId').val(); 
    var cardCVVNumber = $('#txtcvvNumberId').val();
    //new address fields
    var address1 = $('#address1').val();
    var address2 = $('#address2').val();
    var type_state = $('#registerState').val();
    var select_state = $('#registerStateDropdown :selected').val();
    var city = $('#registerCity').val();
    var zip_code = $('#registerZip').val();
    var countryManually = $('#registerCountry #countryManually').val() ||
                      $('#registerCountry #countryManually :selected').val() ||
                      '';
    var currentMonth = new Date().getMonth() + 1;
    var currentYear = new Date().getFullYear().toString().slice(-2);
    var month = cardValidityDate.toLocaleString().split('/')[0];
    var year = cardValidityDate.toLocaleString().split('/')[1];
    var cvv_regex = /^[0-9]{3,4}$/;
    var name_regex = /^[a-zA-Z]+ [a-zA-Z]+$/;
    var state = select_state != '' && select_state != undefined ? select_state : type_state;

    countryManually = kpNormalizeCountryCode(countryManually);

    if (!countryManually) {
        countryManually = kpInferCountryFromState(state);
    }

    if (!countryManually) {
        countryManually = kpInferCountryFromPostal(zip_code);
    }

    var cardHolderName = $('#card_holder_name').val();
    var is_sameBillingShipping = $('#signupBillingCheckbox').is(':checked');
    var companyName = $('#companyName').val();
    var billingAddress =  $('#billingAddress').val();
    var billingAddress1 =  $('#billingAddress1').val();
    var billingZip =  $('#billingZip').val();
    var billingCountry = $('#billingCountry #countryManually').val() ||
                     $('#billingCountry #countryManually :selected').val() ||
                     '';
    var billingCity =  $('#billingCity').val();
    var billing_text_state =  $('#billingState').val();
    var billing_select_state = $('#billingStateDropdown :selected').val();
    var billingState = billing_select_state != '' && billing_select_state != undefined ? billing_select_state : billing_text_state;
    billingCountry = kpNormalizeCountryCode(billingCountry);

    if (!billingCountry) {
        billingCountry = kpInferCountryFromState(billingState);
    }

    if (!billingCountry) {
        billingCountry = kpInferCountryFromPostal(billingZip);
    }
    var email = $('#emailAddress').val();
    var mobile = $('#phoneNumberIds').val().replace(/[_\s]/g, '');
    var dial_code = $('.iti__selected-dial-code').text();
    var productCartName = $('#productCartName').val();
    var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    var maxYear = parseInt(currentYear) + 25;
    var wooCheckoutValidation = validateKreplingWooCheckoutFields('.registration_section');

    if (!wooCheckoutValidation.valid) {
        removeValidationError();
        return false;
    }

    kpPayDebug('values', {
        hideAddress: parseData.hide_Address,
        firstLastName: firstLastName,
        email: email,
        mobile: mobile,
        dial_code: dial_code,

        address1: address1,
        address2: address2,
        city: city,
        zip_code: zip_code,
        type_state: type_state,
        select_state: select_state,
        resolved_state: state,
        countryManually: countryManually,

        billingAddress: billingAddress,
        billingAddress1: billingAddress1,
        billingCity: billingCity,
        billingZip: billingZip,
        billingCountry: billingCountry,
        billing_text_state: billing_text_state,
        billing_select_state: billing_select_state,
        billingState: billingState,
        is_sameBillingShipping: is_sameBillingShipping,

        cardNumberLength: cardNumber ? cardNumber.length : 0,
        cardValidityDate: cardValidityDate,
        cardCVVLength: cardCVVNumber ? cardCVVNumber.length : 0,
        cardHolderName: cardHolderName
    });

    kpPayDebug('checkpoint', { step: 'after_value_capture' });
    kpPayDebug('checkpoint', { step: 'before_email_validation' });

    if(typeof email === 'undefined' || email == ''){
        $('#emailAddress').addClass('addErrorBorder');	
        $('#registerEmailError').text('Enter a valid email address');
        $('#registerEmailError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!email_regex.test(email)){    
        $('#emailAddress').addClass('addErrorBorder');	
        $('#registerEmailError').text("This email address doesn't exist");
        $('#registerEmailError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    kpPayDebug('checkpoint', { step: 'before_phone_validation' });
    if(typeof mobile == 'undefined' || mobile == ''){
        $('#phoneNumberIds').addClass('addErrorBorder');	
        $('#registerPhoneNumberError').text('Enter a valid phone number');
        $('#registerPhoneNumberError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(dial_code == ''){ 
        $('#phoneNumberIds').addClass('addErrorBorder');	
        $('#registerPhoneNumberError').text("Select your country code");
        $('#registerPhoneNumberError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    kpPayDebug('checkpoint', { step: 'before_shipping_validation' });

    if(parseData.hide_Address != 'yes'){
        if(firstLastName == null || firstLastName == "") {
            $('#first_last').addClass('addErrorBorder');
            $('#fullNameError').text('Enter your full name');
            $('#fullNameError').addClass('card_details_error');
            removeValidationError();
            return kpPayFail('shipping_full_name_missing', {
                firstLastName: firstLastName
            });
        }else if(!name_regex.test(firstLastName)){   
            $('#first_last').addClass('addErrorBorder');	
            $('#fullNameError').text('Enter a valid name. Avoid using numbers or special symbols');
            $('#fullNameError').addClass('card_details_error');
            removeValidationError();
            return kpPayFail('shipping_full_name_invalid', {
                firstLastName: firstLastName
            });
        }

        if(address1 == null || address1 == "") {
            $('#registerSearchAddress').addClass('addErrorBorder');
            $('#address1').addClass('addErrorBorder');
            $('#searchAddressError').text('Enter your shipping address');
            $('#searchAddressError').addClass('card_details_error');        
            $('#address1Error').text('Enter your shipping address');
            $('#address1Error').addClass('card_details_error');
            removeValidationError();
            return kpPayFail('shipping_address_missing', {
                address1: address1,
                searchAddress: $('#registerSearchAddress').val()
            });
        }

        if(city == null || city == "") {
            $('#registerCity').addClass('addErrorBorder');
            $('#cityError').text('Enter your city');
            $('#cityError').addClass('card_details_error');
            removeValidationError();      
            return kpPayFail('shipping_city_missing', {
                city: city
            });
        }

        if(countryManually == null || countryManually == "") {
            $('#registerCountry').addClass('addErrorBorder');
            $('#countryError').text('Select your country');
            $('#countryError').addClass('card_details_error');
            removeValidationError();        
            return kpPayFail('shipping_country_missing', {
                countryManually: countryManually
            });
        }

        if(zip_code == null || zip_code == "") {
            $('#registerZip').addClass('addErrorBorder');
            $('#zipError').text('Enter your zip code');
            $('#zipError').addClass('card_details_error');
            removeValidationError();
            return kpPayFail('shipping_zip_missing', {
                zip_code: zip_code
            });
        }
        
        if(state == null || state == "") {
            $('#registerState').addClass('addErrorBorder');
            $('#registerStateDropdown').addClass('addErrorBorder');
            $('#stateError').text('Select your state');
            $('#stateError').addClass('card_details_error');
            removeValidationError();
            return kpPayFail('shipping_state_missing', {
                type_state: type_state,
                select_state: select_state,
                resolved_state: state
            });
        }
    }

    kpPayDebug('checkpoint', { step: 'before_card_validation' });

    if(cardNumber == "" && cardValidityDate == "" && cardCVVNumber == "") {
        $('.card-information input').addClass('addErrorBorder');;                                    
        $('#cardDetailsError').text('Enter your card details');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
    
    if(cardValidityDate == "" && cardCVVNumber == "") {
        $('#cardvalidityDateId').addClass('addErrorBorder');
        $('#txtcvvNumberId').addClass('addErrorBorder');                                  
        $('#cardDetailsError').text('Enter a valid expiration date and the CVV or security code on the back of your card');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(cardNumber == null || cardNumber == "") {
		$('#card').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid card number');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(cardValidityDate == null || cardValidityDate == "") {
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('.card-info-2::before').attr('style', 'width: 0px!important');
        $('#cardDetailsError').text('Enter a valid expiration date');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!$.isNumeric(year) || !$.isNumeric(month)){
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid expiration date');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(year < currentYear){
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid expiration year');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year > currentYear) && (month > 12 || month < 1)){
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid expiration date');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year == currentYear) && (currentMonth >= month) || (month > 12 || month < 1)){
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid expiration date');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    } else if(year > maxYear){
		$('#cardvalidityDateId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter a valid expiration year');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(cardCVVNumber == null || cardCVVNumber == "") {
		$('#txtcvvNumberId').addClass('addErrorBorder');
        $('#cardDetailsError').text('Enter the CVV or security code on the back of your card');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!cvv_regex.test(cardCVVNumber)){  
		$('#txtcvvNumberId').addClass('addErrorBorder');	
        $('#cardDetailsError').text('Enter a valid CVV or security code');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
    
    if(cardHolderName == null || cardHolderName == "") {
		$('#card_holder_name').addClass('addErrorBorder');
        $('#cardHolderNameError').text("Enter a valid cardholder name");
        $('#cardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!name_regex.test(cardHolderName)) {
		$('#card_holder_name').addClass('addErrorBorder');		
        $('#cardHolderNameError').text("Enter your name exactly as it's written on your card");
        $('#cardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    var match = /(?:(^4[0-9]{0,12}(?:[0-9]{3}))|(^5[1-5][0-9]{0,14})|(^222[1-9]|^22[3-9][0-9]|^2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{0,12}|(^6(?:011|5[0-9][0-9])[0-9]{0,12})|(^3[47][0-9]{0,13})|(^3(?:0[0-5]|[68][0-9])[0-9]{0,11})|(^(?:2131|1800|35\d{3})\d{0,11}))$/.exec(cardNumber);
    var paymentCardType = '';
    if (match) {
      var types = ['visa', 'mastercard', 'mastercard', 'discover', 'amex', 'diners', 'jcb'];
      for (var i = 1; i < match.length; i++) {
        if (match[i]) {
            paymentCardType = types[i - 1];
            break;
        }
      }
    }

    kpPayDebug('checkpoint', { step: 'before_billing_validation' });

    if(parseData.hide_Address != 'yes'){
        if(is_sameBillingShipping){
            billingAddress = address1;
            billingAddress1 = address2;
            billingZip = zip_code;
            billingCity = city;
            billingState = state;
            billingCountry = countryManually;
        }else{
            if(billingAddress == null || billingAddress == "") {
                $('#billingAddress').addClass('addErrorBorder');       
                $('#billingAddressError').text('Enter your billing address');
                $('#billingAddressError').addClass('card_details_error');
                removeValidationError();
                return false;
            }

            if(billingCity == null || billingCity == "") {
                $('#billingCity').addClass('addErrorBorder');
                $('#billingCityError').text('Enter your city');
                $('#billingCityError').addClass('card_details_error');
                removeValidationError();      
                return false;
            }

            if(billingCountry == null || billingCountry == "") {
                $('#billingCountry').addClass('addErrorBorder');
                $('#billingCountryError').text('Select your country');
                $('#billingCountryError').addClass('card_details_error');
                removeValidationError();        
                return false;
            }

            if(billingZip == null || billingZip == "") {
                $('#billingZip').addClass('addErrorBorder');
                $('#billingZipError').text('Enter your zip code');
                $('#billingZipError').addClass('card_details_error');
                removeValidationError();
                return false;
            }

            if(billingState == null || billingState == "") {
                $('#billingState').addClass('addErrorBorder');
                $('#billingStateDropdown').addClass('addErrorBorder');
                $('#billingStateError').text('Select your state');
                $('#billingStateError').addClass('card_details_error');
                removeValidationError();
                return false;
            }    
        }
    }

   if(paymentCardType){
        kreplingAjax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: $.extend({
                action:'newUserSignup',
                first_last_name : firstLastName,
                delivery_address_1 : address1,
                delivery_address_2 : address2,
                card_number : cardNumber,
                card_validity_date : month+year,
                card_cvv_number : cardCVVNumber,
                state:state,
                city:city,
                zip_code:zip_code,
                countryManually:countryManually,
                is_checked:is_checked,
                paymentCardType: paymentCardType,
                cardHolderName: cardHolderName,
                is_sameBillingShipping : is_sameBillingShipping,
                companyName : companyName,
                billingAddress : billingAddress,
                billingAddress1 : billingAddress1,
                billingZip : billingZip,
                billingCountry : billingCountry,
                billingCity : billingCity,
                billingState : billingState,
                email:email,
                mobile:mobile,
                productCartName:productCartName,
                countryCode: dial_code
            }, wooCheckoutValidation.payload),
            beforeSend: function() {
                $('#btnFirstStep').val('Please wait...');
                $('#btnFirstStep').attr('disabled',true);
            },  
            success: function(response){
                if(response.card_details_status == 1){
                    let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;
                    if(response.status == 1){
                        $('#btnFirstStep').hide();
                        $('.registration_section').removeClass('hideclass');
                        $('.registration_section .common_toster').addClass('success_toster_messsage');
                        $('.registration_section .toster_message').text('Payment Successful');
                        $('#crossArrow').hide();
                        $('#rightArrow').html('');
                        if(isMobile){
                            $('#rightArrow').html('<div class="paycheck_phone">'+
                                '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                                '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                                '</svg><span> Done </span></div>');
                        }else{
                            $('#rightArrow').html('<div class="paycheck">'+
                                '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                                '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                                '</svg></div>');
                        }
                        $('#rightArrow').show();
                        setTimeout(function(){
                            $('#rightArrow').fadeOut();
                            $('#btnFirstStep').show();
                            verifyKreplingOrderAndRedirect(response);
                        },3000);                               
                    }else if(response.status == 3){
                        $('#emailAddress').addClass('addErrorBorder');	
                        $('#registerPhoneNumberError').html('');
                        $('#phoneNumberIds').removeClass('addErrorBorder');
                        $('#registerEmailError').html(`<span> This email address is already in use.</span>`);
                        $('#registerEmailError').addClass('card_details_error');
                        return false;
                    }else if(response.status == 10){
                        $('#phoneNumberIds').addClass('addErrorBorder');	
                        $('#registerEmailError').html('');
                        $('#emailAddress').removeClass('addErrorBorder');
                        $('#registerPhoneNumberError').html(`<span> This phone number is already in use.</span>`);
                        $('#registerPhoneNumberError').addClass('card_details_error');
                        return false;
                    }else{
                        $('#btnFirstStep').hide();
                        $('#rightArrow').hide();
                        $('#crossArrow').html('');
                        $('.registration_section').removeClass('hideclass');
                        $('.registration_section .common_toster').addClass('error_toster_messsage');
                        $('.registration_section .toster_message').text(response.message);
                        if(isMobile){
                            $('#crossArrow').html('<div class="cross_phone">'+
                                '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                                '<path d="M32 16L16 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path>'+
                                '<circle cx="23" cy="23" r="23" transform="matrix(-1 0 0 1 47 1)" stroke="'+parseData.kp_button_color+'" stroke-width="2"></circle>'+
                                '<path d="M16 16L32 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path></svg>'+
                                '<span> Payment not completed </span></div>');
                        }else{
                            $('#crossArrow').html('<div class="cross">Payment not completed <span class="crossIcon">&#10005;</span></div>');
                        }
                        $('#crossArrow').show();
                        setTimeout(function(){
                            $('#crossArrow').fadeOut();
                            $('#btnFirstStep').show();
                        },5000);
                    }
                }else if(response.card_details_status == 400){
                    $('.registration_section').removeClass('hideclass');
                    $('.registration_section .common_toster').addClass('error_toster_messsage');
                    $('.registration_section .toster_message').text('Fill your delivery address');
                }else{
                    $('.registration_section').removeClass('hideclass');
                    $('.registration_section .common_toster').addClass('error_toster_messsage');
                    $('.registration_section .toster_message').text('Invalid card type!');
                }
                removeValidationError();
            },
            complete: function(){
                $('#btnFirstStep').val('Pay');
                $('#btnFirstStep').attr('disabled',false);
            }
        });
    } else {
        $('#card').addClass('addErrorBorder');
        $('#cardDetailsError').text('We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.');
        $('#cardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
});

function showKreplingLoginView() {
    $('#user_email').val($('#emailAddress').val());
    $('#stepFirstId').hide();
    $('#loginDivId').show();
    $('.stepBackBtn').show();
    $('#krepling_paymentgateway #accordion').hide();
    $('.heading-1 h2').hide();
    $('.heading-1').addClass('loginHeader');
    sessionStorage.setItem('krepling_checkout_view', 'login');
}

function showKreplingGuestView() {
    $('#loginDivId').hide();
    $('#stepFourId').hide();
    $('#stepFirstId').show();
    $('.stepBackBtn').hide();
    $('#krepling_paymentgateway #accordion').show();
    $('.heading-1 h2').show();
    $('.heading-1').removeClass('loginHeader');
    sessionStorage.setItem('krepling_checkout_view', 'guest');
}

$(document).on('click', '#firstLoginId', function(e) {
    e.preventDefault();
    showKreplingLoginView();
});
// user login
$(document).on('click', '#loginSubmit', function(){
    var user_email = $('#user_email').val();
    var user_password = $('#user_password').val();
    var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if(user_email == '' || !email_regex.test(user_email)){
        $('#user_email').addClass('addErrorBorder');
        $('#userLoginEmailError').text('Enter your valid email address');
        $('#userLoginEmailError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
    if(user_password == ''){
        $('#user_password').addClass('addErrorBorder');
        $('#userLoginPasswordError').text('Enter your password');
        $('#userLoginPasswordError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
    krepling_user_login(user_email, user_password);
});

//Krepling Fast Feature
$(document).ready(function(){
    var currentView = sessionStorage.getItem('krepling_checkout_view');

    if (currentView === 'login') {
        showKreplingLoginView();
    } else {
        showKreplingGuestView();
    }


    if(sessionStorage.getItem('login_status') != 1){
        $("#payment_method_krepling").click(function(){
            $("#payment_method_krepling").prop('checked', true);

            var user_email = Cookies.get('kp_user_email');

            // Do not auto-login with stored password.
            // Only prefill the email field for convenience.
            if (user_email && $('#user_email').length) {
                $('#user_email').val(user_email);
            }
        });
    } else {
        addMerchantAddress();
    }
});

function krepling_user_login(user_email, user_password){
    var browser_name = get_browser_details();
    if(user_email != '' && user_password != '' && user_email != undefined ){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
                action:'user_login',
                user_email:user_email,
                user_password:user_password,
                browser_name: browser_name
            },
            beforeSend: function() {
                $('#loginSubmit').val('Please wait...');
                $('#loginSubmit').attr('disabled',true);
            },
            success:function(response){
                if(response.status == 1){
                    sessionStorage.setItem("login_status", 1);
                    location.reload(true);
                }else if(response.status == 404){
                    $('.loginemail_section').removeClass('hideclass');
                    $('.loginemail_section .common_toster').addClass('error_toster_messsage');
                    $('.loginemail_section .toster_message').text(response.message);
                }else if(response.status == 0){
                    $('.login_section').removeClass('hideclass');
                    $('.login_section .common_toster').addClass('warning_toster_messsage');
                    $('.login_section .toster_message').text(response.message);
                }else if(response.status == 11){
                    $('.login_section').removeClass('hideclass');
                    $('.login_section .common_toster').addClass('warning_toster_messsage');
                    $('.login_section .toster_message').text(response.message);
                }else{
                    $('.login_section').removeClass('hideclass');
                    $('.login_section .common_toster').addClass('error_toster_messsage');
                    $('.login_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function(){
                $('#loginSubmit').val('Continue');
                $('#loginSubmit').attr('disabled',false);
            }
        });
    }
}

// Add address
$(document).on('click', '#addaddressbtn', function(e){
    e.preventDefault();
    e.stopPropagation();

    var StreetAddress1 = $('#address1').val();
    var StreetAddress2 = $('#address2').val();
    var newCity = $('#registerCity').val();
    var newState = $('#registerState').val();
    var newSelectState = $('#registerStateDropdown :selected').val();
    var newZip = $('#registerZip').val();
    var newCountry = $('#registerCountry #countryManually :selected').val();
    var state = newSelectState != '' &&  newSelectState != undefined ? newSelectState : newState;
    var is_sameBillingShipping = $('#signupBillingCheckbox').is(':checked');
    var billingAddress =  $('#billingAddress').val();
    var billingAddress1 =  $('#billingAddress1').val();
    var billingZip =  $('#billingZip').val();
    var billingCountry =  $('#billingCountry #countryManually :selected').val();
    var billingCity =  $('#billingCity').val();
    var billing_text_state =  $('#billingState').val();
    var billing_select_state = $('#billingStateDropdown :selected').val();
    var billingState = billing_select_state != '' && billing_select_state != undefined ? billing_select_state : billing_text_state;


    if(StreetAddress1 == null || StreetAddress1 == "") {
        $('#address1').addClass('addErrorBorder');
        $('#deliveryAddressError').text('Enter your shipping address');
        $('#deliveryAddressError').addClass('card_details_error');        
        removeValidationError();
        return false;
    }

    if(newCity == null || newCity == "") {
        $('#registerCity').addClass('addErrorBorder');
        $('#cityError').text('Enter your city');
        $('#cityError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }

    if(newCountry == null || newCountry == "") {
        $('#registerCountry').addClass('addErrorBorder');
        $('#countryError').text('Select your country');
        $('#countryError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }

    if(newZip == null || newZip == "") {
        $('#registerZip').addClass('addErrorBorder');
        $('#zipError').text('Enter your zip code');
        $('#zipError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
 
    if(state == null || state == "") {
        $('#registerState').addClass('addErrorBorder');
        $('#registerStateDropdown').addClass('addErrorBorder');
        $('#stateError').text('Select your state');
        $('#stateError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
    
    if(is_sameBillingShipping){      
        billingAddress = StreetAddress1;
        billingAddress1 = StreetAddress2;
        billingZip = newZip;
        billingCity = newCity;
        billingState = state;
        billingCountry = newCountry;
    }else{
        if(billingAddress == null || billingAddress == "") {
            $('#billingAddress').addClass('addErrorBorder');       
            $('#billingAddressError').text('Enter your billing address');
            $('#billingAddressError').addClass('card_details_error');
            removeValidationError();
            return false;
        }

        if(billingCity == null || billingCity == "") {
            $('#billingCity').addClass('addErrorBorder');
            $('#billingCityError').text('Enter your city');
            $('#billingCityError').addClass('card_details_error');
            removeValidationError();      
            return false;
        }
        
        if(billingCountry == null || billingCountry == "") {
            $('#billingCountry').addClass('addErrorBorder');
            $('#billingCountryError').text('Select your country');
            $('#billingCountryError').addClass('card_details_error');
            removeValidationError();        
            return false;
        }

        if(billingZip == null || billingZip == "") {
            $('#billingZip').addClass('addErrorBorder');
            $('#billingZipError').text('Enter your zip code');
            $('#billingZipError').addClass('card_details_error');
            removeValidationError();
            return false;
        }

        if(billingState == null || billingState == "") {
            $('#billingState').addClass('addErrorBorder');
            $('#billingStateDropdown').addClass('addErrorBorder');
            $('#billingStateError').text('Select your state');
            $('#billingStateError').addClass('card_details_error');
            removeValidationError();
            return false;
        }    
    }

    if(StreetAddress1!=''){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
                action:'addAddress',
                StreetAddress1:StreetAddress1,
                StreetAddress2:StreetAddress2,
                newCity:newCity,
                newState:state,
                newZip:newZip,
                newCountry:newCountry,
                is_sameBillingShipping : is_sameBillingShipping,
                billingAddress : billingAddress,
                billingAddress1 : billingAddress1,
                billingZip : billingZip,
                billingCountry : billingCountry,
                billingCity : billingCity,
                billingState : billingState,
                isDefaultStatus : 0
            },
            beforeSend: function() {
                $('#addaddressbtn').text('Wait...');
                $('#addaddressbtn').attr('disabled',true);
            },   
            success: function(response){
                if(response.status==1){
                    $('#AddAddressPopup').modal('hide');
                    location.reload();
                }else{
                    $('.add_address_section').removeClass('hideclass');
                    $('.add_address_section .common_toster').addClass('error_toster_messsage');
                    $('.add_address_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function(){
                $('#addaddressbtn').text('Add');
                $('#addaddressbtn').attr('disabled',false); 
            }
        });
    }
});

//Remove Address
function removeAddress(addressID, userID, streetAddress) {
    if(addressID!=''){
        sessionStorage.setItem('delete-address-status', true);
        $('#deleted_address_popup').show();
        if(streetAddress.length > 25){
            streetAddress = streetAddress.substring(0,25) +'...';
        }
        $('#delete_address_text').text('Deleted "'+streetAddress+'"');
        setTimeout(function(){
            if(sessionStorage.getItem('delete-address-status') == 'true'){
                $.ajax({
                    url: parseData.base_url+'controller.php',
                    type:'POST',
                    dataType:'json',
                    data: {
                        action:'removeAddress',
                        addressID:addressID,
                        userID:userID
                    },   
                    success: function(response){
                        if(response.status == 200){
                            sessionStorage.removeItem('delete-address-status');
                            location.reload();
                        }else{
                            $('.address_section').removeClass('hideclass');
                            $('.address_section .common_toster').addClass('error_toster_messsage');
                            $('.address_section .toster_message').text(response.message);
                        }
                        removeValidationError();
                    }
                });
            }
            $('#deleted_address_popup').hide();
        }, 5000); 
    }
}

function updateAddress(addressID, userID) {    
    var StreetAddress1 = $('#newstreetaddress_line1_'+addressID).val();
    var StreetAddress2 = $('#newstreetaddress_line2_'+addressID).val();
    var newCity = $('#ucity_'+addressID).val();
    var newZip = $('#uzip_'+addressID).val();
    var newCountry = $('#update_country_'+addressID+' :selected').val();
    var newState = $('#update_state_'+addressID+' :selected').val();
    var newSelectState = $('#update_stateDropdown_'+addressID).val();
    var state = newState != '' &&  newState != undefined ? newState : newSelectState;    
    var is_sameBillingShipping = $('#sameBillingShippingUpdate_'+addressID).is(':checked');
    var billingAddress =  $('#update_billingAddress_'+addressID).val();
    var billingAddress1 =  $('#update_billingAddress1_'+addressID).val();
    var billingZip =  $('#update_billingZip_'+addressID).val();
    var billingCountry =  $('#update_billingCountry_'+addressID+' #countryManually :selected').val();
    var billingCity =  $('#update_billingCity_'+addressID).val();
    var billing_select_state =  $('#update_billingState_'+addressID+' :selected').val();
    var billing_text_state = $('#update_billingStateDropdown_'+addressID).val();
    var billingState = billing_select_state != '' && billing_select_state != undefined ? billing_select_state : billing_text_state;   

    if(StreetAddress1 == null || StreetAddress1 == "") {
        $('#newstreetaddress_line1_'+addressID).addClass('addErrorBorder');
        $('#updateDeliveryAddressError_'+addressID).text('Enter your shipping address');
        $('#updateDeliveryAddressError_'+addressID).addClass('card_details_error');        
        removeValidationError();
        return false;
    }
    
    if(newCity == null || newCity == "") {
        $('#ucity_'+addressID).addClass('addErrorBorder');
        $('#updateDeliveryCityError_'+addressID).text('Enter your city');
        $('#updateDeliveryCityError_'+addressID).addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(newCountry == null || newCountry == "") {
        $('#update_country_'+addressID).addClass('addErrorBorder');
        $('#updateDeliveryCountryError_'+addressID).text('Select your country');
        $('#updateDeliveryCountryError_'+addressID).addClass('card_details_error');
        removeValidationError();      
        return false;
    }

    if(newZip == null || newZip == "") {
        $('#uzip_'+addressID).addClass('addErrorBorder');
        $('#updateDeliveryZipError_'+addressID).text('Enter your zip code');
        $('#updateDeliveryZipError_'+addressID).addClass('card_details_error');
        removeValidationError();
        return false;
    }
      
    if(state == null || state == "") {
        $('#edit_state_'+addressID).addClass('addErrorBorder');
        $('#updateDeliveryStateError_'+addressID).text('Select your state');
        $('#updateDeliveryStateError_'+addressID).addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(is_sameBillingShipping){                   // when billing address same as shipping
        billingAddress = StreetAddress1;
        billingAddress1 = StreetAddress2;
        billingZip = newZip;
        billingCity = newCity;
        billingState = state;
        billingCountry = newCountry;
    }else{
        if(billingAddress == null || billingAddress == "") {
            $('#update_billingAddress_'+addressID).addClass('addErrorBorder');       
            $('#updateBillingAddressError_'+addressID).text('Enter your billing address');
            $('#updateBillingAddressError_'+addressID).addClass('card_details_error');
            removeValidationError();
            return false;
        }

        if(billingCity == null || billingCity == "") {
            $('#update_billingCity_'+addressID).addClass('addErrorBorder');
            $('#updateBillingCityError_'+addressID).text('Enter your zip code');
            $('#updateBillingCityError_'+addressID).addClass('card_details_error');
            removeValidationError();
            return false;
        }

        if(billingCountry == null || billingCountry == "") {
            $('#update_billingCountry_'+addressID).addClass('addErrorBorder');
            $('#updateBillingCountryError_'+addressID).text('Select your country');
            $('#updateBillingCountryError_'+addressID).addClass('card_details_error');
            removeValidationError();        
            return false;
        }

        if(billingZip == null || billingZip == "") {
            $('#update_billingZip_'+addressID).addClass('addErrorBorder');
            $('#updateBillingZipError_'+addressID).text('Enter your zip code');
            $('#updateBillingZipError_'+addressID).addClass('card_details_error');
            removeValidationError();
            return false;
        }

        if(billingState == null || billingState == "") {
            $('#edit_billingState_'+addressID).addClass('addErrorBorder');
            $('#updateBillingStateError_'+addressID).text('Select your state');
            $('#updateBillingStateError_'+addressID).addClass('card_details_error');
            removeValidationError();
            return false;
        }    
    }

    if(StreetAddress1 != ''){
        var edit_address_id = '#editAddressPopup_'+addressID;
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
                action:'updateAddress',
                newstreetaddress1 : StreetAddress1,
                newstreetaddress2 : StreetAddress2,
                addressID : addressID,
                userID : userID,
                ucity : newCity,
                ucountry : newCountry,
                ustate : state,
                uzip : newZip,
                is_sameBillingShipping : is_sameBillingShipping,
                billingAddress : billingAddress,
                billingAddress1 : billingAddress1,
                billingZip : billingZip,
                billingCountry : billingCountry,
                billingCity : billingCity,
                billingState : billingState
            },
            beforeSend: function () {
                $(edit_address_id+' #updateAddressBtn').text('Wait...');
                $(edit_address_id+' #updateAddressBtn').attr('disabled',true);
            },
            success: function(response){
                if(response.status == 200){
                    $(edit_address_id).hide();
                    $('.address_section').removeClass('hideclass');
                    $('.address_section .common_toster').addClass('success_toster_messsage');
                    $('.address_section .toster_message').text(response.message);
                    setTimeout(()=>{
                        location.reload(true);
                    }, 2000);
                }else{
                    $('.update_address_section').removeClass('hideclass');
                    $('.update_address_section .common_toster').addClass('error_toster_messsage');
                    $('.update_address_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function () {
                $(edit_address_id+' #updateAddressBtn').text('Update');
                $(edit_address_id+' #updateAddressBtn').attr('disabled',false);
            }
        });
    }
}

//Set Default Address
function setDefaultAddress(addressID,userID,address) {
    if(address!=''){    
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action:'setDefaultAddress',
                addressID:addressID,
                userID:userID,
                address:address
            },   
            success: function(response){
                if(response.status == 200){
                    location.reload(true);
                }else{
                    $('.address_section').removeClass('hideclass');
                    $('.address_section .common_toster').addClass('error_toster_messsage');
                    $('.address_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }
}

// Call add new payment card API
$(document).on('click', '#btnAddCard', function(e){
    e.preventDefault();
    e.stopPropagation();

    var cardFullName = $('#cardFirstName').val();
    var addCardNumber = $('#addCardNumber').val().replace(/\s+/g, '');
    var addCardExpiry = $('#addCardExpiry').val();
    var addCardCvv = $('#addCardCvv').val();
    var currentMonth = new Date().getMonth() + 1;
    var currentYear = new Date().getFullYear().toString().slice(-2);
    var month = addCardExpiry.toLocaleString().split('/')[0];
    var year = addCardExpiry.toLocaleString().split('/')[1];
    var cvv_regex = /^[0-9]{3,4}$/;
    var name_regex = /^[a-zA-Z]+ [a-zA-Z]+$/;
    var maxYear = parseInt(currentYear) + 25;

    if(addCardNumber == "" && addCardExpiry == "" && addCardCvv == "") {
        $('.card-information input').addClass('addErrorBorder');;                                    
        $('#addCardDetailsError').text('Enter your card details');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
    
    if(addCardExpiry == "" && addCardCvv == "") {
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardCvv').addClass('addErrorBorder');                                     
        $('#addCardDetailsError').text('Enter a valid expiration date and the CVV or security code on the back of your card');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(addCardNumber == null || addCardNumber == "") {
        $('#addCardNumber').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid card number');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(addCardNumber.length < 13) {
        $('#addCardNumber').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid card number');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(addCardExpiry == null || addCardExpiry == "") {
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration date');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(addCardExpiry.length < 5) {
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration date');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!$.isNumeric(year) || !$.isNumeric(month)){
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration date');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(year < currentYear){
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration year');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year > currentYear) && (month > 12 || month < 1)){
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration date');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year == currentYear) && (currentMonth >= month) || (month > 12 || month < 1)){
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration date');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    } else if(year > maxYear){
        $('#addCardExpiry').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid expiration year');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(addCardCvv == null || addCardCvv == "") {
        $('#addCardCvv').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter the CVV or security code on the back of your card');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!cvv_regex.test(addCardCvv)){    
        $('#addCardCvv').addClass('addErrorBorder');
        $('#addCardDetailsError').text('Enter a valid CVV or security code');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(cardFullName == null || cardFullName == "") {
        $('#cardFirstName').addClass('addErrorBorder');
        $('#addCardHolderNameError').text("Enter a valid cardholder name");
        $('#addCardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!name_regex.test(cardFullName)){
        $('#cardFirstName').addClass('addErrorBorder');		
        $('#addCardHolderNameError').text("Enter your name exactly as it's written on your card");
        $('#addCardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    var match = /(?:(^4[0-9]{0,12}(?:[0-9]{3}))|(^5[1-5][0-9]{0,14})|(^222[1-9]|^22[3-9][0-9]|^2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{0,12}|(^6(?:011|5[0-9][0-9])[0-9]{0,12})|(^3[47][0-9]{0,13})|(^3(?:0[0-5]|[68][0-9])[0-9]{0,11})|(^(?:2131|1800|35\d{3})\d{0,11}))$/.exec(addCardNumber);
    var paymentCardType = '';
    if (match) {
      var types = ['visa', 'mastercard', 'mastercard', 'discover', 'amex', 'diners', 'jcb'];
      for (var i = 1; i < match.length; i++) {
        if (match[i]) {
            paymentCardType = types[i - 1];
          break;
        }
      }
    } 

    var paymentCardType = '';
    if (match) {
      var types = ['visa', 'mastercard', 'mastercard', 'discover', 'amex', 'diners', 'jcb'];
      for (var i = 1; i < match.length; i++) {
        if (match[i]) {
            paymentCardType = types[i - 1];
            break;
        }
      }
    }

    if(paymentCardType){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
            action:'addPaymentCard',
            cardFirstName:cardFullName,
            addCardNumber:addCardNumber,
            addCardExpiry:addCardExpiry,
            addCardCvv:addCardCvv,
            paymentCardType : paymentCardType
            },
            beforeSend: function() {
                $('#btnAddCard').text('Wait...');
                $('#btnAddCard').prop('disabled', true);
            }, 
            success: function(response){
                if(response.status == 1){
                    $('#addcard').modal('hide');
                    $('#stepFourId').show();
                    setTimeout(function(){
                        location.reload();
                    },1000);
                }else{
                    $('.addcard_section').removeClass('hideclass');
                    $('.addcard_section .common_toster').addClass('error_toster_messsage');
                    $('.addcard_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function(){
                $('#btnAddCard').text('Add');
                $('#btnAddCard').prop('disabled', false); 
            }
        });
    } else {
        $('#addCardNumber').addClass('addErrorBorder');
        $('#addCardDetailsError').text('We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.');
        $('#addCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
});


// Delete payment card 
function deleteCard(card_id, user_id, last_four_digits){
    if(card_id != ''){
        sessionStorage.setItem('delete-card-status', true);
        $('#deleted_card_popup').show();
        $('#delete_card_text').text('Deleted card ending in '+last_four_digits);
        setTimeout(function(){
            if(sessionStorage.getItem('delete-card-status') == 'true'){
                $.ajax({
                    url: parseData.base_url+'controller.php',
                    type:"POST",
                    dataType:'json',
                    data: {
                    action:'deletCard',
                        card_id: card_id,
                        user_id: user_id
                    },   
                    success: function(response){
                        if(response.status == 1){
                            sessionStorage.removeItem('delete-card-status');
                            location.reload(true);
                        }else{
                            $('.paymentCard_section').removeClass('hideclass');
                            $('.paymentCard_section .common_toster').addClass('error_toster_messsage');
                            $('.paymentCard_section .toster_message').text(response.message);
                        }
                        removeValidationError();
                    }
                });
            }
            $('#deleted_card_popup').hide();
        }, 5000);
    }
}

//Payment
$(document).on('click', '#complete_payment', function(e){
    var txtCVVNumberId = $('#txtCVVNumberId').val();

    if ($.trim(txtCVVNumberId).length < 3) {
        e.preventDefault();
        $('.cardcvv').addClass('addErrorBorder');
        $('#cardCvvError').text('Fill in your security code');
        $('#cardCvvError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    var krpAmount = $('#krpAmount').val();
    var cardName = $('#cardName').val();
    var cardIdNum = $('#cardIdNum').val();
    var cardExpDate = $('#cardExpDate').val();
    var currencySymbol = $('#current_currency_symbol').val();
    var currencyName = $('#current_currency_name').val();
    var selectedAddressId = $('input[type="radio"][name="radiosButton"]:checked').val();
    var wooCheckoutValidation = validateKreplingWooCheckoutFields('.payment_section');

    if (!wooCheckoutValidation.valid) {
        e.preventDefault();
        removeValidationError();
        return false;
    }
                
    if(selectedAddressId == undefined){
        $('.payment_section').removeClass('hideclass');
        $('.payment_section .common_toster').addClass('error_toster_messsage');
        $('.payment_section .toster_message').text("Select the delivery address to make payment");
        removeValidationError();
        return false;
    }
    if(txtCVVNumberId != '' && (txtCVVNumberId.length == 3 || txtCVVNumberId.length == 4)){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: $.extend({
                action:'pay',
                krpAmount:krpAmount,
                cardName:cardName,
                txtCVVNumberId:txtCVVNumberId,
                cardExpDate:cardExpDate,
                cardIdNum:cardIdNum,
                currencySymbol: currencySymbol,
                currencyName: currencyName,
                addressId : selectedAddressId
            }, wooCheckoutValidation.payload),
            beforeSend: function() {
                kpPayDebug('ajax_before_send', {
                    action: 'newUserSignup'
                });
                $('#btnFirstStep').val('Please wait...');
                $('#btnFirstStep').attr('disabled',true);
            },  
            success: function(response){ 
                kpPayDebug('ajax_success', response);
                $('#complete_payment').hide();
                $('.cardcvv').hide();
                let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;
                if(response.status == 1){
                    $('.payment_section').removeClass('hideclass');
                    $('.payment_section .common_toster').addClass('success_toster_messsage');
                    $('.payment_section .toster_message').text('Payment Successful');
                    $('#crossArrow').hide();
                    $('#rightArrow').html('');
                    if(isMobile){
                        $('#rightArrow').html('<div class="paycheck_phone">'+
                            '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                            '</svg><span> Done </span></div>');
                    }else{
                        $('#rightArrow').html('<div class="paycheck">'+
                            '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                            '</svg></div>');
                    }
                    $('#rightArrow').show();
                    setTimeout(function(){
                        $('#rightArrow').fadeOut();
                        $('#complete_payment').show();
                        $('.cardcvv').show();
                        verifyKreplingOrderAndRedirect(response);
                    },5000);
                }else{
                    $('#rightArrow').hide();
                    $('#crossArrow').html('');
                    $('.payment_section').removeClass('hideclass');
                    $('.payment_section .common_toster').addClass('error_toster_messsage');
                    $('.payment_section .toster_message').text(response.message);
                    if(isMobile){
                        $('#crossArrow').html('<div class="cross_phone">'+
                            '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M32 16L16 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path>'+
                            '<circle cx="23" cy="23" r="23" transform="matrix(-1 0 0 1 47 1)" stroke="'+parseData.kp_button_color+'" stroke-width="2"></circle>'+
                            '<path d="M16 16L32 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path></svg>'+
                            '<span> Payment not completed </span></div>');
                    }else{
                        $('#crossArrow').html('<div class="cross">Payment not completed <span class="crossIcon">&#10005;</span></div>');
                    }
                    $('#crossArrow').show();
                    setTimeout(function(){
                        $('#crossArrow').fadeOut();
                        $('#complete_payment').show();
                        $('.cardcvv').show();
                    },5000);                
                }
                removeValidationError();
            },
            error: function(xhr, status, errorThrown){
                kpPayDebug('ajax_error', {
                    status: status,
                    errorThrown: errorThrown,
                    httpStatus: xhr && xhr.status,
                    responseText: xhr && xhr.responseText
                });
            },
            complete: function(){
                kpPayDebug('ajax_complete');
                $('#btnFirstStep').val('Pay');
                $('#btnFirstStep').attr('disabled',false);
            }
        });
    }else{
        $('.cardcvv ').addClass('addErrorBorder');
        $('#cardCvvError').text('Fill in your security code');
        $('#cardCvvError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$('.btn_change_phonenumber, #sendChangePhoneOtpAgain').on('click',function(){;
    var mobile_number = $('#otp_phoneNumber').val().replace(/[_\s]/g, '');
    var dialCode = $('.iti__selected-dial-code').text();
    var phone_number = dialCode +' '+mobile_number;
    var mobile_regex = /^\d{6,14}$/;
    if(mobile_number !== '' && mobile_regex.test(mobile_number)){
        if(dialCode !== ''){
            $.ajax({
                url: parseData.base_url+'controller.php',
                type:'POST',
                dataType:'json',
                data: {
                    action:'changePhoneNumber',
                    phone:mobile_number,
                    countryCode:dialCode
                },
                beforeSend: function() {
                    $('.btn_change_phonenumber').val('Please wait...');
                    $('.btn_change_phonenumber').attr('disabled',true);
                },
                success: function(response){
                    if(response.status == 1){
                        sendAgainTimer();
                        $('.change_phonenumber_otp_page').show();
                        $('.change_phonenumber_page').hide();
                        $('.sendphoneotp_section').removeClass('hideclass');
                        $('.sendphoneotp_section .common_toster').addClass('success_toster_messsage');
                        $('.sendphoneotp_section .toster_message').text(response.message);
                        $('#display_otp_phone').text(phone_number);
                    }else if(response.status == 0){
                        $('.changephonenumber_section').removeClass('hideclass');
                        $('.changephonenumber_section .common_toster').addClass('error_toster_messsage');
                        $('.changephonenumber_section .toster_message').text(response.message);
                    }else{
                        $('.sendphoneotp_section').removeClass('hideclass');
                        $('.sendphoneotp_section .common_toster').addClass('error_toster_messsage');
                        $('.sendphoneotp_section .toster_message').text(response.message);
                    }
                    removeValidationError();
                },
                complete: function(){
                    $('.btn_change_phonenumber').val('Continue');
                    $('.btn_change_phonenumber').attr('disabled',false);
                }
            });
        }else{
            $('#otp_phoneNumber').addClass('addErrorBorder');
            $('#updatePhoneNumberError').text('Enter your country code');
            $('#updatePhoneNumberError').addClass('card_details_error');
            removeValidationError();      
            return false;
        }
    }else{
        $('#otp_phoneNumber').addClass('addErrorBorder');
        $('#updatePhoneNumberError').text('Enter your phone number');
        $('#updatePhoneNumberError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$('.btn_change_emailaddress, #sendChangeEmailOtpAgain').on('click',function(){
    var email_id = $('#otp_email').val();
    var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if(email_id !== '' && email_regex.test(email_id)){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action:'changeEmailAddress',
                email:email_id
            },
            beforeSend: function() {
                $('.btn_change_emailaddress').val('Please wait...');
                $('.btn_change_emailaddress').attr('disabled',true);
            },
            success: function(response){
                if(response.status == 1){
                    sendAgainTimer();
                    $('.change_emailaddress_otp_page').show();
                    $('.change_emailaddress_page').hide();
                    $('.sendemailotp_section').removeClass('hideclass');
                    $('.sendemailotp_section .common_toster').addClass('success_toster_messsage');
                    $('.sendemailotp_section .toster_message').text(response.message);
                    $('#display_otp_email').text(email_id);
                }else if(response.status == 0){
                    $('.changeemail_section').removeClass('hideclass');
                    $('.changeemail_section .common_toster').addClass('error_toster_messsage');
                    $('.changeemail_section .toster_message').text(response.message);
                }else{
                    $('.sendemailotp_section').removeClass('hideclass');
                    $('.sendemailotp_section .common_toster').addClass('error_toster_messsage');
                    $('.sendemailotp_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function(){
                $('.btn_change_emailaddress').val('Continue');
                $('.btn_change_emailaddress').attr('disabled',false); 
            }
        });
    }else{
        $('#otp_email').addClass('addErrorBorder');
        $('#updateEmailAddressError').text('Enter your valid email address');
        $('#updateEmailAddressError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$('#verify_email_address_otp').keyup(function(){
    var email_id = $('#display_otp_email').text();
    var otpValue= Array.from(document.querySelector(".SMSArea-code").querySelectorAll("input[type=tel]")).map(x => x.value);
    var otp = otpValue.toString().split(",").join("");
    if(otp.length == 6){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action : 'verifyOtpEmailAddress',
                otp : otp,
                email:email_id
            },  
            success: function(response){
                if(response.status == 1){
                    $('.change_emailaddress_otp_page').hide();
                    $('.wallet_authentication_page').show();
                    $('.verifyemailotp_section').removeClass('hideclass');
                    $('.verifyemailotp_section .common_toster').addClass('success_toster_messsage');
                    $('.verifyemailotp_section .toster_message').text(response.message);
                    setTimeout(function(){
                        sessionStorage.clear();
                        location.reload(true);
                    },2000);
                }else{
                    $('.sendemailotp_section').removeClass('hideclass');
                    $('.sendemailotp_section .common_toster').addClass('error_toster_messsage');
                    $('.sendemailotp_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }else{
        $('.SMSArea-code input[type=tel]').addClass('addErrorBorder');
        $('#verifyEmailAddressOtpError').text('Enter 6-digit code');
        $('#verifyEmailAddressOtpError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$('#verify_phone_number_otp').keyup(function(){
    var phone = $('#display_otp_phone').text();
    var splitNumber = phone.split(' ');
    var phone_number = splitNumber[1].replace(/\D/g,'');
    var otpValue= Array.from(document.querySelector(".phone-verification-code").querySelectorAll("input[type=tel]")).map(x => x.value);
    var otp = otpValue.toString().split(",").join("");
    if(otp.length == 6){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action : 'verifyOtpPhoneNumber',
                otp : otp,
                phone : phone_number,
                countryCode: splitNumber[0]
            },  
            success: function(response){
                if(response.status == 1){
                    $('.change_phonenumber_otp_page').hide();
                    $('.wallet_authentication_page').show();
                    $('.verifyphoneotp_section').removeClass('hideclass');
                    $('.verifyphoneotp_section .common_toster').addClass('success_toster_messsage');
                    $('.verifyphoneotp_section .toster_message').text(response.message);
                    setTimeout(function(){
                        location.reload(true);
                    },2000);                    
                }else{
                    $('.sendphoneotp_section').removeClass('hideclass');
                    $('.sendphoneotp_section .common_toster').addClass('error_toster_messsage');
                    $('.sendphoneotp_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }else{
        $('.phone-verification-code input[type=tel]').addClass('addErrorBorder');
        $('#verifyPhoneNumberOtpError').text('Enter 6-digit code');
        $('#verifyPhoneNumberOtpError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$(document).on('click', '.change_password_btn', function(){
    var old_pass = $('#user_password').val();
    var new_pass = $('#newPassword').val();
    var re_pass = $('#repassword').val();
    var new_pass_regex = /^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%&*]).{6,}$/;

    if(old_pass !== ''){
        if(new_pass == ''){
            $('#newPassword').addClass('addErrorBorder');
            $('#userNewPasswordError').text('Enter a new password');
            $('#userNewPasswordError').addClass('card_details_error');
            removeValidationError();      
            return false;
        }else if(!new_pass_regex.test(new_pass)){
            $('#newPassword').addClass('addErrorBorder');
            $('#userNewPasswordError').text('Password contains atleast 6 characters, which have special character(!@#$%&*) and alphanumeric.');
            $('#userNewPasswordError').addClass('card_details_error');
            removeValidationError();      
            return false;
        }else if(new_pass !== re_pass){
            $('#repassword').addClass('addErrorBorder');
            $('#userRetypePasswordError').text('The new and confirmation password doesn\'t match');
            $('#userRetypePasswordError').addClass('card_details_error');
            removeValidationError();      
            return false;
        }else{
            $.ajax({
                url: parseData.base_url+'controller.php',
                type:"POST",
                dataType:'json',
                data: {
                    action:'change_user_password',
                    OldPassword: old_pass,
                    NewPassword: new_pass,
                    ReNewPassword: re_pass,
                },
                beforeSend: function() {
                    $('.change_password_btn').val('Please wait...');
                    $('.change_password_btn').prop('disabled', true);
                },
                success:function(response){
                    if(response.status == 1){
                        $('.wallet_changePassword_page').hide();
                        $('#stepFourId').show();
                        $('.signin_section').removeClass('hideclass');
                        $('.signin_section .common_toster').addClass('success_toster_messsage');
                        $('.signin_section .toster_message').text(response.message);
                        setTimeout(function(){
                            sessionStorage.clear();
                            location.reload(true);
                        },2000);
                    }else{
                        $('.changepassword_section').removeClass('hideclass');
                        $('.changepassword_section .common_toster').addClass('error_toster_messsage');
                        $('.changepassword_section .toster_message').text(response.message);
                    }
                    removeValidationError();
                },
                complete: function(){
                    $('.change_password_btn').val('Continue');
                    $('.change_password_btn').prop('disabled', false); 
                }
            });
        }
    } else {
        $('#user_password').addClass('addErrorBorder');
        $('#userOldPasswordError').text('Enter your old password');
        $('#userOldPasswordError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

function forgotPassword(email = null){
    if(email == null){
        var user_email = $('#user_email').val();
        var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if(typeof user_email == 'undefined' || user_email == ''){
            $('#user_email').addClass('addErrorBorder');
            $('#userLoginEmailError').text('Enter your email address then click on forgot password link');
            $('#userLoginEmailError').addClass('card_details_error');
            removeValidationError();
            return false;
        } else if(!email_regex.test(user_email)){
            $('#user_email').addClass('addErrorBorder');
            $('#userLoginEmailError').text('Enter your valid email address then click on forgot password link');
            $('#userLoginEmailError').addClass('card_details_error');
            removeValidationError();
            return false;
        }    
    }
    var email_address = email == null ? user_email : email;

    if(email_address != ''){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action:'forgotAccountPassword',
                email:email_address
            },
            success: function(response){
                if(response.status == 200){
                    $('.forget-alert').show();
                    $('.login_section').removeClass('hideclass');
                    $('.login_section .common_toster').addClass('error_toster_messsage');
                    $('.login_section .toster_message').text(response.message);
                    setTimeout(function(){
                        sessionStorage.clear();
                        location.reload(true);
                    },2000);
                }else if(response.status == 404){
                    $('.login_section').removeClass('hideclass');
                    $('.login_section .common_toster').addClass('warning_toster_messsage');
                    $('.login_section .toster_message').text(response.message);
                }else{
                    $('.loginemail_section').removeClass('hideclass');
                    $('.loginemail_section .common_toster').addClass('error_toster_messsage');
                    $('.loginemail_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }
}

$('#resend_sms, #resendSmsOtpAgain').click(function(){
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:'POST',
        dataType:'json',
        data: {
            action:'resend_smsOtp_action'
        },
        beforeSend: function() {
            $('body').addClass('loading');
        }, 
        success: function(response){
            if(response.status == 2){
                sendAgainTimer();
                $('.verify_resend_otp_page').show();
                $('.wallet_authentication_page').hide();
                $('#display_resend_otp_field').text(response.resendOtpData);
                $('.resendsmsotp_section').removeClass('hideclass');
                $('.resendsmsotp_section .common_toster').addClass('success_toster_messsage');
                $('.resendsmsotp_section .toster_message').text(response.message);
            }else{
                $('.verifyphoneotp_section').removeClass('hideclass');
                $('.verifyphoneotp_section .common_toster').addClass('error_toster_messsage');
                $('.verifyphoneotp_section .toster_message').text(response.message);
            }
            removeValidationError();
        },
        complete: function(){
            setTimeout(function(){
                $('body').removeClass('loading');
            }, 2000);
        }
    });
});

$('#verify_resendOtp_phoneNumber').keyup(function(){
    var signup_otp = Array.from(document.querySelector(".resendPhone-verification-code").querySelectorAll("input[type=tel]")).map(x => x.value);
    var otp = signup_otp.toString().split(",").join("");
    if(otp.length == 6){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
            action:'verifyOTP',
            otp:otp
            },   
            success: function(response){
                if(response.status == 1){
                    $('.verify_resend_otp_page').hide();
                    $('.wallet_authentication_page').show();
                    $('.verifyphoneotp_section').removeClass('hideclass');
                    $('.verifyphoneotp_section .common_toster').addClass('success_toster_messsage');
                    $('.verifyphoneotp_section .toster_message').text(response.message);
                    setTimeout(function(){
                        location.reload();
                    }, 2000);
                }else{
                    $('.resendsmsotp_section').removeClass('hideclass');
                    $('.resendsmsotp_section .common_toster').addClass('error_toster_messsage');
                    $('.resendsmsotp_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }else{
        $('.resendPhone-verification-code input[type=tel]').addClass('addErrorBorder');
        $('#verifyResendPhoneNumberOtpError').text('Enter 6-digit code');
        $('#verifyResendPhoneNumberOtpError').addClass('card_details_error');
        removeValidationError();      
        return false;
    }
});

$(document).on('click', '.logout_btn', function(){
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:'POST',
        dataType:'json',
        data: {
            action:'userLogout'
        },
        beforeSend: function() {
            $('.logout_btn').val('Please wait...');
            $('.logout_btn').prop('disabled', true); 
        }, 
        success: function(response){
            if(response.status==1){
                $('.logout_section').removeClass('hideclass');
                $('.logout_section .common_toster').addClass('success_toster_messsage');
                $('.logout_section .toster_message').text(response.message);
                setTimeout(function(){
                    sessionStorage.clear();
                    location.reload(true);
                },2000);
            }else{
                $('.logout_section').removeClass('hideclass');
                $('.logout_section .common_toster').addClass('error_toster_messsage');
                $('.logout_section .toster_message').text(response.message);
            }
            removeValidationError();
        },
        complete: function(){
            $('.logout_btn').val('Confirm');
            $('.logout_btn').prop('disabled', false); 
        }
    });
});

jQuery(function ($) {
    $.ajax({
        url: parseData.base_url + 'controller.php',
        type: 'POST',
        dataType: 'json',
        data: $.param({
            action: 'typeCurrencyValue'
        })
    });
});

//get country
jQuery(function ($) {
    var data = '';
    $.ajax({
        url: parseData.base_url + 'controller.php',
        type: 'POST',
        dataType: 'json',
        data: $.param({
            action: 'reqCountry'
        }),
        success: function(response){
            data += '<select class="selectCountry" id="countryManually" name="registerCountry">';

            $.each(response.country, function(key, value) {
                if(response.defaultCountry == value.countryCode){
                    getZipcodePlaceholder(value.countryCode);
                    getDeliveryStates(value.countryCode, response.defaultState);
                    getBillingStates(value.countryCode, response.defaultState);
                    getZipcodePlaceholder(value.countryCode, 'billing');
                    updateLabelText(value.countryCode, 'shipping');
                    updateLabelText(value.countryCode, 'billing');
                    data += '<option value="'+value.countryCode+'" selected>'+value.countryName+'</option>';
                }else{
                    data += '<option value="'+value.countryCode+'">'+value.countryName+'</option>';
                }
            });

            $('#registerCountry').html(data);
            $('#billingCountry').html(data);
            $('.update_country').html(data);
            $('.update_billingCountry').html(data);
        }
    });
});

$(document).on('click', '.backbutton, .backbutton .back', function(e){
    e.preventDefault();
    showKreplingGuestView();
});

/* delivery sddress */
$(document).on('click', '#cantFind-button', function () {
    $('#address-form').css("display", "none");
    $('#address-form2').css({
        "display": "block",
        "margin-bottom": "21px"
    });
    $('#cantFind-button').css("display", "none");
    $('#searchAddress').css("display", "block");
    
    $('#address1').val($('#registerSearchAddress').val());
    $('#address2').val($('#registerUnitAddress').val());
});

$(document).on('click', '#searchAddress', function () {
    $('#address-form2').css("display", "none");
    $('#address-form').css("display", "block");
    $('#cantFind-button').css("display", "block");
    $('#searchAddress').css("display", "none");
});

function getCounterNumber(id, masking) {
    var nameID = "customerFullNameId1"+id;
    var customerName = $('#'+nameID).html();
      $('#cardName').val(customerName); 
  
    var cardPaynumber = "spanCardNumberIduser"+id;
    var cusCardNum = $('#'+cardPaynumber).val();
    $('#payCardNumber').val(cusCardNum);

    var cardNumberId = "spanCardId"+id;
    var cardNumID = $('#'+cardNumberId).val();
    $('#cardIdNum').val(cardNumID);

    var cardExpiry = "spanExpiryDate"+id;
    var expiryDate = $('#'+cardExpiry).attr('value');
    $('#cardExpDate').val(expiryDate);

    $('.getcvvval').val('').focus();
    $('.getcvvval').attr('maxlength', masking);

    let placeholder_text = "0".repeat(masking);
    $('.getcvvval').attr('placeholder', placeholder_text);

    var selected_card = "delete_card_"+id;
    $('.filled-card').removeClass('active_card');
    $('#'+selected_card).find('.filled-card').addClass('active_card');
    toggleKreplingSavedCardPayButton();
}

function updateCurrencyPrice(newCurrency, symbol){
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:'POST',
        dataType:'json',
        data: {
            action:'changeCurrency',
            newCurrency:newCurrency,
            symbol:symbol
        },
        success: function(response) { 
            if(response.status == "success"){
                $('#current_currency_symbol').val(symbol);
                $('#current_currency_name').val(newCurrency);
                $('#krpAmount').val(response.convertedAmount.total);
                $('.order_summary_price_symbol').text(symbol);
                $('.cart_subtotal').html('<span><i class="fa fa current_currency_type" aria-hidden="true">'+response.symbol+'</i>'+response.convertedAmount.subtotal+'</span>');           
                $('.cart_discount').html('<span><i class="fa fa current_currency_type" aria-hidden="true">- '+response.symbol+'</i>'+response.convertedAmount.discount+'</span>');
                $('.cart_shipping').html('<span><i class="fa fa current_currency_type" aria-hidden="true">'+response.symbol+'</i>'+response.convertedAmount.shipping+'</span>');
                $('.cart_tax').html('<span><i class="fa fa current_currency_type" aria-hidden="true">'+response.symbol+'</i>'+response.convertedAmount.tax+'</span>');
                $('.cart_total').html('<strong><span><i class="fa fa current_currency_type" aria-hidden="true">'+response.symbol+'</i>'+response.convertedAmount.total+'</span></strong>');
                $('.total_cart_amount').html('<label id="lblAmountId"> <span id="current_currency">'+response.symbol+'</span>'+response.convertedAmount.total+'</label>');

                var productDetails = '';
                response.product_convertedPrices.forEach(element => {
                    productDetails += '<tr class="item-row">'+
                        '<td class="product-item">'+element.product_image+'</td>'+
                        '<td class="product-name">'+
                            '<p>'+element.product_name+'</p>'+
                            '<p>Price: <b aria-hidden="true" class="order_summary_price_symbol">'+response.symbol+'</b><strong>'+element.product_price+'</strong></p>'+
                        '</td>'+
                        '<td class="text-left product_quantity" title="quantity">'+
                            '<strong class="ProductPrice">'+
                                '<div class="productQuantity">'+
                                    '<span class="qnt_back">'+element.product_qty+'</span>'+
                                '</div>'+
                            '</strong>'+
                        '</td>'+
                        '<td class="text-right" title="price">'+
                            '<strong class="ProductPrice">'+
                                    '<span class="current_currency_type">'+response.symbol+'<b class="product_price">'+(element.product_qty * + element.product_price).toFixed(2)+'</b></span><br>'+
                            '</strong>'+
                        '</td>'+
                    '</tr>';                    
                });
                $('#panelproductId tbody').html(productDetails);
            }
        }
    });
}

$(document).ready(function(){  
    $('.selectDevices').change(function(){
        var countCheckedCheckboxes = $('.selectDevices').filter(':checked').length;
        if(countCheckedCheckboxes > 0){
            $('.logout_allDevices').removeAttr('disabled');
        }else{
            $('.logout_allDevices').attr('disabled', true);
        }
    });
});

function deleteUserAccount(user_id) {
    if(user_id != ''){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action:'deleteUserAccount',
                user_id: user_id
            },
            beforeSend: function() {
                $('.delete_btn').val('Please wait...');
                $('.delete_btn').attr('disabled',true); 
            }, 
            success: function(response){
                if(response.status == 1){
                    $('.deleteaccount_section').removeClass('hideclass');
                    $('.deleteaccount_section .common_toster').addClass('success_toster_messsage');
                    $('.deleteaccount_section .toster_message').text(response.message);
                    setTimeout(function(){
                        sessionStorage.clear();
                        location.reload(true);
                    },1000);
                }else{
                    $('.deleteaccount_section').removeClass('hideclass');
                    $('.deleteaccount_section .common_toster').addClass('error_toster_messsage');
                    $('.deleteaccount_section .toster_message').text(response.message);
                }
                removeValidationError();
            },
            complete: function(){
                $('.delete_btn').val('Confirm');
                $('.delete_btn').attr('disabled',false); 
            }
        });
    }
}

$(document).ready(function(){
    if(sessionStorage.getItem('login_status') == 1){
        $("input[name=payment_method][value=krepling]").prop('checked', true);
        $('#loginDivId').hide();
        initializeKreplingSavedCardsSlider();
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: {
                action:'getUserDetails'
            },
            success: function(response){
                if(response.status == 200){
                    sessionStorage.setItem('login_status', 1);
                    response.userData.paymentMethodVM.forEach(element => {
                        if(element.isSetdefaultCard == true){
                            $('.getcvvval').val('').focus();
                            $('.getcvvval').attr('maxlength', element.cvvMasking.length);
                            let placeholder_text = "0".repeat(element.cvvMasking.length);
                            $('.getcvvval').attr('placeholder', placeholder_text);
                            $('#cardName').val(element.cardHolderFirstName);
                            $('#cardIdNum').val(element.cardId);
                            $('#cardExpDate').val(element.expiryDate);
                        }
                    });
                }else if(response.status == 440){
                    sessionStorage.clear();
                    // $('.signup_section').removeClass('hideclass');
                    $('.signup_section .common_toster').addClass('warning_toster_messsage');
                    $('.signup_section .toster_message').text(response.message);
                }else{
                    sessionStorage.clear();
                    $('.signup_section').removeClass('hideclass');
                    $('.signup_section .common_toster').addClass('error_toster_messsage');
                    $('.signup_section .toster_message').text(response.message);
                }
                removeValidationError();
            }
        });
    }
});

$('#login_iskreplingFastId').on('click', function(){
    var is_checked = $('#login_iskreplingFastId').is(':checked');
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:'POST',
        dataType:'json',
        data: {
            action:'manageEnableFastKrepling',
            kreplingFast: is_checked
        },
        success: function(response){
            if(response.status == 1){
                $('.save_payment_details_section').removeClass('hideclass');
                $('.save_payment_details_section .common_toster').addClass('success_toster_messsage');
                $('.save_payment_details_section .toster_message').text(response.message);
            }else{
                $('.save_payment_details_section').removeClass('hideclass');
                $('.save_payment_details_section .common_toster').addClass('warning_toster_messsage');
                $('.save_payment_details_section .toster_message').text(response.message);
            }
            removeValidationError();
        }
    });
});

$("#registerCountry").change(function () {
    var selected_country = $('#registerCountry #countryManually').val();
    getZipcodePlaceholder(selected_country);
    getDeliveryStates(selected_country);
    updateLabelText(selected_country, 'shipping');
});

function updateDeliveryState(address_id, searchCountry = null, selectedState = null) {
    var savedCountry = $('#update_country_'+address_id+' #countryManually').find(":selected").val();
    var selected_country = searchCountry != null ? searchCountry : savedCountry;    
    updateLabelText(selected_country, 'shipping' , address_id);
    var data = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: selected_country
        },
        success: function(response){
            if(response != '' && response != false){
                data += '<select class="updateState" id="update_state_'+address_id +'" name="selectCountry">';
                $.each(response, function(key, value) {
                    if(selectedState != null && value == selectedState) {
                        data += '<option value="'+value+'" selected>'+value+'</option>';
                    }
                    data += '<option value="'+value+'">'+value+'</option>';
                });
            }else{
                data += '<input type="text" class="form-control state3" id="update_stateDropdown_'+address_id +'" placeholder="State" autocomplete="on"/>';
            }
            $('#edit_state_'+address_id+'').html(data);
        }
    });
}

function updateBillingState(address_id, searchCountry = null, selectedState = null) {
    var savedCountry = $('#update_billingCountry_'+address_id+' #countryManually').find(":selected").val();
    var selected_country = searchCountry != null ? searchCountry : savedCountry; 
    updateLabelText(selected_country, 'billing' , address_id);
    var data = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: selected_country
        },
        success: function(response){
            if(response != '' && response != false){
                data += '<select class="updateState" id="update_billingState_'+address_id+'" name="selectCountry">';
                $.each(response, function(key, value) {
                    if(selectedState != null && value == selectedState) {
                        data += '<option value="'+value+'" selected>'+value+'</option>';
                    }
                    data += '<option value="'+value+'">'+value+'</option>';
                });
            }else{
                data += '<input type="text" class="form-control state3" id="update_billingStateDropdown_"'+address_id +' placeholder="State" autocomplete="on"/>';
            }
            $('#edit_billingState_'+address_id+'').html(data);
        }
    });
}

function showSelectedCountry(shippingCountry, state, id, billingCountry, billingState){
    $('#update_country_'+id+' #countryManually').val(shippingCountry);
    updateLabelText(shippingCountry, 'shipping' , id);
    updateLabelText(billingCountry, 'billing' , id);
    var data = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: shippingCountry
        },
        beforeSend: function() {
            $('body').addClass('loading');
        },
        success: function(response){
            if(response != '' && response != false){
                data += '<select class="updateState" id="update_state_'+id +'" name="selectCountry">';
                $.each(response, function(key, value) {
                    if(value == state){
                        data += '<option value="'+value+'" selected>'+value+'</option>';
                    }
                    data += '<option value="'+value+'">'+value+'</option>';
                });
            }else{
                data += '<input type="text" class="form-control state3" id="update_stateDropdown_'+id +'" placeholder="State" value="'+state+'"autocomplete="on"/>';
            }
            $('#edit_state_'+id).html(data);
            $('#editAddressPopup_'+id).show();
        }
    });

    $('#update_billingCountry_'+id+' #countryManually').val(billingCountry);
    var billingStates = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: billingCountry
        },
        success: function(response){
            if(response != '' && response != false){
                billingStates += '<select class="updateState" id="update_billingState_'+id +'" name="selectCountry">';
                $.each(response, function(key, value) {
                    if(value == billingState){
                        billingStates += '<option value="'+value+'" selected>'+value+'</option>';
                    }
                    billingStates += '<option value="'+value+'">'+value+'</option>';
                });
            }else{
                billingStates += '<input type="text" class="form-control state3" id="update_billingStateDropdown_'+id +'" placeholder="State" value="'+state+'"autocomplete="on"/>';
            }
            $('#edit_billingState_'+id+'').html(billingStates);
        },
        complete: function(){
            setTimeout(function(){
                $('body').removeClass('loading');
            }, 2000);
        }
    });
}

$(document).ready(function() {
    if(sessionStorage.getItem('login_status') == 1){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"GET",
            dataType:'json',
            data: {
                action:'getReviewDevices'
            }
        });
    }

    $(window).keydown(function(event){
      if(event.keyCode == 13) {
        event.preventDefault();
        return false;
      }
    });
});

$(document).on('change','.selectDevices',function(){
    var countCheckedCheckboxes = $('.selectDevices').filter(':checked').length;
    if(countCheckedCheckboxes > 0){
        $('.selectallDevices').text('Unselect all');
        $('.logout_allDevices').removeAttr('disabled');
        $('.logout_allDevices').removeClass('disabled');
    }else{
        $('.selectallDevices').text('Select all');
        $('.logout_allDevices').attr('disabled', true);
        $('.logout_allDevices').addClass('disabled');
    }
});

$('.selectallDevices').click(function(){  
    if($('.selectDevices:checked').length > 0){
        $('.selectallDevices').text('Select all');
        $('.selectDevices').prop('checked', false);
        $('.logout_allDevices').attr('disabled', true);
        $('.logout_allDevices').addClass('disabled');
    }else{
        $('.selectallDevices').text('Unselect all');
        $('.selectDevices').prop('checked', true);
        $('.logout_allDevices').removeAttr('disabled');
        $('.logout_allDevices').removeClass('disabled');
    }
});

$('.logout_allDevices').click(function(){
    var selectedDevices = [];
    $(".selectDevices:checked").each(function(){
        selectedDevices.push($(this).val());
    });

    if(selectedDevices.length > 0){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action:'logoutSelectedDevices',
                devices : selectedDevices
            },
            success: function(response){
                if(response.logoutDevices != ''){
                    location.reload(true);
                }
            }
        });
    }
});

$('.defaltbtn').click(function(){
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'setDefaultAction'
        },   
        success: function(response){
            if(response.status == 200){
                $('.authentication_section').removeClass('hideclass');
                $('.authentication_section .common_toster').addClass('success_toster_messsage');
                $('.authentication_section .toster_message').text(response.message);
                setTimeout(function(){
                    location.reload(true);
                },2000);
            }else{
                $('.authentication_section').removeClass('hideclass');
                $('.authentication_section .common_toster').addClass('error_toster_messsage');
                $('.authentication_section .toster_message').text(response.message);
            }
            removeValidationError();
        }
    });
});
  
$(".panel-heading").click(function(){
    $(".panel-collapse").slideToggle();
    $('.delivery-Address-content').slideToggle("slow");
    $('.panel-heading').toggleClass('active');
});

// add company name
$(document).on('click', '#addcompany_button', function(){
    $("#addcompany").show();
    $('.checkout-company_name').hide();
});

function removeValidationError(){
    setTimeout(function(){
        $('.card_details_error').text('');
			$('input').removeClass('addErrorBorder');
			$('div').removeClass('addErrorBorder');
            $('span').removeClass('addErrorBorder');
	        $('select').removeClass('addErrorBorder');
	        $(".toster_msg_section").addClass("hideclass");
	        $(".common_toster").removeClass().addClass('common_toster');
    }, 10000);
}

$("#billingCountry").change(function () {
    var selected_country = $('#billingCountry #countryManually').val();
    getZipcodePlaceholder(selected_country, 'billing');
    getBillingStates(selected_country);
    updateLabelText(selected_country, 'billing');
});

function getDeliveryStates(country, selectedState = null){
    var data = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: country
        },
        success: function(response){
            if(response != ''){
                data += '<select class="registerState" id="registerStateDropdown" name="registerState">';
                $.each(response, function(key, value) {
                    if(value == selectedState){
                        data += '<option selected value="'+value+'">'+value+'</option>';
                    }
                    data += '<option value="'+value+'">'+value+'</option>';
                });
                data += '</select>';
            }else{
                data += '<input type="text" class="form-control state1" id="registerState" placeholder="State" autocomplete="on"/>';
            }
            $('.stateDropdown').html(data);
        }
    });
}

function getBillingStates(country, selectedState = null){
    var data = '';
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"POST",
        dataType:'json',
        data: {
            action:'getStates',
            country: country
        },
        success: function(response){
            if(response != ''){
                data += '<select class="billingState" id="billingStateDropdown" name="billingState">';
                $.each(response, function(key, value) {
                    if(value == selectedState){
                        data += '<option selected value="'+value+'">'+value+'</option>';
                    }
                    data += '<option value="'+value+'">'+value+'</option>';
                });
                data += '</select>';
            }else{
                data += '<input type="text" class="form-control state1" id="billingState" placeholder="State" autocomplete="on"/>';
            }
            $('.billingStateDropdown').html(data);
        }
    });
}

function changeBillingCheckbox(userId){
    var is_sameBillingShipping = $('#sameBillingShippingUpdate_'+userId).is(':checked');
    is_sameBillingShipping == true ? $('#updateBillingForm_'+userId).hide() : $('#updateBillingForm_'+userId).show();
    
    var selected_country = $('#update_billingCountry #countryManually').val();
    is_sameBillingShipping ? false : getBillingStates(selected_country);
}

function getZipcodePlaceholder(selected_country, type = null){
    $.ajax({
        url: parseData.base_url+'controller.php',
        type:"GET",
        dataType:'json',
        data: {
            action:'getZipcodePlaceholder'
        },
        success: function(response){
            response.tblCountries.forEach(element => {
                if(element.shortName == selected_country){
                    var placeholder =  element.zipCodePlaceHolder != null ? element.zipCodePlaceHolder : '00000';
                    if(type == 'billing'){
                        $('#billingZip').attr('placeholder', 'e.g. '+placeholder);
                    }else{
                        $('#registerZip').attr('placeholder', 'e.g. '+placeholder);
                    }
                }
            });
        }
    });
}

$('#first_last').on('change',function(){
    if(Cookies.get('populate_name') != 'true' || $('#card_holder_name').val() == ''){
        $('#card_holder_name').val($('#first_last').val());
    }
});

$('#card_holder_name').on('input',function(){
    Cookies.set('populate_name', true);
});

$("#card_holder_name").keyup(function (event) {
    if(event.key == 'Backspace'){
        $('#card_holder_name').val('');
    }
});

function addMerchantAddress(){
    var selected_method = $("input[name=payment_method][value=krepling]").is(':checked');
    if(sessionStorage.getItem('login_status') == 1 && selected_method == true && parseData.hide_Address == 'yes'){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:"POST",
            dataType:'json',
            data: {
                action:'addAddress',
                isDefaultStatus : 1
            },
            success: function(response){
                if(response.status == 1){
                    location.reload();
                }
            }
        });
    }
}

// Add additional information name
function sendAgainTimer(){
    var timeLeft = 29;
    $(".countdown").html("Resend in "+timeLeft+" seconds");
    $('.sms_content').hide();
    var timerId = setInterval(function(){
        if (timeLeft == -1) {
            $('#emailAddress').removeAttr('disabled');
            clearTimeout(timerId);
            $(".countdown").html("");
            $('.sms_content').show();
        } else {
            $(".countdown").html("Resend in "+timeLeft+" seconds");
            timeLeft--;
        }
    }, 1000);
}

$(document).on('click', '#saveCard_payment', function(){
    var paymentAmount = $('#krpAmount').val();
    var currencySymbol = $('#current_currency_symbol').val();
    var currencyName = $('#current_currency_name').val();
    var paymentCardName = $('#paymentCardName').val();
    var paymentCardNumber = $('#paymentCardNumber').val().replace(/\s+/g, '');
    var paymentCardExpiry = $('#paymentCardExpiry').val();
    var paymentCardCvv = $('#paymentCardCvv').val();
    var currentMonth = new Date().getMonth() + 1;
    var currentYear = new Date().getFullYear().toString().slice(-2);
    var month = paymentCardExpiry.toLocaleString().split('/')[0];
    var year = paymentCardExpiry.toLocaleString().split('/')[1];
    var cvv_regex = /^[0-9]{3,4}$/;
    var name_regex = /^[a-zA-Z]+ [a-zA-Z]+$/;
    var maxYear = parseInt(currentYear) + 25;
    var wooCheckoutValidation = validateKreplingWooCheckoutFields('.payment_section');

    if (!wooCheckoutValidation.valid) {
        removeValidationError();
        return false;
    }

    var selectedAddressId = $('input[type="radio"][name="radiosButton"]:checked').val();                
    if(selectedAddressId == undefined){
        $('.payment_section').removeClass('hideclass');
        $('.payment_section .common_toster').addClass('error_toster_messsage');
        $('.payment_section .toster_message').text("Select the delivery address to make payment");
        removeValidationError();
        return false;
    }

    if(paymentCardNumber == "" && paymentCardExpiry == "" && paymentCardCvv == "") {
        $('#stepFourId .card-information input').addClass('addErrorBorder');;                                    
        $('#paymentCardDetailsError').text('Enter your card details');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
    
    if(paymentCardExpiry == "" && paymentCardCvv == "") {
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardCvv').addClass('addErrorBorder');                                     
        $('#paymentCardDetailsError').text('Enter a valid expiration date and the CVV or security code on the back of your card');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(paymentCardNumber == null || paymentCardNumber == "") {
        $('#paymentCardNumber').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid card number');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(paymentCardNumber.length < 13) {
        $('#paymentCardNumber').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid card number');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(paymentCardExpiry == null || paymentCardExpiry == "") {
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration date');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(paymentCardExpiry.length < 5) {
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration date');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!$.isNumeric(year) || !$.isNumeric(month)){
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration date');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(year < currentYear){
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration year');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year > currentYear) && (month > 12 || month < 1)){
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration date');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if((year == currentYear) && (currentMonth >= month) || (month > 12 || month < 1)){
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration date');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    } else if(year > maxYear){
        $('#paymentCardExpiry').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid expiration year');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(paymentCardCvv == null || paymentCardCvv == "") {
        $('#paymentCardCvv').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter the CVV or security code on the back of your card');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!cvv_regex.test(paymentCardCvv)){    
        $('#paymentCardCvv').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('Enter a valid CVV or security code');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    if(paymentCardName == null || paymentCardName == "") {
        $('#paymentCardName').addClass('addErrorBorder');
        $('#paymentCardHolderNameError').text("Enter a valid cardholder name");
        $('#paymentCardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }else if(!name_regex.test(paymentCardName)){
        $('#paymentCardName').addClass('addErrorBorder');		
        $('#paymentCardHolderNameError').text("Enter your name exactly as it's written on your card");
        $('#paymentCardHolderNameError').addClass('card_details_error');
        removeValidationError();
        return false;
    }

    var match = /(?:(^4[0-9]{0,12}(?:[0-9]{3}))|(^5[1-5][0-9]{0,14})|(^222[1-9]|^22[3-9][0-9]|^2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{0,12}|(^6(?:011|5[0-9][0-9])[0-9]{0,12})|(^3[47][0-9]{0,13})|(^3(?:0[0-5]|[68][0-9])[0-9]{0,11})|(^(?:2131|1800|35\d{3})\d{0,11}))$/.exec(paymentCardNumber);
    var paymentCardType = '';
    if (match) {
      var types = ['visa', 'mastercard', 'mastercard', 'discover', 'amex', 'diners', 'jcb'];
      for (var i = 1; i < match.length; i++) {
        if (match[i]) {
            paymentCardType = types[i - 1];
          break;
        }
      }
    }

    if(paymentCardType){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type:'POST',
            dataType:'json',
            data: $.extend({
                action:'pay',
                krpAmount: paymentAmount,
                cardName: paymentCardName,
                txtCVVNumberId: paymentCardCvv,
                cardExpDate: paymentCardExpiry,
                customerCardNum: paymentCardNumber,
                currencySymbol: currencySymbol,
                currencyName: currencyName,
                addressId : selectedAddressId,
                paymentCardType : paymentCardType
            }, wooCheckoutValidation.payload),
            beforeSend: function() {
                $('#saveCard_payment').text('Processing...');
                $('#saveCard_payment').prop('disabled', true);
            },  
            success: function(response){ 
                $('#saveCard_payment').hide();
                let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;
                if(response.status == 1){
                    $('.payment_section').removeClass('hideclass');
                    $('.payment_section .common_toster').addClass('success_toster_messsage');
                    $('.payment_section .toster_message').text('Payment Successful');
                    $('#rightArrow').html('');
                    if(isMobile){
                        $('#rightArrow').html('<div class="paycheck_phone">'+
                            '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                            '</svg><span> Done </span></div>');
                    }else{
                        $('#rightArrow').html('<div class="paycheck">'+
                            '<svg width="34" height="26" viewBox="0 0 34 26" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M1 16L9.5 24.5L33 1" stroke="'+parseData.kp_button_color+'" stroke-width="2" stroke-linecap="round"></path>'+
                            '</svg></div>');
                    }
                    $('#rightArrow').show();
                    setTimeout(function(){
                        $('#rightArrow').fadeOut();
                        $('#saveCard_payment').show();
                        verifyKreplingOrderAndRedirect(response);
                    },3000);
                }else{
                    $('#rightArrow').hide();
                    $('#crossArrow').html('');
                    $('.payment_section').removeClass('hideclass');
                    $('.payment_section .common_toster').addClass('error_toster_messsage');
                    $('.payment_section .toster_message').text(response.message);
                    if(isMobile){
                        $('#crossArrow').html('<div class="cross_phone">'+
                            '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">'+
                            '<path d="M32 16L16 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path>'+
                            '<circle cx="23" cy="23" r="23" transform="matrix(-1 0 0 1 47 1)" stroke="'+parseData.kp_button_color+'" stroke-width="2"></circle>'+
                            '<path d="M16 16L32 32" stroke="'+parseData.kp_button_color+'" stroke-width="2"></path></svg>'+
                            '<span> Payment not completed </span></div>');
                    }else{
                        $('#crossArrow').html('<div class="cross">Payment not completed <span class="crossIcon">&#10005;</span></div>');
                    }
                    $('#crossArrow').show();
                    setTimeout(function(){
                        $('#crossArrow').fadeOut();
                        $('#saveCard_payment').show();
                    },5000);                
                }
                removeValidationError();
            },
            complete: function(){
                $('#saveCard_payment').text('Pay');
                $('#saveCard_payment').prop('disabled', false); 
            }
        });
    } else {
        $('#paymentCardNumber').addClass('addErrorBorder');
        $('#paymentCardDetailsError').text('We accept only: Visa, American Express, MasterCard, Discover, JCB, Diners Club.');
        $('#paymentCardDetailsError').addClass('card_details_error');
        removeValidationError();
        return false;
    }
});

function thisDeviceWasMe(deviceId) {
    var selectedDevices = [];
    selectedDevices.push(deviceId);    

    if(selectedDevices.length > 0){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action:'thisDeviceWasMe',
                devices : selectedDevices
            },
            success: function(response){                
                if(response.logoutDevices != ''){
                    $('#reviewdevice-'+deviceId).remove();
                    $('#review-btns-'+deviceId).remove();
                    $('#whereyourother-option-'+deviceId).remove();
                }
            }
        });
    }
}

function thisDeviceWasnotMe(location, deviceName, deviceId) {
    var selectedDevices = [];
    selectedDevices.push(deviceId);    
    var review_message = 'Signed out from "'+deviceName+'"in '+location;
    if(selectedDevices.length > 0){
        $.ajax({
            url: parseData.base_url+'controller.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action:'logoutSelectedDevices',
                devices : selectedDevices
            },
            success: function(response){                
                if(response.logoutDevices != ''){
                    $('#reviewdevice-'+deviceId).remove();
                    $('#review-btns-'+deviceId).remove();
                    $('#whereyourother-option-'+deviceId).remove();
                    $('#review_device_popup_'+deviceId).show();
                    $('#review_device_popup_'+deviceId+' #review_device_text').text(review_message.substring(0,50) +'...');
                }
            }
        });
    }
}

$('#review_device_reset_password').click(function(){
    $(".review_devices_page").hide();
    $(".review_toasts").hide();
    $(".wallet_changePassword_page").show();
});

$('.close_review_device_btn').click(function(e){
    var device_id = e.target.getAttribute('deviceid');    
    $('#review_device_popup_'+device_id).hide();
});


function searchCurrencyFunction() {
    const filter = $('#searchCurrency').val().toUpperCase();
    const signupDiv = document.getElementById("submenu");
    const loginDiv = document.getElementById("login_submenu");
    const main_id = signupDiv == undefined ? loginDiv : signupDiv;    
    const element = main_id.getElementsByTagName("li");
    
    for (let i = 0; i < element.length; i++) {
      txtValue = element[i].textContent || element[i].innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        element[i].style.display = "";
      } else {
        element[i].style.display = "none";
      }
    }
}


function changeBillingCheckboxText(userId){    
    $('#sameBillingShippingUpdate_'+userId).prop('checked', !($('#sameBillingShippingUpdate_'+userId).is(':checked')));
    var is_sameBillingShipping = $('#sameBillingShippingUpdate_'+userId).is(':checked');
    is_sameBillingShipping ? $('#updateBillingForm_'+userId).hide() : $('#updateBillingForm_'+userId).show();
}

$('.login-alert-sms').on('change', function(){
    var login_alert_status = $('.login-alert-sms').is(':checked');
    $.ajax({
        url: parseData.base_url+'controller.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action:'smsLoginAlerts',
            login_alert_status : login_alert_status
        },
        success: function (response) {
            if(response.status == 200){
                $('.smsloginalert_section').removeClass('hideclass');
                $('.smsloginalert_section .common_toster').addClass('success_toster_messsage');
                $('.smsloginalert_section .toster_message').text(response.message);                  
            }else{
                $('.smsloginalert_section').removeClass('hideclass');
                $('.smsloginalert_section .common_toster').addClass('error_toster_messsage');
                $('.smsloginalert_section .toster_message').text(response.message);
            }        
        }
    });    
});

$(function () {
    initializeKreplingCheckoutUi();
});

$(document.body).on('updated_checkout init_checkout', function () {
    setTimeout(initializeKreplingCheckoutUi, 0);
});

$(window).on('pageshow', function() {
    var currentView = sessionStorage.getItem('krepling_checkout_view');
    if (currentView === 'login') {
        showKreplingLoginView();
    } else {
        showKreplingGuestView();
    }
});