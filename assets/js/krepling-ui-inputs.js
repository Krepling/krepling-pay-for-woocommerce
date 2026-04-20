(function ($) {
$("#card, #cardvalidityDateId, #txtcvvNumberId, #addCardNumber, #addCardExpiry, #addCardCvv, .getcvvval, .smsCode, #phoneNumberIds, .phoneCode, #otp_phoneNumber").keypress(function (e) {
    if(e.which < 48 || e.which > 57){
        return false;
    }
});

// Open add card popup
$('#divAddCardId').click(function(){
    $('#addcard').show();  
    $('#stepFourId').hide();
    $('#myHeader').hide();
    $('.account_card_copyright').hide();
});

$(function () {
    if (typeof window.kreplingInitPhone === 'function') {
        window.kreplingInitPhone();
    }
});

$(document).on('click', '#flipDivId', function(){
    $('#panelproductId').slideToggle();
    $(".rotate").toggleClass("down");
});

$(document).ready(function(){
    initializeKreplingCheckoutUi();
});

//getCurrency
$(document).on('click', '.currency_dropdown', function(e){
    e.preventDefault();
    e.stopPropagation();

    var $toggle = $(this);
    var $menu = $toggle.closest('li').children('.submenu');
    var isOpen = $menu.is(':visible');

    $('.submenu').not($menu).slideUp();
    $('.rotatearrow').not($toggle.find('.rotatearrow')).removeClass('down');

    if (isOpen) {
        $menu.slideUp();
        $toggle.find('.rotatearrow').removeClass('down');
    } else {
        $menu.slideDown();
        $toggle.find('.rotatearrow').addClass('down');
    }
});

$(document).on('click', '#submenu li, #login_submenu li', function(ev){
    var $menu = $(this).closest('.submenu');
    var $toggle = $menu.siblings('.currency_dropdown');

    $toggle.find('span').first().text(ev.currentTarget.innerText);
    $menu.slideUp();
    $toggle.find('.rotatearrow').removeClass('down');
});

$(document).on('click', function(e){
    if ($(e.target).closest('.currency_dropdown, .submenu').length) {
        return;
    }

    $('.submenu').slideUp();
    $('.rotatearrow').removeClass('down');
});

//load card image based on cardType
$(document).on('keyup', '#card, #addCardNumber, #paymentCardNumber', function(){
    var number = $(this).val(); 
    // Strip spaces and dashes
    cardnumber = number.replace(/[ -]/g, '');
    // See if the card is valid
    // The regex will capture the number in one of the capturing groups
    var match = /(?:(^4[0-9]{0,12}(?:[0-9]{3}))|(^5[1-5][0-9]{0,14})|(^222[1-9]|^22[3-9][0-9]|^2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{0,12}|(^6(?:011|5[0-9][0-9])[0-9]{0,12})|(^3[47][0-9]{0,13})|(^3(?:0[0-5]|[68][0-9])[0-9]{0,11})|(^(?:2131|1800|35\d{3})\d{0,11}))$/.exec(cardnumber);
    if (match) {
      // List of card types, in the same order as the regex capturing groups
      var types = ['visa', 'mastercard', 'mastercard', 'discover', 'amex', 'diners', 'jcb'];
      // Find the capturing group that matched
      // Skip the zeroth element of the match array (the overall match)
      for (var i = 1; i < match.length; i++) {
        if (match[i]) {
            if(types[i - 1] == 'amex'){
                $('#'+$(this)[0].id).parent().parent().children('.card-info-3').children("input").attr('maxlength', 4);
                $('#'+$(this)[0].id).parent().parent().children('.card-info-3').children("input").attr('placeholder', '0000');
            }else{
                $('#'+$(this)[0].id).parent().parent().children('.card-info-3').children("input").attr('maxlength', 3);
                $('#'+$(this)[0].id).parent().parent().children('.card-info-3').children("input").attr('placeholder', '000');
            }
          // Display the card type for that group
          $('#cardlogo').html('<img src="'+parseData.base_url+'assets/images/cardType/'+types[i - 1]+'.svg">');
          break;
        }
      }
    } else {
      $('#cardlogo').html('<svg width="32" height="21" viewBox="0 0 32 21" fill="none" xmlns="http://www.w3.org/2000/svg">'+
        '<path d="M0 7H31.56V18.51C31.56 19.8852 30.4452 21 29.07 21H2.49C1.11481 21 0 19.8852 0 18.51V7Z" fill="#BBBBBB"/>'+
        '<path d="M0 2.49C0 1.11481 1.11481 0 2.49 0H29.07C30.4452 0 31.56 1.11481 31.56 2.49V4H0V2.49Z" fill="#BBBBBB"/>'+
        '<line x1="3" y1="17" x2="11.1562" y2="17" stroke="#EFEFEF" stroke-width="2"/>'+
        '<rect x="24" y="14" width="4.53125" height="4.53125" rx="1.5" fill="#EFEFEF"/>'+
        '</svg>');
    }
});

var cardNumberFormat = document.getElementById('card') != undefined ? document.getElementById('card') : document.getElementById('addCardNumber');
if(cardNumberFormat != undefined){
    cardNumberFormat.addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\dA-Z]/g, '').replace(/(.{4})/g, '$1 ').trim();
    });
}

