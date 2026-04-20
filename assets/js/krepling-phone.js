(function () {
  function getUtilsUrl() {
    if (window.kreplingPhoneConfig && window.kreplingPhoneConfig.utilsUrl) {
      return window.kreplingPhoneConfig.utilsUrl;
    }
    return '';
  }

  function applyPhoneMask(input, iti) {
    if (
      !input ||
      !iti ||
      typeof window.intlTelInputUtils === 'undefined' ||
      typeof iti.getSelectedCountryData !== 'function'
    ) {
      return;
    }

    try {
      var selectedCountry = iti.getSelectedCountryData();
      if (!selectedCountry || !selectedCountry.iso2) {
        return;
      }

      var dialCode = selectedCountry.dialCode || '';
      var maskNumber = window.intlTelInputUtils.getExampleNumber(selectedCountry.iso2, 0, 0);
      maskNumber = window.intlTelInputUtils.formatNumber(maskNumber, selectedCountry.iso2, 2);
      maskNumber = maskNumber.replace('+' + dialCode + ' ', '');
      var mask = maskNumber.replace(/[0-9+]/gi, '9');

      if (window.jQuery && typeof window.jQuery(input).inputmask === 'function') {
        window.jQuery(input).val('');
        window.jQuery(input).inputmask(mask);
      }
    } catch (e) {
      console.warn('[Krepling phone] Failed applying phone mask', e);
    }
  }

  function initSinglePhone(input) {
    if (!input || typeof window.intlTelInput !== 'function') {
      return null;
    }

    if (input._kreplingIti && typeof input._kreplingIti.destroy === 'function') {
      input._kreplingIti.destroy();
      input._kreplingIti = null;
    }

    var iti = window.intlTelInput(input, {
      initialCountry: (window.kreplingPhoneConfig && window.kreplingPhoneConfig.initialCountry) || 'ca',
      nationalMode: true,
      separateDialCode: true,
      formatAsYouType: true,
      autoPlaceholder: 'polite',
      countrySearch: false,
      dropdownContainer: document.body,
      useFullscreenPopup: false,
      loadUtils: function () {
        var utilsUrl = getUtilsUrl();
        return utilsUrl ? import(utilsUrl) : Promise.resolve();
      }
    });

    input._kreplingIti = iti;

    input.addEventListener('countrychange', function () {
      applyPhoneMask(input, iti);
    });

    setTimeout(function () {
      applyPhoneMask(input, iti);
    }, 50);

    return iti;
  }

  function initKreplingPhone() {
    var phoneInputs = document.querySelectorAll('#phoneNumberIds, #otp_phoneNumber');
    if (!phoneInputs.length) {
      return;
    }

    phoneInputs.forEach(function (input) {
      initSinglePhone(input);
    });
  }

  function normalizePhoneInput(input) {
    if (!input || !input._kreplingIti) {
      return;
    }

    try {
      if (input._kreplingIti.isValidNumber()) {
        input.value = input._kreplingIti.getNumber();
      }
    } catch (e) {
      console.warn('[Krepling phone] Failed normalizing phone', e);
    }
  }

  function normalizePhonesBeforePayment() {
    normalizePhoneInput(document.querySelector('#phoneNumberIds'));
    normalizePhoneInput(document.querySelector('#otp_phoneNumber'));
  }

  document.addEventListener('DOMContentLoaded', function () {
    initKreplingPhone();

    var saveCardButton = document.querySelector('#saveCard_payment');
    if (saveCardButton) {
      saveCardButton.addEventListener('click', normalizePhonesBeforePayment, true);
    }

    var completePaymentButton = document.querySelector('#complete_payment');
    if (completePaymentButton) {
      completePaymentButton.addEventListener('click', normalizePhonesBeforePayment, true);
    }
  });

  if (window.jQuery) {
    jQuery(document.body).on('updated_checkout init_checkout', function () {
      setTimeout(initKreplingPhone, 0);
      setTimeout(initKreplingPhone, 150);
    });
  }

  window.kreplingInitPhone = initKreplingPhone;
})();