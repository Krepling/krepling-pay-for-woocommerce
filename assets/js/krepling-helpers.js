(function (window) {
    window.kpPayDebug = function(stage, details) {
        return;
    };

    window.kpPayFail = function(reason, details) {
        return false;
    };

    window.kpNormalizeCountryCode = function(value) {
        var v = (value || '').toString().trim().toUpperCase();

        var aliases = {
            'CANADA': 'CA',
            'CA': 'CA',
            'UNITED STATES': 'US',
            'UNITED STATES OF AMERICA': 'US',
            'USA': 'US',
            'US': 'US'
        };

        return aliases[v] || '';
    };

    window.kpInferCountryFromState = function(stateValue) {
        var v = (stateValue || '').toString().trim().toLowerCase();

        var canadaProvinces = {
            'alberta': 'CA',
            'british columbia': 'CA',
            'manitoba': 'CA',
            'new brunswick': 'CA',
            'newfoundland and labrador': 'CA',
            'nova scotia': 'CA',
            'ontario': 'CA',
            'prince edward island': 'CA',
            'quebec': 'CA',
            'saskatchewan': 'CA',
            'northwest territories': 'CA',
            'nunavut': 'CA',
            'yukon': 'CA',
            'ab': 'CA',
            'bc': 'CA',
            'mb': 'CA',
            'nb': 'CA',
            'nl': 'CA',
            'ns': 'CA',
            'on': 'CA',
            'pe': 'CA',
            'qc': 'CA',
            'sk': 'CA',
            'nt': 'CA',
            'nu': 'CA',
            'yt': 'CA'
        };

        var usStates = {
            'alabama': 'US', 'alaska': 'US', 'arizona': 'US', 'arkansas': 'US',
            'california': 'US', 'colorado': 'US', 'connecticut': 'US', 'delaware': 'US',
            'florida': 'US', 'georgia': 'US', 'hawaii': 'US', 'idaho': 'US',
            'illinois': 'US', 'indiana': 'US', 'iowa': 'US', 'kansas': 'US',
            'kentucky': 'US', 'louisiana': 'US', 'maine': 'US', 'maryland': 'US',
            'massachusetts': 'US', 'michigan': 'US', 'minnesota': 'US', 'mississippi': 'US',
            'missouri': 'US', 'montana': 'US', 'nebraska': 'US', 'nevada': 'US',
            'new hampshire': 'US', 'new jersey': 'US', 'new mexico': 'US', 'new york': 'US',
            'north carolina': 'US', 'north dakota': 'US', 'ohio': 'US', 'oklahoma': 'US',
            'oregon': 'US', 'pennsylvania': 'US', 'rhode island': 'US', 'south carolina': 'US',
            'south dakota': 'US', 'tennessee': 'US', 'texas': 'US', 'utah': 'US',
            'vermont': 'US', 'virginia': 'US', 'washington': 'US', 'west virginia': 'US',
            'wisconsin': 'US', 'wyoming': 'US', 'dc': 'US',
            'al': 'US', 'ak': 'US', 'az': 'US', 'ar': 'US', 'ca': 'US', 'co': 'US',
            'ct': 'US', 'de': 'US', 'fl': 'US', 'ga': 'US', 'hi': 'US', 'id': 'US',
            'il': 'US', 'in': 'US', 'ia': 'US', 'ks': 'US', 'ky': 'US', 'la': 'US',
            'me': 'US', 'md': 'US', 'ma': 'US', 'mi': 'US', 'mn': 'US', 'ms': 'US',
            'mo': 'US', 'mt': 'US', 'ne': 'US', 'nv': 'US', 'nh': 'US', 'nj': 'US',
            'nm': 'US', 'ny': 'US', 'nc': 'US', 'nd': 'US', 'oh': 'US', 'ok': 'US',
            'or': 'US', 'pa': 'US', 'ri': 'US', 'sc': 'US', 'sd': 'US', 'tn': 'US',
            'tx': 'US', 'ut': 'US', 'vt': 'US', 'va': 'US', 'wa': 'US', 'wv': 'US',
            'wi': 'US', 'wy': 'US'
        };

        return canadaProvinces[v] || usStates[v] || '';
    };

    window.kpInferCountryFromPostal = function(zipValue) {
        var zip = (zipValue || '').toString().trim().toUpperCase();

        if (/^[A-Z]\d[A-Z][ -]?\d[A-Z]\d$/.test(zip)) {
            return 'CA';
        }

        if (/^\d{5}(-\d{4})?$/.test(zip)) {
            return 'US';
        }

        return '';
    };

    window.kreplingDetectCountryCode = function() {
        var selectors = [
            '#billing_country',
            '#shipping_country',
            '#country',
            '#ucountry',
            '#newcountry'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var field = document.querySelector(selectors[i]);

            if (field && field.value) {
                var normalized = window.kpNormalizeCountryCode(field.value);

                if (normalized) {
                    return normalized.toLowerCase();
                }
            }
        }

        var postalSelectors = [
            '#billing_postcode',
            '#shipping_postcode',
            '#zip',
            '#update_billingZip'
        ];

        for (var j = 0; j < postalSelectors.length; j++) {
            var postalField = document.querySelector(postalSelectors[j]);

            if (postalField && postalField.value) {
                var fromPostal = window.kpInferCountryFromPostal(postalField.value);

                if (fromPostal) {
                    return fromPostal.toLowerCase();
                }
            }
        }

        return 'us';
    };

    (function registerModalHelpers($) {
        if (!$ || $.fn.kreplingModal) {
            return;
        }

        function getTargetFromTrigger(trigger) {
            var target = trigger.getAttribute('data-target') || trigger.getAttribute('href') || '';

            if (!target || target.charAt(0) !== '#') {
                return null;
            }

            return document.querySelector(target);
        }

        function showModal(modal) {
            if (!modal) {
                return;
            }

            modal.style.display = 'block';
            modal.classList.add('in');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('krepling-modal-open');
        }

        function hideModal(modal) {
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            modal.classList.remove('in');
            modal.setAttribute('aria-hidden', 'true');

            if (!document.querySelector('.modal.in')) {
                document.body.classList.remove('krepling-modal-open');
            }
        }

        $.fn.modal = function(action) {
            return this.each(function() {
                if (action === 'hide') {
                    hideModal(this);
                } else {
                    showModal(this);
                }
            });
        };

        $(document)
            .off('click.kreplingModalOpen', '[data-toggle*="modal"]')
            .on('click.kreplingModalOpen', '[data-toggle*="modal"]', function(event) {
                var target = getTargetFromTrigger(this);

                if (!target) {
                    return;
                }

                event.preventDefault();
                showModal(target);
            });

        $(document)
            .off('click.kreplingModalClose', '[data-dismiss="modal"], .customClose')
            .on('click.kreplingModalClose', '[data-dismiss="modal"], .customClose', function(event) {
                var modal = this.closest('.modal');

                if (!modal && this.getAttribute('data-target')) {
                    modal = document.querySelector(this.getAttribute('data-target'));
                }

                if (!modal) {
                    return;
                }

                event.preventDefault();
                hideModal(modal);
            });
    })(window.jQuery);

    (function registerCollapseHelpers($) {
        if (!$) {
            return;
        }

        $(document)
            .off('click.kreplingCollapse', '[data-toggle="collapse"]')
            .on('click.kreplingCollapse', '[data-toggle="collapse"]', function(event) {
                var targetSelector = this.getAttribute('data-target') || this.getAttribute('href') || '';

                if (!targetSelector || targetSelector.charAt(0) !== '#') {
                    return;
                }

                var $target = $(targetSelector);

                if (!$target.length) {
                    return;
                }

                event.preventDefault();
                $target.stop(true, true).slideToggle();
            });
    })(window.jQuery);

    (function registerIntlTelStub($) {
        if (!$ || $.fn.intlTelInput) {
            return;
        }

        var dialCodes = {
            us: '1',
            ca: '1',
            gb: '44',
            au: '61'
        };

        function getCountryData(iso2) {
            var normalized = (iso2 || 'us').toString().toLowerCase();

            return {
                iso2: normalized,
                dialCode: dialCodes[normalized] || '1'
            };
        }

        $.fn.intlTelInput = function(option) {
            if (typeof option === 'string') {
                var instance = this.first().data('kreplingIntlTelInput');

                if (!instance) {
                    return option === 'getSelectedCountryData' ? getCountryData('us') : this;
                }

                if (option === 'getSelectedCountryData') {
                    return instance.countryData;
                }

                return this;
            }

            return this.each(function() {
                var inferredCountry = window.kreplingDetectCountryCode ? window.kreplingDetectCountryCode() : 'us';
                var countryData = getCountryData(inferredCountry);

                $(this).data('kreplingIntlTelInput', {
                    options: option || {},
                    countryData: countryData
                });
            });
        };
    })(window.jQuery);

    if (!window.intlTelInputUtils) {
        window.intlTelInputUtils = {
            numberFormat: {
                E164: 0,
                INTERNATIONAL: 1,
                NATIONAL: 2,
                RFC3966: 3
            },
            getExampleNumber: function(iso2) {
                return iso2 && iso2.toLowerCase() === 'ca' ? '+1 604 555 1234' : '+1 555 123 4567';
            },
            formatNumber: function(number) {
                return number || '';
            }
        };
    }

    window.get_browser_details = function() {
        let browser_name;
        let userAgent = window.navigator.userAgent;
        var Browsers = ['Trident', 'Edg', 'MSIE', 'OPR', 'Chrome', 'Firefox', 'Safari'];
        for (var i = 0; i < Browsers.length; i++) {
            if (userAgent.indexOf(Browsers[i]) > -1) {
                browser_name = Browsers[i];
                break;
            }
        }
        switch (browser_name) {
            case 'Trident':
            case 'MSIE':
                browser_name = 'Internet Explorer';
                break;
            case 'Edg':
                browser_name = 'Microsoft Edge';
                break;
            case 'Chrome':
                browser_name = 'Google Chrome';
                break;
            case 'Firefox':
                browser_name = 'Mozilla Firefox';
                break;
            case 'OPR':
                browser_name = 'Opera Mini';
                break;
            case 'Safari':
                browser_name = 'Safari';
                break;
            default:
                browser_name = 'Browser Info not found';
                break;
        }
        return browser_name;
    };

    window.formatKreplingExpiryValue = function(value) {
        var raw = String(value || '').replace(/\D/g, '').slice(0, 4);

        if (raw.length === 1 && parseInt(raw, 10) > 1) {
            raw = '0' + raw;
        }

        if (raw.length >= 3) {
            return raw.slice(0, 2) + '/' + raw.slice(2);
        }

        if (raw.length === 2) {
            return raw + '/';
        }

        return raw;
    };
})(window);