var paymentCardNumber = document.getElementById('paymentCardNumber');
if(paymentCardNumber != undefined){
    paymentCardNumber.addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\dA-Z]/g, '').replace(/(.{4})/g, '$1 ').trim();
    });
}

//phone Code Verification
$(".verification-code input[type=tel], .emailverification-code input[type=tel], .SMSArea input[type=tel]").keyup(function (event) {
    if (event.key >= 0) {
        $(this).next().focus();
    }
    if(event.key == 'Backspace'){
        $(this).prev().focus();
    }
}); // keyup

$(document).on('click', '#togglePassword', function(){
    const type = $('#user_password').attr('type') == 'password' ? 'text' : 'password';
    $('#user_password').attr('type', type);
    if($('#user_password').attr('type') == 'password'){
        $('#togglePassword').html('<svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>'+
            '<path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>'+
            '<rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>'+
            '<rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>'+
        '</svg>');
    }else{
        $('#togglePassword').html('<svg width="24" height="16" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M21.8357 5.37087C21.6394 5.15182 16.916 0 11 0C5.08399 0 0.360701 5.15182 0.164291 5.37087C-0.0547636 5.61571 -0.0547636 5.98584 0.164291 6.23068C0.360701 6.44973 5.08408 11.6015 11 11.6015C16.9159 11.6015 21.6393 6.44973 21.8357 6.23068C22.0547 5.98584 22.0547 5.61571 21.8357 5.37087ZM11 10.3125C8.51251 10.3125 6.4883 8.28828 6.4883 5.80077C6.4883 3.31327 8.51251 1.28906 11 1.28906C13.4875 1.28906 15.5117 3.31327 15.5117 5.80077C15.5117 8.28828 13.4875 10.3125 11 10.3125Z" fill="#AAAAAA"/>'+
            '<path d="M11.6445 4.51172C11.6445 3.86332 11.9664 3.29291 12.4561 2.94207C12.0167 2.71713 11.5266 2.57812 11 2.57812C9.22311 2.57812 7.77734 4.02389 7.77734 5.80078C7.77734 7.57766 9.22311 9.02343 11 9.02343C12.5909 9.02343 13.9076 7.86181 14.1677 6.34399C12.8698 6.76186 11.6445 5.78024 11.6445 4.51172Z" fill="#AAAAAA"/>'+
        '</svg>');
    }
});

$(document).on('input keyup change paste', '.getcvvval', function(){
    toggleKreplingSavedCardPayButton();
});

$(document).ready(function () {
    setTimeout(function () {
        toggleKreplingSavedCardPayButton();
    }, 0);
});
$("#undo_delete_address").click(function(){
    sessionStorage.setItem('delete-address-status', 'false');
    $('#deleted_address_popup').hide();
});

$("#close_delete_address_btn").click(function(){
    $('#deleted_address_popup').hide();
});

$("#undo_delete_card").click(function(){
    sessionStorage.setItem('delete-card-status', 'false');
    $('#deleted_card_popup').hide();
});

$("#close_delete_card_btn").click(function(){
    $('#deleted_card_popup').hide();
});

