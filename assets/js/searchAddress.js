(function () {
    function debug() {
        return;
    }

    debug("searchAddress.js loaded");

    function getConfig() {
        if (window.kreplingConfig) {
            return window.kreplingConfig;
        }

        var node = document.querySelector("#krepling-config-data");
        debug("config node found?", !!node);

        if (!node) {
            return null;
        }

        try {
            var parsed = JSON.parse(node.textContent || node.innerHTML || "{}");
            debug("config parsed", parsed);
            return parsed;
        } catch (e) {
            return null;
        }
    }

    var config = getConfig();
    var servicesBase = config && config.services_base ? config.services_base.replace(/\/$/, "") : "";
    var mapsServiceBase = servicesBase ? (servicesBase + "/maps") : "";
    var publicServiceTokenEndpoint = config && config.public_service_token_endpoint ? config.public_service_token_endpoint : "";
    var publicMapsTokenCache = {
        token: "",
        expiresAt: 0
    };

    debug("mapsServiceBase =", mapsServiceBase);
    debug("publicServiceTokenEndpoint =", publicServiceTokenEndpoint);

    function generateSessionToken() {
        var token = "kp_" + Math.random().toString(36).slice(2) + Date.now().toString(36);
        debug("generated session token", token);
        return token;
    }

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function ensureStyle() {
        if (document.getElementById("kp-address-autocomplete-style")) {
            debug("style already present");
            return;
        }

        debug("injecting autocomplete style");

        var style = document.createElement("style");
        style.id = "kp-address-autocomplete-style";
        style.textContent = [
            ".kp-autocomplete-wrap{position:relative;}",
            ".kp-autocomplete-menu{position:absolute;left:0;right:0;top:100%;z-index:99999;background:#fff;border:1px solid #d9d9d9;border-top:none;max-height:240px;overflow:auto;box-shadow:0 8px 24px rgba(0,0,0,.12);}",
            ".kp-autocomplete-item{padding:10px 12px;cursor:pointer;font-size:14px;line-height:1.4;border-top:1px solid #f3f3f3;}",
            ".kp-autocomplete-item:first-child{border-top:none;}",
            ".kp-autocomplete-item:hover{background:#f7f7f7;}",
            ".kp-autocomplete-hidden{display:none;}"
        ].join("");
        document.head.appendChild(style);
    }

    function wrapInput(input) {
        if (!input) {
            debug("wrapInput skipped: no input");
            return;
        }

        if (input.dataset.kpWrapped === "1") {
            debug("wrapInput skipped: already wrapped", input.id, input.name);
            return;
        }

        debug("wrapping input", {
            id: input.id,
            name: input.name,
            type: input.type,
            value: input.value
        });

        var wrapper = document.createElement("div");
        wrapper.className = "kp-autocomplete-wrap";

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var menu = document.createElement("div");
        menu.className = "kp-autocomplete-menu kp-autocomplete-hidden";
        wrapper.appendChild(menu);

        input.dataset.kpWrapped = "1";
        input._kpMenu = menu;
    }

    async function postJson(url, payload, extraHeaders) {
        debug("POST", url, payload);

        var headers = Object.assign({
            "Content-Type": "application/json"
        }, extraHeaders || {});

        var resp = await fetch(url, {
            method: "POST",
            headers: headers,
            body: JSON.stringify(payload || {})
        });

        debug("POST response status", resp.status, url);

        var data = await resp.json();
        debug("POST response data", data);

        if (!resp.ok) {
            throw new Error((data && data.detail) ? JSON.stringify(data.detail) : ("HTTP " + resp.status));
        }
        return data;
    }

    async function getPublicMapsToken(forceRefresh) {
        var now = Math.floor(Date.now() / 1000);

        if (
            !forceRefresh &&
            publicMapsTokenCache.token &&
            publicMapsTokenCache.expiresAt > (now + 30)
        ) {
            return publicMapsTokenCache.token;
        }

        if (!publicServiceTokenEndpoint) {
            throw new Error("Missing public service token endpoint");
        }

        var data = await postJson(
            publicServiceTokenEndpoint,
            {},
            {}
        );

        if (!data || !data.token) {
            throw new Error("Missing public maps token");
        }

        publicMapsTokenCache.token = String(data.token);
        publicMapsTokenCache.expiresAt = parseInt(data.expires_at || "0", 10) || (now + 240);

        if (data.maps_base) {
            mapsServiceBase = String(data.maps_base).replace(/\/$/, "");
        }

        return publicMapsTokenCache.token;
    }

    async function fetchSuggestions(inputText, sessionToken, countryCode) {
        debug("fetchSuggestions()", {
            inputText: inputText,
            sessionToken: sessionToken,
            countryCode: countryCode
        });

        var payload = {
            input: inputText,
            session_token: sessionToken,
            country_code: countryCode || null
        };

        var token = await getPublicMapsToken(false);

        try {
            return await postJson(
                mapsServiceBase + "/autocomplete",
                payload,
                {
                    "Authorization": "Bearer " + token
                }
            );
        } catch (err) {
            if (String(err && err.message || "").indexOf("401") !== -1) {
                token = await getPublicMapsToken(true);
                return await postJson(
                    mapsServiceBase + "/autocomplete",
                    payload,
                    {
                        "Authorization": "Bearer " + token
                    }
                );
            }
            throw err;
        }
    }

    async function fetchPlaceDetails(placeId, sessionToken) {
        debug("fetchPlaceDetails()", {
            placeId: placeId,
            sessionToken: sessionToken
        });

        var payload = {
            place_id: placeId,
            session_token: sessionToken
        };

        var token = await getPublicMapsToken(false);

        try {
            return await postJson(
                mapsServiceBase + "/place-details",
                payload,
                {
                    "Authorization": "Bearer " + token
                }
            );
        } catch (err) {
            if (String(err && err.message || "").indexOf("401") !== -1) {
                token = await getPublicMapsToken(true);
                return await postJson(
                    mapsServiceBase + "/place-details",
                    payload,
                    {
                        "Authorization": "Bearer " + token
                    }
                );
            }
            throw err;
        }
    }

    function setSelectValue(selector, value) {
        debug("setSelectValue", selector, value);
        if (!value) return;
        var el = document.querySelector(selector);
        if (!el) {
            debug("setSelectValue target not found", selector);
            return;
        }
        el.value = value;
        if (window.jQuery) {
            window.jQuery(el).trigger("change");
        }
    }

    function setInputValue(selector, value) {
        debug("setInputValue", selector, value);
        var el = document.querySelector(selector);
        if (!el) {
            debug("setInputValue target not found", selector);
            return;
        }
        el.value = value || "";
        if (window.jQuery) {
            window.jQuery(el).trigger("change");
        }
    }

    function applyDeliveryPlace(place) {
        debug("applyDeliveryPlace", place);

        var selectedState = place.state || "";

        if (typeof getDeliveryStates === "function" && place.country_code) {
            getDeliveryStates(place.country_code, selectedState);
        }

        setInputValue("#registerCity", place.city || "");
        setInputValue("#registerSearchAddress", place.line1 || place.formatted_address || "");
        setInputValue("#address1", place.line1 || place.formatted_address || "");
        setInputValue("#registerState", place.state || "");
        setInputValue("#registerZip", place.postal_code || "");

        setTimeout(function () {
            setSelectValue("#registerStateDropdown", place.state || "");
            setSelectValue("#registerCountry #countryManually", place.country_code || "");
        }, 250);
    }

    function applyBillingPlace(place) {
        debug("applyBillingPlace", place);

        var selectedState = place.state || "";

        if (typeof getBillingStates === "function" && place.country_code) {
            getBillingStates(place.country_code, selectedState);
        }

        setInputValue("#billingCity", place.city || "");
        setInputValue("#billingAddress", place.line1 || place.formatted_address || "");
        setInputValue("#billingState", place.state || "");
        setInputValue("#billingZip", place.postal_code || "");

        setTimeout(function () {
            setSelectValue("#billingStateDropdown", place.state || "");
            setSelectValue("#billingCountry #countryManually", place.country_code || "");
        }, 250);
    }

    function applyUpdateDeliveryPlace(addressId, place) {
        debug("applyUpdateDeliveryPlace", addressId, place);

        var selectedState = place.state || "";

        if (typeof updateDeliveryState === "function" && place.country_code) {
            updateDeliveryState(addressId, place.country_code, selectedState);
        }

        setInputValue("#ucity_" + addressId, place.city || "");
        setInputValue("#newstreetaddress_line1_" + addressId, place.line1 || place.formatted_address || "");
        setInputValue("#update_state_" + addressId, place.state || "");
        setInputValue("#uzip_" + addressId, place.postal_code || "");

        setTimeout(function () {
            setSelectValue("#update_stateDropdown_" + addressId, place.state || "");
            setSelectValue("#update_country_" + addressId + " #countryManually", place.country_code || "");
        }, 250);
    }

    function applyUpdateBillingPlace(addressId, place) {
        debug("applyUpdateBillingPlace", addressId, place);

        var selectedState = place.state || "";

        if (typeof updateBillingState === "function" && place.country_code) {
            updateBillingState(addressId, place.country_code, selectedState);
        }

        setInputValue("#update_billingCity_" + addressId, place.city || "");
        setInputValue("#update_billingAddress_" + addressId, place.line1 || place.formatted_address || "");
        setInputValue("#update_billingState_" + addressId, place.state || "");
        setInputValue("#update_billingZip_" + addressId, place.postal_code || "");

        setTimeout(function () {
            setSelectValue("#update_billingStateDropdown_" + addressId, place.state || "");
            setSelectValue("#update_billingCountry_" + addressId + " #countryManually", place.country_code || "");
        }, 250);
    }

    function hideMenu(menu) {
        if (!menu) {
            debug("hideMenu skipped: no menu");
            return;
        }
        menu.classList.add("kp-autocomplete-hidden");
        menu.innerHTML = "";
    }

    function renderMenu(input, suggestions, onSelect, sessionToken) {
        var menu = input._kpMenu;
        debug("renderMenu", {
            inputId: input && input.id,
            suggestionsCount: suggestions ? suggestions.length : 0
        });

        if (!menu) {
            debug("renderMenu skipped: no menu on input", input);
            return;
        }

        if (!suggestions || !suggestions.length) {
            hideMenu(menu);
            return;
        }

        menu.innerHTML = "";

        suggestions.forEach(function (item) {
            var row = document.createElement("div");
            row.className = "kp-autocomplete-item";
            row.textContent = item.text || "";

            row.addEventListener("mousedown", async function (e) {
                e.preventDefault();
                debug("clicked suggestion", item);

                try {
                    var detailResp = await fetchPlaceDetails(item.place_id, sessionToken);
                    if (detailResp && detailResp.place) {
                        onSelect(detailResp.place);
                        input.value = detailResp.place.line1 || detailResp.place.formatted_address || item.text || "";
                    }
                } catch (err) {
                } finally {
                    hideMenu(menu);
                }
            });

            menu.appendChild(row);
        });

        menu.classList.remove("kp-autocomplete-hidden");
        debug("menu shown");
    }

    function attachAutocomplete(input, onSelect, getCountryCode) {
        if (!input) {
            debug("attachAutocomplete skipped: input missing");
            return;
        }

        if (input.dataset.kpAutocompleteAttached === "1") {
            debug("attachAutocomplete skipped: already attached", input.id, input.name);
            return;
        }
        
        if (!mapsServiceBase || !publicServiceTokenEndpoint) {
            debug("attachAutocomplete skipped: maps service configuration missing");
            return;
        }

        debug("attachAutocomplete binding", {
            id: input.id,
            name: input.name,
            type: input.type,
            placeholder: input.placeholder,
            visible: !!(input.offsetWidth || input.offsetHeight || input.getClientRects().length)
        });

        wrapInput(input);
        input.dataset.kpAutocompleteAttached = "1";

        var sessionToken = generateSessionToken();

        var runLookup = debounce(async function () {
            var query = (input.value || "").trim();
            debug("runLookup fired", {
                id: input.id,
                name: input.name,
                query: query,
                length: query.length
            });

            if (query.length < 3) {
                debug("query too short, hiding menu");
                hideMenu(input._kpMenu);
                return;
            }

            try {
                var countryCode = typeof getCountryCode === "function" ? getCountryCode() : null;
                debug("countryCode =", countryCode);

                var resp = await fetchSuggestions(query, sessionToken, countryCode);

                renderMenu(
                    input,
                    (resp && resp.suggestions) ? resp.suggestions : [],
                    function (place) {
                        onSelect(place);
                        sessionToken = generateSessionToken();
                    },
                    sessionToken
                );
            } catch (err) {
                hideMenu(input._kpMenu);
            }
        }, 250);

        input.addEventListener("input", function (e) {
            debug("input event", {
                id: input.id,
                name: input.name,
                value: input.value
            });
            runLookup(e);
        });

        input.addEventListener("blur", function () {
            debug("blur event", input.id, input.name);
            setTimeout(function () {
                hideMenu(input._kpMenu);
            }, 200);
        });

        input.addEventListener("focus", function () {
            debug("focus event", {
                id: input.id,
                name: input.name,
                value: input.value
            });

            if ((input.value || "").trim().length >= 3) {
                runLookup();
            }
        });
    }

    function attachAutocompleteToAll(selector, onSelect, getCountryCode) {
        var nodes = document.querySelectorAll(selector);
        debug("attachAutocompleteToAll", selector, "count =", nodes.length);

        nodes.forEach(function (node, index) {
            debug("node[" + index + "]", {
                id: node.id,
                name: node.name,
                type: node.type,
                value: node.value,
                visible: !!(node.offsetWidth || node.offsetHeight || node.getClientRects().length)
            });
            attachAutocomplete(node, onSelect, getCountryCode);
        });
    }

    function initStaticAutocomplete() {
        debug("initStaticAutocomplete start");
        ensureStyle();

        var deliveryCountryGetter = function () {
            var el = document.querySelector("#registerCountry #countryManually");
            return el ? el.value : null;
        };

        var billingCountryGetter = function () {
            var el = document.querySelector("#billingCountry #countryManually");
            return el ? el.value : null;
        };

        attachAutocompleteToAll("input#registerSearchAddress", applyDeliveryPlace, deliveryCountryGetter);
        attachAutocompleteToAll("input#address1", applyDeliveryPlace, deliveryCountryGetter);
        attachAutocompleteToAll("input#billingAddress", applyBillingPlace, billingCountryGetter);
        debug("initStaticAutocomplete done");
    }

    function initDynamicDeliveryAutocomplete(addressId) {
        debug("initDynamicDeliveryAutocomplete", addressId);
        ensureStyle();

        var input = document.getElementById("newstreetaddress_line1_" + addressId);
        var getter = function () {
            var el = document.querySelector("#update_country_" + addressId + " #countryManually");
            return el ? el.value : null;
        };

        attachAutocomplete(input, function (place) {
            applyUpdateDeliveryPlace(addressId, place);
        }, getter);
    }

    function initDynamicBillingAutocomplete(addressId) {
        debug("initDynamicBillingAutocomplete", addressId);
        ensureStyle();

        var input = document.getElementById("update_billingAddress_" + addressId);
        var getter = function () {
            var el = document.querySelector("#update_billingCountry_" + addressId + " #countryManually");
            return el ? el.value : null;
        };

        attachAutocomplete(input, function (place) {
            applyUpdateBillingPlace(addressId, place);
        }, getter);
    }

    window.initMap = function () {
        debug("window.initMap called");
    };

    window.searchDeliveryAddress = function () {
        debug("window.searchDeliveryAddress called");
    };

    window.searchBillingAddress = function () {
        debug("window.searchBillingAddress called");
    };

    window.searchUpdateDeliveryAddress = function (addressId) {
        debug("window.searchUpdateDeliveryAddress called", addressId);
        initDynamicDeliveryAutocomplete(addressId);
    };

    window.searchUpdateBillingAddress = function (addressId) {
        debug("window.searchUpdateBillingAddress called", addressId);
        initDynamicBillingAutocomplete(addressId);
    };

    function isAddressInput(target) {
        return !!target && target.tagName === "INPUT" && (
            target.id === "registerSearchAddress" ||
            target.id === "address1" ||
            target.id === "billingAddress"
        );
    }

    function getDeliveryCountryGetter() {
        return function () {
            var el = document.querySelector("#registerCountry #countryManually");
            return el ? el.value : null;
        };
    }

    function getBillingCountryGetter() {
        return function () {
            var el = document.querySelector("#billingCountry #countryManually");
            return el ? el.value : null;
        };
    }

    function ensureMenuForInput(input) {
        if (!input) return;
        if (!input._kpMenu || !document.body.contains(input._kpMenu)) {
            input.dataset.kpWrapped = "";
            wrapInput(input);
        }
    }

    function ensureAutocompleteInitialized(input) {
        if (!input) return;

        if (input.id === "billingAddress") {
            attachAutocomplete(input, applyBillingPlace, getBillingCountryGetter());
        } else {
            attachAutocomplete(input, applyDeliveryPlace, getDeliveryCountryGetter());
        }

        ensureMenuForInput(input);
    }

    document.addEventListener("DOMContentLoaded", function () {
        debug("DOMContentLoaded");
        initStaticAutocomplete();

        document.addEventListener("focusin", function (e) {
            if (!isAddressInput(e.target)) return;
            debug("delegated focusin", e.target.id, e.target.value);
            ensureAutocompleteInitialized(e.target);
        });

        document.addEventListener("input", function (e) {
            if (!isAddressInput(e.target)) return;
            debug("delegated input", e.target.id, e.target.value);
            ensureAutocompleteInitialized(e.target);
        });

        document.addEventListener("click", function (e) {
            var menus = document.querySelectorAll(".kp-autocomplete-menu");
            menus.forEach(function (menu) {
                if (menu.parentNode && !menu.parentNode.contains(e.target)) {
                    hideMenu(menu);
                }
            });
        });

        if (window.jQuery) {
            window.jQuery(document.body).on("updated_checkout", function () {
                debug("WooCommerce updated_checkout fired");
                initStaticAutocomplete();
            });

            window.jQuery(document.body).on("country_to_state_changed", function () {
                debug("WooCommerce country_to_state_changed fired");
                initStaticAutocomplete();
            });
        }

        setInterval(function () {
            var input = document.querySelector("#registerSearchAddress");
            if (input) {
                ensureAutocompleteInitialized(input);
            }
        }, 2000);
    });
})();