$(".fa-times").click(function(){
    $(".toster_msg_section").addClass("hideclass");
    $(".common_toster").removeClass().addClass('common_toster');
});

$(document).on('click', '#toggleNewPassword', function(){
    const type = $('#newPassword').attr('type') == 'password' ? 'text' : 'password';
    $('#newPassword').attr('type', type);
    if($('#newPassword').attr('type') == 'password'){
        $('#toggleNewPassword').html('<svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>'+
            '<path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>'+
            '<rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>'+
            '<rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>'+
        '</svg>');
    }else{
        $('#toggleNewPassword').html('<svg width="24" height="16" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M21.8357 5.37087C21.6394 5.15182 16.916 0 11 0C5.08399 0 0.360701 5.15182 0.164291 5.37087C-0.0547636 5.61571 -0.0547636 5.98584 0.164291 6.23068C0.360701 6.44973 5.08408 11.6015 11 11.6015C16.9159 11.6015 21.6393 6.44973 21.8357 6.23068C22.0547 5.98584 22.0547 5.61571 21.8357 5.37087ZM11 10.3125C8.51251 10.3125 6.4883 8.28828 6.4883 5.80077C6.4883 3.31327 8.51251 1.28906 11 1.28906C13.4875 1.28906 15.5117 3.31327 15.5117 5.80077C15.5117 8.28828 13.4875 10.3125 11 10.3125Z" fill="#AAAAAA"/>'+
            '<path d="M11.6445 4.51172C11.6445 3.86332 11.9664 3.29291 12.4561 2.94207C12.0167 2.71713 11.5266 2.57812 11 2.57812C9.22311 2.57812 7.77734 4.02389 7.77734 5.80078C7.77734 7.57766 9.22311 9.02343 11 9.02343C12.5909 9.02343 13.9076 7.86181 14.1677 6.34399C12.8698 6.76186 11.6445 5.78024 11.6445 4.51172Z" fill="#AAAAAA"/>'+
        '</svg>');
    }
});

$(document).on('click', '#toggleRePassword', function(){
    const type = $('#repassword').attr('type') == 'password' ? 'text' : 'password';
    $('#repassword').attr('type', type);
    if($('#repassword').attr('type') == 'password'){
        $('#toggleRePassword').html('<svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>'+
            '<path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>'+
            '<rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>'+
            '<rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>'+
        '</svg>');
    }else{
        $('#toggleRePassword').html('<svg width="24" height="16" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg">'+
            '<path d="M21.8357 5.37087C21.6394 5.15182 16.916 0 11 0C5.08399 0 0.360701 5.15182 0.164291 5.37087C-0.0547636 5.61571 -0.0547636 5.98584 0.164291 6.23068C0.360701 6.44973 5.08408 11.6015 11 11.6015C16.9159 11.6015 21.6393 6.44973 21.8357 6.23068C22.0547 5.98584 22.0547 5.61571 21.8357 5.37087ZM11 10.3125C8.51251 10.3125 6.4883 8.28828 6.4883 5.80077C6.4883 3.31327 8.51251 1.28906 11 1.28906C13.4875 1.28906 15.5117 3.31327 15.5117 5.80077C15.5117 8.28828 13.4875 10.3125 11 10.3125Z" fill="#AAAAAA"/>'+
            '<path d="M11.6445 4.51172C11.6445 3.86332 11.9664 3.29291 12.4561 2.94207C12.0167 2.71713 11.5266 2.57812 11 2.57812C9.22311 2.57812 7.77734 4.02389 7.77734 5.80078C7.77734 7.57766 9.22311 9.02343 11 9.02343C12.5909 9.02343 13.9076 7.86181 14.1677 6.34399C12.8698 6.76186 11.6445 5.78024 11.6445 4.51172Z" fill="#AAAAAA"/>'+
        '</svg>');
    }
});

$(document).on('input', '#cardvalidityDateId, #addCardExpiry, #paymentCardExpiry', function() {
    $(this).val(formatKreplingExpiryValue($(this).val()));
});

$('#txtCVVNumberId')
  .on('focusin', function() {
    $(this).attr('type', 'text');
  })
  .on('focusout', function() {
    $(this).attr('type', 'password');
  });

})(window.jQuery);
