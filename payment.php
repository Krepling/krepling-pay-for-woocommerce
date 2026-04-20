<?php
defined('ABSPATH') || exit;

if (!function_exists('krepling_wc_session_get')) {
    require_once __DIR__ . '/krepling-session.php';
}
$krepling_total = number_format((float)WC()->cart->total, 2, '.', '');
$krepling_subtotal = number_format((float)WC()->cart->subtotal, 2, '.', '');
$krepling_shipping = number_format((float)WC()->cart->shipping_total, 2, '.', '');
$krepling_discount = number_format((float)WC()->cart->get_cart_discount_total(), 2, '.', '');
$krepling_tax = number_format((float)WC()->cart->total_tax, 2, '.', '');
$krepling_cart_data = WC()->cart->get_cart();

// Get the path of uploaded logo
$krepling_logo_file = get_option('woocommerce_krepling_logo');
$krepling_config_data = get_option( 'woocommerce_krepling_settings' );
$krepling_plugin_text_color = esc_attr($krepling_config_data['plugin-text-color'] ?? '');
$krepling_plugin_button_color = esc_attr($krepling_config_data['plugin-btn-color'] ?? '');
$krepling_plugin_bg_color = esc_attr($krepling_config_data['plugin-bg-color'] ?? '');
$krepling_plugin_input_color = esc_attr($krepling_config_data['plugin-input-color'] ?? '');
$krepling_logo_url = !empty($krepling_logo_file) ? esc_url($krepling_logo_file) : '';
$krepling_plugin_dir_url = esc_url(plugin_dir_url(__FILE__));
$krepling_remembered_email = sanitize_email((string) krepling_cookie_value('kp_user_email', ''));
$krepling_logged_in_email = krepling_wc_session_login_email();
$krepling_wc_currency_name = get_woocommerce_currency();
$krepling_wc_currency_symbol = get_woocommerce_currency_symbol($krepling_wc_currency_name);
$krepling_wc_currency_and_symbol = $krepling_wc_currency_name . '(' . $krepling_wc_currency_symbol . ')';
$krepling_default_currency_and_symbol = krepling_wc_session_get('defaultCurrencyAndSymbol') && krepling_wc_session_get('defaultCurrencyAndSymbol') !== 'undefined'
    ? sanitize_text_field(wp_unslash((string) krepling_wc_session_get('defaultCurrencyAndSymbol')))
    : $krepling_wc_currency_and_symbol;
$krepling_default_currency_symbol = krepling_wc_session_get('defaultCurrencySymbol') && krepling_wc_session_get('defaultCurrencySymbol') !== 'undefined'
    ? sanitize_text_field(wp_unslash((string) krepling_wc_session_get('defaultCurrencySymbol')))
    : $krepling_wc_currency_symbol;
$krepling_default_currency_name = krepling_wc_session_get('defaultCurrencyName') && krepling_wc_session_get('defaultCurrencyName') !== 'undefined'
    ? sanitize_text_field(wp_unslash((string) krepling_wc_session_get('defaultCurrencyName')))
    : $krepling_wc_currency_name;
$krepling_list_currency = krepling_wc_session_list_currency();
$krepling_user_detail = krepling_wc_session_user_detail();

if (!krepling_wc_session_get('defaultCurrencySymbol') || krepling_wc_session_get('defaultCurrencySymbol') === 'undefined') {
    krepling_wc_session_set('defaultCurrencySymbol', $krepling_default_currency_symbol);
}

if (!krepling_wc_session_get('defaultCurrencyAndSymbol') || krepling_wc_session_get('defaultCurrencyAndSymbol') === 'undefined') {
    krepling_wc_session_set('defaultCurrencyAndSymbol', $krepling_default_currency_and_symbol);
}

if (!krepling_wc_session_get('defaultCurrencyName') || krepling_wc_session_get('defaultCurrencyName') === 'undefined') {
    krepling_wc_session_set('defaultCurrencyName', $krepling_default_currency_name);
}

if (!function_exists('krepling_render_currency_options')) {
    function krepling_render_currency_options($krepling_data_currency)
    {
        $html = '';
        foreach ((array) $krepling_data_currency as $groupKey => $innerArray) {
            if ($groupKey === 'second') {
                $html .= '<div class="dropHead">A-Z</div>';
            }

            foreach ((array) $innerArray as $value) {
                $currencyName = isset($value['currencyName']) ? sanitize_text_field((string) $value['currencyName']) : '';
                $symbol = isset($value['symbol']) ? sanitize_text_field((string) $value['symbol']) : '';
                $html .= sprintf(
                    '<li id="%1$s" onclick="updateCurrencyPrice(%2$s,%3$s)"><a>%4$s</a></li>',
                    esc_attr($symbol),
                    wp_json_encode($currencyName),
                    wp_json_encode($symbol),
                    esc_html($currencyName . '(' . $symbol . ')')
                );
            }
        }

        return $html;
    }
}
?>
<?php if(!empty($krepling_default_currency_symbol) && $krepling_default_currency_symbol !== 'undefined' ){ ?>
<div class="checkout_wraper move_anim">
    <div class="loader_overlay"></div>
    <div class="card-1 checkout_popup" id="krepling_paymentgateway"> 
        <?php if(isset($krepling_user_detail) && !empty($krepling_user_detail)){ ?>
            <div id="stepFourId" class="checkout_wraper">
                <div class="logedIn_checkout move_anim">
                    <div class="card-header2 card3">
                        <div class="card-svg">
                            <div class="backBtn">
                            <?php if(empty($krepling_logo_file)){ ?>
                                <div class="svglogo">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="470.000000pt" height="210.000000pt" viewBox="0 0 470.000000 210.000000" preserveAspectRatio="xMidYMid meet">
                                        <g transform="translate(0.000000,210.000000) scale(0.100000,-0.100000)" stroke="none">
                                            <path d="M875 2085 c-430 -81 -755 -394 -852 -820 -20 -88 -23 -326 -5 -410 62 -287 220 -522 455 -679 296 -197 658 -229 988 -87 119 51 219 120 320 221 400 394 423 999 55 1430 -61 71 -91 90 -146 90 l-44 0 -354 -353 c-331 -331 -354 -356 -359 -394 -12 -93 -6 -102 195 -305 l187 -188 138 0 c75 0 137 3 137 7 0 4 -100 108 -222 230 l-223 223 276 276 276 276 42 -58 c57 -79 107 -184 132 -280 31 -112 31 -316 0 -428 -65 -243 -224 -438 -446 -546 -119 -58 -197 -78 -327 -87 -342 -22 -663 168 -812 480 -55 114 -75 194 -83 319 -21 344 171 667 482 814 137 64 205 78 370 78 121 -1 151 -4 225 -27 l85 -26 75 74 74 74 -56 26 c-155 71 -413 102 -583 70z" />
                                            <path d="M660 1050 l0 -460 100 0 100 0 0 460 0 460 -100 0 -100 0 0 -460z" />
                                            <path d="M2760 1050 l0 -320 80 0 80 0 0 255 0 255 74 0 c91 0 132 -14 151 -51 19 -36 19 -62 0 -100 -18 -33 -50 -49 -100 -49 -62 0 -94 -59 -59 -108 13 -19 24 -22 78 -22 81 0 141 22 188 67 45 44 61 88 62 168 0 109 -58 187 -159 214 -22 6 -120 11 -217 11 l-178 0 0 -320z" />
                                            <path d="M3645 1361 c-48 -21 -76 -73 -184 -340 -61 -151 -111 -278 -111 -283 0 -5 39 -7 86 -6 l86 3 89 214 c49 118 90 216 92 218 3 2 18 -30 35 -72 l31 -75 -27 -6 c-56 -14 -112 -76 -112 -124 0 -6 29 -10 70 -10 105 0 133 -15 161 -89 l24 -61 89 0 c66 0 87 3 83 13 -240 608 -245 618 -338 623 -30 2 -63 0 -74 -5z" />
                                            <path d="M4100 1364 c0 -3 44 -79 97 -167 54 -90 103 -184 110 -212 7 -27 13 -96 13 -152 l0 -103 80 0 80 0 0 113 c1 141 9 166 127 370 l92 158 -97 -3 -97 -3 -47 -79 c-25 -43 -50 -82 -56 -88 -6 -6 -25 18 -52 64 -66 112 -60 108 -162 108 -48 0 -88 -3 -88 -6z" />
                                        </g>
                                    </svg>
                                </div>
                            <?php } else { ?>
                                <img src="<?php echo  esc_url($krepling_logo_url); ?>" alt="Krepling logo">
                            <?php } ?>
                            </div>
                        </div>
                        <div class="heading-1 heading-2 align-items-center card-heading-list">
                            <input type="hidden" id="selected_currency">
                            <div>
                                <h2 class="checkout-card-title">Checkout</h2>
                                <div class="card-header-logout">
                                    <div class="login_credentials">
                                        <div class="checkout-card-head">
                                            <div>
                                                <label class="m-0" id="login_email">
                                                    <?php 
                                                    if(!empty($krepling_logged_in_email)){
                                                        echo esc_html($krepling_logged_in_email);
                                                    }
                                                    ?>
                                                </label>
                                            </div>
                                            <span class="setting_mark"> &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;</span>
                                            <div>
                                                <a id="krepling_settings">Settings</a>
                                            </div>
                                        </div>
                                        <ul class="accordion">
                                            <li>
                                                <div class="link currency_dropdown"><span id="login_link"><?php if(!empty($krepling_default_currency_and_symbol)) { echo esc_html($krepling_default_currency_and_symbol);}?></span>
                                                    <i class="rotatearrow">
                                                    <svg width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2.31592 2.18311L6.94459 6.37095L11.5733 2.18311" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="2.7772" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    </i>
                                                </div>
                                                <?php if(!empty($krepling_list_currency['first'])){?>
                                                <ul class="submenu" id="login_submenu" style="display:none">
                                                    <input type="text" placeholder="Search..." class="form-control" id="searchCurrency" onkeyup="searchCurrencyFunction()">
                                                    <div class="star"><i class="fa fa-star" aria-hidden="true"></i></div>
                                                    <?php 
                                                    $krepling_data_currency = $krepling_list_currency;
                                                    echo wp_kses_post(krepling_render_currency_options($krepling_data_currency)); ?>
                                                </ul>
                                                <?php } ?>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="signin_section toster_msg_section hideclass">
                                        <div class="common_toster">
                                            <span class="toster_message"></span>
                                            <span><i class="fa fa-times"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="stepFirstId" class="payment-form">
                        <!-- price list toggle -->
                        <div class="pannel-carddetails-sec">
                            <div class="row cart cart_input dropdown-toggle" id="flipDivId" style="display: flex;">
                                <div class="cart-1 shopping-card-icon">
                                    <svg width="21" height="24" viewBox="0 0 21 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20.3632 22.546L19.6333 0.660004C19.6276 0.482966 19.5532 0.315105 19.4259 0.191925C19.2986 0.0687454 19.1284 -9.20898e-05 18.9513 9.24636e-08H1.4133C1.23617 -9.20898e-05 1.06585 0.0687454 0.938565 0.191925C0.81128 0.315105 0.737004 0.482966 0.73129 0.660004L0.000210958 22.582C-0.00205554 22.673 0.013963 22.7636 0.047208 22.8484C0.0804531 22.9331 0.130271 23.0104 0.193814 23.0756C0.257358 23.1408 0.333326 23.1927 0.417203 23.2281C0.50108 23.2636 0.591161 23.2819 0.682218 23.282H19.6822C19.8631 23.282 20.0366 23.2101 20.1645 23.0822C20.2924 22.9543 20.3642 22.7809 20.3642 22.6C20.3652 22.585 20.3642 22.566 20.3632 22.546ZM1.3873 21.923L2.07321 1.36401H18.2933L18.9782 21.923H1.3873Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                        <path d="M14.5671 4.38478C14.4775 4.38465 14.3888 4.40217 14.3059 4.43638C14.223 4.4706 14.1477 4.52084 14.0842 4.58418C14.0208 4.64752 13.9705 4.72274 13.9362 4.80555C13.9018 4.88836 13.8842 4.97711 13.8842 5.06675C13.8842 6.04805 13.4943 6.98917 12.8004 7.68306C12.1065 8.37694 11.1655 8.76677 10.1842 8.76677C9.2029 8.76677 8.26176 8.37694 7.56787 7.68306C6.87399 6.98917 6.48413 6.04805 6.48413 5.06675C6.47737 4.89026 6.40252 4.72326 6.27527 4.60078C6.14801 4.4783 5.97825 4.40989 5.80164 4.40989C5.62502 4.40989 5.45526 4.4783 5.328 4.60078C5.20075 4.72326 5.1259 4.89026 5.11914 5.06675C5.11914 6.41061 5.65302 7.69942 6.60327 8.64967C7.55352 9.59992 8.84231 10.1338 10.1862 10.1338C11.53 10.1338 12.8188 9.59992 13.769 8.64967C14.7193 7.69942 15.2532 6.41061 15.2532 5.06675C15.2532 4.97685 15.2355 4.88786 15.2009 4.80485C15.1664 4.72185 15.1158 4.64649 15.052 4.58311C14.9882 4.51973 14.9126 4.46957 14.8293 4.43553C14.7461 4.40149 14.657 4.38425 14.5671 4.38478Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                    </svg>
                                    <p>Total: 
                                        <span class="total_cart_amount">
                                            <label id="lblAmountId"> 
                                                <?php echo wp_kses_post('<span class="current_currency_type">' . esc_html($krepling_default_currency_symbol) . '</span> ' . esc_html($krepling_total))?>
                                            </label>
                                        </span>
                                    </p>

                                </div>
                                <div class="quantity-1">
                                    <div class="rotate">
                                        <svg width="29" height="17" viewBox="0 0 29 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 2.06836L14.5 14.5684L27 2.06836" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div id="panelproductId" class="cart  dropdown">
                                <h3>Order Summary</h3>
                                <table class="table table-xs">
                                    <tbody>
                                        <?php if(!empty($krepling_cart_data)){
                                            foreach( $krepling_cart_data as $krepling_cart_item ) { 
                                                $krepling_product_id = $krepling_cart_item['product_id'];
                                                $krepling_product = wc_get_product( $krepling_product_id );
                                                $krepling_show_total = number_format((float) $krepling_cart_item['quantity'] * $krepling_product->get_price(), 2, '.', '');
                                                ?>
                                                <tr class="item-row">
                                                    <td class="product-item">
                                                        <?php echo wp_kses_post($krepling_product->get_image());?>
                                                    </td>
                                                    <td class="product-name">
                                                        <p><?php echo esc_html($krepling_cart_item['data']->get_name());?></p>
                                                        <p>Price: <b aria-hidden="true" class="order_summary_price_symbol"><?php echo esc_html($krepling_default_currency_symbol)?></b><strong><?php echo esc_html(number_format((float) $krepling_product->get_price(), 2, '.', ''))?></strong></p>
                                                    </td>
                                                    <td class="text-left product_quantity" title="quantity">
                                                        <strong class="ProductPrice">
                                                            <div class="productQuantity">
                                                                <span class="qnt_back"><?php echo esc_html((string) $krepling_cart_item['quantity'])?></span>
                                                            </div>
                                                        </strong>
                                                    </td>
                                                    <td class="text-right" title="price">
                                                        <strong class="ProductPrice">
                                                                <span class="current_currency_type"><?php echo esc_html($krepling_default_currency_symbol)?><b class="product_price"><?php echo esc_html($krepling_show_total)?></b></span><br> 
                                                        </strong>
                                                    </td>
                                                </tr>
                                        <?php } }?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row info">
                                            <td class="text-left" colspan="3">Subtotal</td>
                                            <td class="text-right cart_subtotal">
                                                <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_subtotal); ?></span>
                                            </td>
                                        </tr>
                                        <?php if(isset($krepling_discount) && $krepling_discount > 0){ ?>
                                        <tr class="total-row info">
                                            <td class="text-left" colspan="3">Discount</td>
                                            <td class="text-right cart_discount">
                                                <span><i class="fa fa current_currency_type" aria-hidden="true">-<?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_discount); ?></span>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        <?php if(isset($krepling_shipping) && $krepling_shipping > 0){ ?>
                                        <tr class="total-row info">
                                            <td class="text-left" colspan="3">Shipping</td>
                                            <td class="text-right cart_shipping">
                                                <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_shipping); ?></span>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        <?php if(isset($krepling_tax) && $krepling_tax > 0){ ?>
                                        <tr class="total-row info">
                                            <td class="text-left" colspan="3">Tax</td>
                                            <td class="text-right cart_tax">
                                                <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_tax); ?></span>
                                            </td>
                                        </tr> 
                                        <?php } ?> 
                                        <tr class="total-row info">
                                            <td class="text-left" colspan="3"><strong>Total</strong></td>
                                            <td class="text-right cart_total">
                                                <strong>
                                                    <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_total); ?></span>
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- Start delivery address panel -->
                        <div class="form-group delivery_address">
                            <div class="wrapper center-block">
                                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab" id="headingThree">
                                            <h4 class="panel-title">
                                                <a id="hide-delivery" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                                                    Delivery Address
                                                    <svg width="26" height="15" viewBox="0 0 26 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2 2L13 13L24 2" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>
                                            </h4>
                                        </div>
                                        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                                            <!-- Deleted address-->
                                            <div class="toasts" id="deleted_address_popup" style="display: none;">
                                                <div class="flex_group">
                                                    <div class="toast__content">
                                                        <p class="toast__message" id="delete_address_text"></p>
                                                    </div>
                                                    <div class="undo_messsage" id="undo_delete_address">
                                                        <span><a>Undo</a></span>
                                                    </div>
                                                    <div class="toast__close">
                                                        <span class="close_btn" id="close_delete_address_btn">×</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Deleted address end-->
                                            <div class="address_section toster_msg_section hideclass">
                                                <div class="common_toster">
                                                    <span class="toster_message"></span>
                                                    <span><i class="fa fa-times"></i></span>
                                                </div>
                                            </div>
                                            <div class="panel-body d-flex justify-content-between">
                                                <div class="radio-new-button-animation">
                                                    <?php if(!empty($krepling_user_detail)){ ?>
                                                        <form id="address_form_div" class="my-radio">
                                                            <?php 
                                                            $krepling_default_address = '';
                                                            foreach($krepling_user_detail->userVM->checkoutAddress as $krepling_user_info){ 
                                                                $krepling_user_info_id = absint($krepling_user_info->id);
                                                                $krepling_user_info_user_id = absint($krepling_user_info->userId);
                                                                $krepling_address = sanitize_text_field(trim((string) $krepling_user_info->streetAddress1 . ' ' . (string) $krepling_user_info->streetAddress2));
                                                                $krepling_user_info_country = sanitize_text_field((string) $krepling_user_info->country);
                                                                $krepling_user_info_state = sanitize_text_field((string) $krepling_user_info->state);
                                                                $krepling_user_info_billing_country = sanitize_text_field((string) $krepling_user_info->billingCountry);
                                                                $krepling_user_info_billing_state = sanitize_text_field((string) $krepling_user_info->billingState);
                                                                if($krepling_user_info->isDefault==true){
                                                                    $krepling_default_address = $krepling_address;
                                                                } ?>
                                                                <!-- <span> -->
                                                                <input id="address_<?php echo absint($krepling_user_info_id); ?>" type="radio" value="<?php echo absint($krepling_user_info_id); ?>" name="radiosButton" <?php checked((bool) $krepling_user_info->isNewAddress, true); ?> >
                                                                <div class="carddetails-address-infolist d-flex justify-content-between" id="address_div_<?php echo absint($krepling_user_info_id); ?>">
                                                                    <label for="address_<?php echo absint($krepling_user_info_id); ?>" id="label_<?php echo absint($krepling_user_info_id); ?>"><?php echo esc_html($krepling_address)?></label>
                                                                    <div class="icons-collapse d-flex">
                                                                    <?php if($krepling_user_info->isNewAddress == true){ ?>
																	        <span class="defaul_message">New</span>
                                                                        <?php } ?>
                                                                        <?php if($krepling_user_info->isDefault == true){ ?>
																	        <span class="defaul_message">Set to default</span>
                                                                        <?php } ?>
                                                                        <a href="#" onclick="removeAddress(<?php echo absint($krepling_user_info_id); ?>, <?php echo absint($krepling_user_info_user_id); ?>, <?php echo wp_json_encode($krepling_address)?>); return false;"><i class="fa fa-trash" aria-hidden="true"></i></a> 
                                                                        <div class="add-delivery-withplus" data-toggle="modal" data-target="#EditAddressPopup">
                                                                            <a data-toggle="modal" data-target="#EditAddressPopup<?php echo absint($krepling_user_info_id); ?>" onclick="showSelectedCountry(<?php echo wp_json_encode($krepling_user_info_country)?>, <?php echo wp_json_encode($krepling_user_info_state)?>, <?php echo absint($krepling_user_info_id); ?>, <?php echo wp_json_encode($krepling_user_info_billing_country)?>, <?php echo wp_json_encode($krepling_user_info_billing_state)?>)"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                                                                        </div> 
                                                                        <a href="#" onclick="setDefaultAddress(<?php echo absint($krepling_user_info_id); ?>, <?php echo absint($krepling_user_info_user_id); ?>, <?php echo wp_json_encode($krepling_address)?>); return false;"><i class="fa fa-star default-fav" aria-hidden="true" id="setColorDefault_<?php echo absint($krepling_user_info_id); ?>" <?php if($krepling_user_info->isDefault==true){ echo 'style="color:' . esc_attr($krepling_plugin_button_color) . '"';}?>></i></a>
                                                                    </div>
                                                                </div>
                                                                <!-- </span> -->
                                                            <?php } ?>
                                                        </form>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            
                                            <div class="add-delivery-withplus" data-toggle="modal fade" data-target="#AddAddressPopup" id="addNewAddressPopup">
                                                <a data-toggle="modal" data-target="#AddAddressPopup"><i class="fa fa-plus" aria-hidden="true"></i>Add delivery address</a>
                                            </div>
                                        </div>
                                        <div class="delivery-Address-content">
                                            <h6 id="default_address"><?php echo isset($krepling_default_address) ? esc_html($krepling_default_address) : ''?></h6>
                                        </div>
                                    </div> 
                                </div>
                            
                                <div id="AddAddressPopup" class="modal" role="dialog">
                                    <div class="modal-dialog editbtn-dialog">
                                        <!-- Modal content-->
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Add Address</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body editmodel-popup">
                                                <!-- card screen delivery address popup -->
                                                <form id="adddeliveryform" class="address-form">
                                                    <input type="hidden" id="registerSearchAddress">
                                                    <label for="address1">Address Line 1 </label>
                                                    <input type="text" class="form-control filladdress-input" id="address1" placeholder="Street Address" autocomplete="off" maxlength="20">
                                                    <span class="hideclass" id="deliveryAddressError"></span>

                                                    <!--Add additional information  name-->
                                                    <div class="add_additional_shippingAddress" >
                                                        <button type="button" class="btn addcompany_button common_btn" id="additional_shippingAddress" data-toggle="modal" data-target="">+ Add an additional shipping address</button>
                                                    </div>
                                                    <div class="optional_shipping_address" style="display:none">
                                                        <label for="address2">Address Line 2 (Optional)</label>
                                                        <input type="text" class="form-control mt-2 filladdress-input" id="address2" placeholder="Street Address (Optional)" autocomplete="on" maxlength="20">
                                                    </div>
                                                    <!--Add additional information  end-->
                                                    
                                                    <div class="popupaddress mb-21">
                                                     
                                                        <div class="select_country mb-21">
                                                            <label for="country1">Country </label>
                                                            <div class="state-select popupcountryselect country" id="country1">
                                                                <div class="select-state country" id="registerCountry"></div>
                                                                <span class="hideclass" id="countryError"></span>                                           
                                                            </div>
                                                        </div>
														
														 <div class="statezipcode">
                                                            <label for="registerZip">Zip Code </label>
                                                            <input type="text" class="form-control zip2" id="registerZip" placeholder=" e.g. 12345" autocomplete="on" maxlength="20" >
                                                            <span class="hideclass" id="zipError"></span>                                           
                                                        </div>
                                                    </div>
                                                    <div class="popupaddress" id="stateZip">
                                                       
                                                        <div class="addstate mb-21">
                                                            <label for="registerState">State </label>
                                                            <div class="state-select stateDropdown">
                                                                <input type="text" class="form-control state2" id="registerState" placeholder="State" autocomplete="on" >
                                                            </div>
                                                            <span class="hideclass" id="stateError"></span>                                           
                                                        </div>
														
														
														   <div class="addcity">
                                                            <label for="registerCity">City </label>
                                                            <input type="text" class="form-control mt-2 filladdress-input city2" id="registerCity" placeholder="City" autocomplete="on" maxlength="20">
                                                            <span class="hideclass" id="cityError"></span>                                           
                                                        </div>
                                                    </div>                                                
                                                </form>
                                                <!--billing_checkbox-->
                                                <div class="billing_checkbox">
                                                    <div class="biling_flex">
                                                        <input type="checkbox" class="checkbox-round custom_checkbox" id="signupBillingCheckbox" checked="checked">
                                                    </div>
                                                    <div class="biling_flex">
                                                        <p>Billing address same as shipping</p>
                                                    </div>
                                                </div>
                                                <!--billing_checkbox end-->

                                                <form id="billingAddressForm" class="address-form2" style="display:none">
                                                    <label for="billingAddress">Address Line 1</label>
                                                    <input type="text" class="form-control filladdress-input" id="billingAddress" placeholder="Street Address" autocomplete="off" maxlength="20">
                                                    <span class="hideclass" id="billingAddressError"></span>

                                                    <!--Add additional information  name-->
                                                    <div class="add_additional_billingAddress" >
                                                        <button type="button" class="btn addcompany_button common_btn" id="additional_billingAddress" data-toggle="modal" data-target="">+ Add an additional billing address</button>
                                                    </div>
                                                    <div class="optional_billing_address" style="display:none">
                                                        <label for="billingAddress1">Address Line 2 (Optional)</label>
                                                        <input type="text" class="form-control mt-2 filladdress-input" id="billingAddress1" placeholder="Street Address (Optional)" autocomplete="on" maxlength="20">
                                                    </div>
                                                    <!--Add additional information  end-->

                                                    <div class="blling_popupaddress mb-21">
                                                     
                                                        <div class="select_country mb-21">
                                                            <label for="billingCountry">Country </label>
                                                            <div class="state-select country1" id="billingCountry"></div>
                                                            <span class="hideclass" id="billingCountryError"></span>
                                                        </div>
														
														 <div class="statezipcode">
                                                            <label for="billingZip">Zip Code </label>
                                                            <input type="text" class="form-control zip1" id="billingZip" placeholder="e.g. 12345" autocomplete="on" maxlength="10">
                                                            <span class="hideclass" id="billingZipError"></span>
                                                        </div>
                                                    </div>
                                                    <div class="blling_popupaddress">
                                                       
                                                        <div class="addstate mb-21">
                                                            <label for="billingState">State </label>
                                                            <div class="state1 state-select billingStateDropdown">
                                                                <input type="text" class="form-control state1" id="billingState" placeholder="State" autocomplete="on">
                                                            </div>
                                                            <span class="hideclass" id="billingStateError"></span>
                                                        </div>
														
														   <div class="addcity">
                                                            <label for="billingCity">City </label>
                                                            <input type="text" class="form-control mt-2 filladdress-input city1" id="billingCity" placeholder="City" autocomplete="on" maxlength="20">
                                                            <span class="hideclass" id="billingCityError"></span>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- card screen delivery address popup -->
                                            </div>
                                            <div class="add_address_section toster_msg_section hideclass">
                                                <div class="common_toster">
                                                    <span class="toster_message"></span>
                                                    <span><i class="fa fa-times"></i></span>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default modalclosebtn" data-dismiss="modal">Cancel</button>
                                                <button type="button" id="addaddressbtn" class="btn btn-info update-btn-edit">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php foreach($krepling_user_detail->userVM->checkoutAddress as $krepling_user_info){
                                    $krepling_user_info_id = absint($krepling_user_info->id);
                                    $krepling_user_info_user_id = absint($krepling_user_info->userId);
                                    $krepling_optional_shipping_visible = ((string) $krepling_user_info->streetAddress2 === '') ? 'none' : 'block';
                                    $krepling_optional_billing_visible = ((string) $krepling_user_info->billingAddress2 === '') ? 'none' : 'block';
                                    ?>
                                    <div id="editAddressPopup_<?php echo absint($krepling_user_info_id); ?>" class="modal editmodal" role="dialog">
                                        <div class="modal-dialog editbtn-dialog">
                                            <!-- Modal content-->
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title editaddresstitle">Edit Address</h4>
                                                    <button type="button" class="close close_edit_address" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body editmodel-popup">
                                                    <h5 id="streetaddresses" readonly="readonly"></h5>                                   
                                                    <form id="updateDeliveryForm" class="address-form">
                                                        <label for="newstreetaddress_line1_<?php echo absint($krepling_user_info_id); ?>">Address Line 1 </label>
                                                        <input type="text" class="form-control filladdress-input" placeholder="Street Address" id="newstreetaddress_line1_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->streetAddress1)?>" autocomplete="off" maxlength="20" onkeyup="searchUpdateDeliveryAddress(<?php echo absint($krepling_user_info_id); ?>)">
                                                        <ul id="suggestions"></ul>
                                                        <span class="hideclass" id="updateDeliveryAddressError_<?php echo absint($krepling_user_info_id); ?>"></span>                                                
                                                        <!--Add additional information name-->
                                                        <?php if($krepling_user_info->streetAddress2 == '') { ?>
                                                            <div class="add_additional_shippingAddress">
                                                                <button type="button" class="btn addcompany_button common_btn additional_shippingAddress" data-toggle="modal" data-target="">+ Add an additional shipping address</button>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="optional_shipping_address" style="display: <?php echo esc_attr($krepling_optional_shipping_visible)?>" >
                                                            <label for="newstreetaddress_line2_<?php echo absint($krepling_user_info_id); ?>">Address Line 2 (Optional) </label>
                                                            <input type="text" class="form-control mt-2 filladdress-input" placeholder="Street Address (Optional)" id="newstreetaddress_line2_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->streetAddress2)?>" autocomplete="on" maxlength="20">
                                                        </div>
                                                        <!--Add additional information end-->                                                       
                                                        <div class="edit_popupaddress mb-21">
                                                            <div class="select_country mb-21">
                                                                <label for="update_country_<?php echo absint($krepling_user_info_id); ?>">Country </label>
                                                                <div class="state-select country2 update_country" id="update_country_<?php echo absint($krepling_user_info_id); ?>" onChange="updateDeliveryState(<?php echo absint($krepling_user_info_id); ?>)"></div>
                                                                <span class="hideclass" id="updateDeliveryCountryError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
															<div class="statezipcode">
                                                                <label for="uzip_<?php echo absint($krepling_user_info_id); ?>">Zip Code </label>
                                                                <input type="text" class="form-control zip3" id="uzip_<?php echo absint($krepling_user_info_id); ?>" placeholder=" Zip Code" value="<?php echo esc_attr((string) $krepling_user_info->zipCode)?>" autocomplete="on" maxlength="20">
                                                                <span class="hideclass" id="updateDeliveryZipError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
                                                        </div>
                                                        <div class="edit_popupaddress mb-21" id="stateZip">
                                                            <div class="addstate mb-21">
                                                                <label for="update_stateDropdown_<?php echo absint($krepling_user_info_id); ?>">State </label>
                                                                <div class="state-select popupstate" id="edit_state_<?php echo absint($krepling_user_info_id); ?>"></div>
                                                                <span class="hideclass" id="updateDeliveryStateError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
															<div class="addstate mb-20">
                                                                <label for="ucity_<?php echo absint($krepling_user_info_id); ?>">City </label>
                                                                <input type="text" class="form-control mt-2 filladdress-input city3" placeholder="City" id="ucity_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->city)?>" autocomplete="on" maxlength="20">
                                                                <span class="hideclass" id="updateDeliveryCityError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <!--billing_checkbox-->
                                                    <div class="billing_checkbox">
                                                        <div class="biling_flex">
                                                            <input type="checkbox" class="checkbox-round sameBillingShippingUpdate custom_checkbox" onChange="changeBillingCheckbox('<?php echo absint($krepling_user_info_id); ?>')" id="sameBillingShippingUpdate_<?php echo absint($krepling_user_info_id); ?>" <?php echo checked((bool) $krepling_user_info->billingAddress, true, false) ?>>
                                                        </div>
                                                        <div class="biling_flex" onClick="changeBillingCheckboxText('<?php echo absint($krepling_user_info_id); ?>')">
                                                            <p>Billing address same as shipping</p>
                                                        </div>
                                                    </div>
                                                    <!--billing_checkbox end-->
                                                    <form id="updateBillingForm_<?php echo absint($krepling_user_info_id); ?>" class="address-form2" style="display:none">
                                                        <label for="update_billingAddress_<?php echo absint($krepling_user_info_id); ?>">Address Line 1</label>
                                                        <input type="text" class="form-control filladdress-input" id="update_billingAddress_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->billingAddress1)?>" placeholder="Street Address" autocomplete="off" maxlength="20" onkeyup="searchUpdateBillingAddress(<?php echo absint($krepling_user_info_id); ?>)">
                                                        <span class="hideclass" id="updateBillingAddressError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                        <!--Add additional information name-->
                                                        <?php if($krepling_user_info->billingAddress2 == '') { ?>
                                                            <div class="add_additional_billingAddress">
                                                                <button type="button" class="btn addcompany_button common_btn additional_billingAddress" data-toggle="modal" data-target="">+ Add an additional billing address</button>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="optional_billing_address" style="display: <?php echo esc_attr($krepling_optional_billing_visible)?>" >
                                                            <label for="update_billingAddress1_<?php echo absint($krepling_user_info_id); ?>">Address Line 2 (Optional)</label>
                                                            <input type="text" class="form-control mt-2 filladdress-input" id="update_billingAddress1_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->billingAddress2)?>" placeholder="Street Address (Optional)" autocomplete="on" maxlength="20">
                                                        </div>
                                                        <!--Add additional information end-->

                                                        <div class="blling_edit_popupaddress mb-21">
                                                            <div class="select_country mb-21">
                                                                <label for="update_billingCountry_<?php echo absint($krepling_user_info_id); ?>">Country </label>
                                                                <div class="state-select country1 update_billingCountry" id="update_billingCountry_<?php echo absint($krepling_user_info_id); ?>" onChange="updateBillingState(<?php echo absint($krepling_user_info_id); ?>)"></div>
                                                                <span class="hideclass" id="updateBillingCountryError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
															<div class="statezipcode">
                                                                <label for="update_billingZip_<?php echo absint($krepling_user_info_id); ?>">Zip Code </label>
                                                                <input type="text" class="form-control zip1" id="update_billingZip_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->billingZip)?>" placeholder="e.g. 12345" autocomplete="on" maxlength="10">
                                                                <span class="hideclass" id="updateBillingZipError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
                                                        </div>
                                                        <div class="blling_edit_popupaddress">
                                                            <div class="addstate mb-21">
                                                                <label for="update_billingStateDropdown_<?php echo absint($krepling_user_info_id); ?>">State </label>
                                                                <div class="state1 state-select updateBillingStateDropdown" id="edit_billingState_<?php echo absint($krepling_user_info_id); ?>"></div>
                                                                <span class="hideclass" id="updateBillingStateError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
															<div class="addcity">
                                                                <label for="update_billingCity_<?php echo absint($krepling_user_info_id); ?>">City </label>
                                                                <input type="text" class="form-control mt-2 filladdress-input city1" id="update_billingCity_<?php echo absint($krepling_user_info_id); ?>" value="<?php echo esc_attr((string) $krepling_user_info->billingCity)?>" placeholder="City" autocomplete="on" maxlength="20">
                                                                <span class="hideclass" id="updateBillingCityError_<?php echo absint($krepling_user_info_id); ?>"></span>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="update_address_section toster_msg_section hideclass">
                                                    <div class="common_toster">
                                                        <span class="toster_message"></span>
                                                        <span><i class="fa fa-times"></i></span>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" id="editaddressbtn" class="btn btn-default modalclosebtn" data-dismiss="modal">Close</button>
                                                    <button type="button" id="updateAddressBtn" onclick="updateAddress(<?php echo absint($krepling_user_info_id); ?>,<?php echo absint($krepling_user_info_user_id); ?>)" class="btn btn-info update-btn-edit" data-dismiss="modal">Update</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <!-- End delivery address panel -->
                        <!-- Delete card popup-->
                        <div class="toasts" id="deleted_card_popup" style="display: none;">
                            <div class="flex_group">
                                <div class="toast__content">
                                    <p class="toast__message" id="delete_card_text"></p>
                                </div>
                                <div class="undo_messsage" id="undo_delete_card">
                                    <span><a>Undo</a></span>
                                </div>
                                <div class="toast__close">
                                    <span class="close_btn" id="close_delete_card_btn">×</span>
                                </div>
                            </div>
                        </div>
                        <!-- Delete card popup end-->
                        <div class="paymentCard_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                        <!-- Start payment card section -->
                        <?php if(!empty($krepling_user_detail->paymentMethodVM)) { ?>
                            <div class="row">
                                <div class="heroSlider-fixed col-md-12 p-0">
                                    <!-- Slider -->
                                    <div class="text-white mt-3 card-scroll slider responsive center checkout_slider" id="divPaymentMethodId">
                                        <?php $krepling_count=1;
                                        foreach($krepling_user_detail->paymentMethodVM as $krepling_row){
                                            $krepling_card_type_slug = sanitize_file_name(strtolower((string) $krepling_row->cardType));
                                            $krepling_card_holder_name = sanitize_text_field((string) $krepling_row->cardHolderFirstName);
                                            $krepling_masked_card_number = sanitize_text_field((string) $krepling_row->maskedCardNumber);
                                            $krepling_expiry_date = sanitize_text_field((string) $krepling_row->expiryDate);
                                            $krepling_card_id = sanitize_text_field((string) $krepling_row->cardId);
                                            $krepling_card_number = sanitize_text_field((string) $krepling_row->cardNumber);
                                            $krepling_card_user_id = absint($krepling_row->userId);
                                            ?>
                                            <form id="delete_card_<?php echo absint($krepling_count); ?>" onclick="getCounterNumber(<?php echo absint($krepling_count); ?>, <?php echo absint(strlen((string) $krepling_row->cvvMasking)); ?>)">
                                                <div id="filled-card" class="filled-card <?php echo ($krepling_row->isSetdefaultCard == true) ? 'active_card' : ''?> " >
                                                    <div class="company_logo">
                                                    <h6 id="customerFullNameId1<?php echo absint($krepling_count); ?>"><?php echo esc_html($krepling_card_holder_name); ?></h6>
                                                        <img class="img-card2  img-fluid"  src="<?php echo esc_url($krepling_plugin_dir_url . 'assets/images/' . $krepling_card_type_slug . '.svg')?>" >
                                                    </div>
                                                    <div class="DCcard-img img-wrap">
                                                        <span class="close close-card-alignment"><i class="fa fa-trash-o fnt15" id="deleteUserCard" onclick="deleteCard(<?php echo wp_json_encode($krepling_card_id)?>, <?php echo esc_attr((string) $krepling_card_user_id)?>, <?php echo wp_json_encode($krepling_masked_card_number)?>)"></i></span>
                                                        <div class="width-card item chkpayment"  id="divCardId1">
                                                            <svg  class="card_design" width="116" height="101" viewBox="0 0 116 101" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: <?php echo esc_attr($krepling_plugin_bg_color)?>;">
                                                                <path d="M0 0.5L115.5 101H7C3.13401 101 0 97.866 0 94V0.5Z" fill="currentColor"></path>
                                                                <path d="M1 94V2.6957L112.827 100H7C3.68629 100 1 97.3137 1 94Z" stroke="transparent" stroke-opacity="0.937255" stroke-width="7" style="stroke: <?php echo esc_attr($krepling_plugin_input_color)?>; stroke-width: 3px;"></path>
                                                            </svg>
                                                            <svg class="card_design_mobile" width="229" height="68" viewBox="0 0 247 68" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: <?php echo esc_attr($krepling_plugin_bg_color)?>;">
                                                                <path d="M245.648 1L245.648 60C245.648 63.866 242.514 67 238.648 67L7.64843 67C3.78244 67 0.648435 63.866 0.648435 60L0.648438 0.999989L245.648 1Z" fill="white"stroke-width="7" style="stroke: <?php echo esc_attr($krepling_plugin_input_color)?>; stroke-width: 3px;"></path>
                                                            </svg>
                                                            <div class="slikcard-move">
                                                                <?php if(empty($krepling_logo_file)){ ?>
                                                                    <img src="<?php echo esc_url($krepling_plugin_dir_url . 'assets/images/logo.svg')?>" class="img-card1">
                                                                <?php } else { ?>
                                                                    <img src="<?php echo  esc_url($krepling_logo_url); ?>" class="img-card1" alt="Krepling logo">
                                                                <?php } ?>
                                                                
                                                                <div class="card_exp_group">
                                                                    <span class="card-number-card2" id="spanCardNumberId<?php echo absint($krepling_count); ?>" value="<?php echo esc_attr($krepling_card_number); ?>">**** **** **** <?php echo esc_html($krepling_masked_card_number); ?></span>
                                                                    <span class="card-number-card2 card-margin-details" id="spanExpiryDate<?php echo absint($krepling_count); ?>" value="<?php echo esc_attr($krepling_expiry_date); ?>">Exp: <?php echo esc_html(str_replace('/', '', $krepling_expiry_date)); ?></span>
                                                                </div>
                                                                <input type="hidden" class="card-number-card2" id="spanCardNumberIduser<?php echo absint($krepling_count); ?>" value="<?php echo esc_attr($krepling_card_number); ?>"> 
                                                                <input type="hidden" class="card-number-card3" id="spanCardId<?php echo absint($krepling_count); ?>" value="<?php echo esc_attr($krepling_card_id); ?>"> 
                                                            </div>
                                                        </div>
                                                        <?php if($krepling_row->isNewCard == true){ ?>
                                                            <span class="defaul_new_card">New</span>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php $krepling_count++; } ?>
                                        <div class="add-card-tile" data-toggle="modal" data-target="#addcard">
                                            <div class="card p-2 cardbg-1  item" id="divAddCardId">
                                                <div class="card-top text-center">
                                                    <button type="button" class="circle plus" id="btnAddCardDetailsId" ></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- control arrows -->
                                    <div class="next-prev-icon">
                                        <div class="prev">
                                            <span class="fa fa-chevron-left" aria-hidden="true"></span>
                                        </div>
                                        <div class="next">
                                            <span class="fa fa-chevron-right" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="addcard-body">
                                <div class="add_newcard" id="divCardId">
                                    <div class="mt-2">
                                        <div class="card-information">
                                            <div class="card-info-1">
                                                <div class="card_label-1">
                                                    <label for="paymentCardNumber" class="card_number">Card Number</label>
                                                </div>
                                                <input type="tel" class="form-control card-input" id="paymentCardNumber" maxlength="19" placeholder="1234 1234 1234 1234" autocomplete="off">
                                                <div id="cardlogo" class="unkown">
                                                    <svg width="32" height="21" viewBox="0 0 32 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 7H31.56V18.51C31.56 19.8852 30.4452 21 29.07 21H2.49C1.11481 21 0 19.8852 0 18.51V7Z" fill="#BBBBBB"/>
                                                        <path d="M0 2.49C0 1.11481 1.11481 0 2.49 0H29.07C30.4452 0 31.56 1.11481 31.56 2.49V4H0V2.49Z" fill="#BBBBBB"/>
                                                        <line x1="3" y1="17" x2="11.1562" y2="17" stroke="#EFEFEF" stroke-width="2"/>
                                                        <rect x="24" y="14" width="4.53125" height="4.53125" rx="1.5" fill="#EFEFEF"/>
                                                    </svg>
                                                </div>                                    
                                            </div>
                                            <div class="card-info-2">
                                                <label for="paymentCardExpiry" class="card_number">Expiration Date</label>
                                                <input type="tel" maxlength="5" class="form-control numbersOnly" id="paymentCardExpiry" placeholder="MM / YY" autocomplete="off">
                                            </div>
                                            <div class="card-info-3">
                                                <label for="paymentCardCvv" class="card_number"> Security Code</label>
                                                <input type="tel" class="form-control numbersOnly" id="paymentCardCvv" maxlength="3" placeholder="000" autocomplete="off">
                                                <div id="securitylogo" class="unkown">
                                                    <svg width="26" height="19" viewBox="0 0 26 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 8.28125H24V15.5485C24 16.9197 22.8884 18.0312 21.5172 18.0312H2.48276C1.11157 18.0312 0 16.9197 0 15.5485V8.28125Z" fill="#E5E5E5"/>
                                                        <path d="M0 4.76401C0 3.39282 1.11157 2.28125 2.48276 2.28125H21.5172C22.8884 2.28125 24 3.39282 24 4.76401V5.28125H0V4.76401Z" fill="#E5E5E5"/>
                                                        <rect x="2" y="10" width="20" height="3" rx="1" fill="white"/>
                                                        <rect y="5" width="22" height="3.5" fill="#BBBBBB"/>
                                                        <circle cx="19" cy="7" r="7" fill="#666666"/>
                                                        <path d="M14.7471 5.108C14.6725 5.33667 14.5558 5.50467 14.3971 5.612C14.2431 5.71933 14.0378 5.773 13.7811 5.773V4.933C14.0331 4.933 14.2291 4.905 14.3691 4.849C14.5091 4.793 14.6071 4.70667 14.6631 4.59C14.7191 4.46867 14.7471 4.30533 14.7471 4.1H15.7271V9H14.7471V5.108ZM18.2152 9.049C17.7206 9.049 17.2912 8.93233 16.9272 8.699C16.5679 8.46567 16.3322 8.139 16.2202 7.719L17.1582 7.502C17.2469 7.73533 17.3822 7.915 17.5642 8.041C17.7509 8.167 17.9772 8.23 18.2432 8.23C18.4906 8.23 18.6842 8.16933 18.8242 8.048C18.9642 7.92667 19.0342 7.75167 19.0342 7.523C19.0342 7.313 18.9572 7.145 18.8032 7.019C18.6492 6.893 18.4276 6.83 18.1382 6.83H17.6202L17.7462 6.088H18.1872C18.4019 6.088 18.5722 6.03433 18.6982 5.927C18.8289 5.81967 18.8942 5.66333 18.8942 5.458C18.8942 5.28067 18.8289 5.13833 18.6982 5.031C18.5676 4.92367 18.3949 4.87 18.1802 4.87C17.7182 4.87 17.4359 5.10333 17.3332 5.57L16.4232 5.367C16.4652 5.11033 16.5702 4.88167 16.7382 4.681C16.9062 4.48033 17.1209 4.32633 17.3822 4.219C17.6482 4.107 17.9352 4.051 18.2432 4.051C18.7472 4.051 19.1486 4.16533 19.4472 4.394C19.7459 4.618 19.8952 4.92367 19.8952 5.311C19.8952 5.58167 19.8089 5.808 19.6362 5.99C19.4636 6.172 19.2372 6.298 18.9572 6.368C19.2886 6.43333 19.5522 6.57333 19.7482 6.788C19.9489 7.00267 20.0492 7.26167 20.0492 7.565C20.0492 8.01767 19.8882 8.37933 19.5662 8.65C19.2442 8.916 18.7939 9.049 18.2152 9.049ZM22.3293 9.049C21.8673 9.049 21.4683 8.92767 21.1323 8.685C20.801 8.43767 20.584 8.11567 20.4813 7.719L21.4123 7.502C21.4917 7.72133 21.6153 7.89867 21.7833 8.034C21.9513 8.16467 22.1427 8.23 22.3573 8.23C22.6 8.23 22.7937 8.146 22.9383 7.978C23.0877 7.81 23.1623 7.586 23.1623 7.306C23.1623 7.03067 23.09 6.809 22.9453 6.641C22.8007 6.473 22.607 6.389 22.3643 6.389C22.1823 6.389 22.012 6.452 21.8533 6.578C21.6993 6.69933 21.5873 6.83933 21.5173 6.998H20.5233L20.9783 4.1H24.0093L23.9393 4.87H21.8533L21.6083 6.333C21.7343 6.123 21.9 5.962 22.1053 5.85C22.3107 5.73333 22.544 5.675 22.8053 5.675C23.23 5.675 23.5637 5.822 23.8063 6.116C24.0537 6.40533 24.1773 6.79733 24.1773 7.292C24.1773 7.642 24.1003 7.95 23.9463 8.216C23.797 8.482 23.5823 8.68733 23.3023 8.832C23.027 8.97667 22.7027 9.049 22.3293 9.049Z" fill="white"/>
                                                    </svg>                                        
                                                </div>
                                            </div>
                                        </div>
                                        <span class="hideclass" id="paymentCardDetailsError"></span>
                                    </div>
                                </div>
                                <div class="add_cardholder">
                                    <div class="form-group fade-in form-bg">
                                        <label for="paymentCardName">Cardholder Name</label>
                                        <input type="text" class="form-control" id="paymentCardName" placeholder="Full name on card" autocomplete="off">
                                    </div>
                                </div>
                                <span class="hideclass" id="paymentCardHolderNameError"></span>
                            </div>
                        <?php } ?>
                        <div class="payment_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                        <!-- End payment card section -->
                        <?php if(!empty($krepling_user_detail->paymentMethodVM)) { ?>
                            <div class="cardcvv">
                                <div class="cardcvv_input">
                                    <label>Enter Security Code</label>
									<div class="card_cvv_input_image">
                                    <input type="password" name="userCardCvv" class="card-cvv-style getcvvval" id="txtCVVNumberId" placeholder="000" maxlength="3">
                                    <div id="securitylogo" class="unkown">
                                        <svg width="26" height="19" viewBox="0 0 26 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 8.28125H24V15.5485C24 16.9197 22.8884 18.0312 21.5172 18.0312H2.48276C1.11157 18.0312 0 16.9197 0 15.5485V8.28125Z" fill="#E5E5E5"></path>
                                            <path d="M0 4.76401C0 3.39282 1.11157 2.28125 2.48276 2.28125H21.5172C22.8884 2.28125 24 3.39282 24 4.76401V5.28125H0V4.76401Z" fill="#E5E5E5"></path>
                                            <rect x="2" y="10" width="20" height="5" rx="1" fill="white"></rect>
                                            <rect y="5" width="22" height="3.5" fill="#BBBBBB"></rect>
                                            <circle cx="19" cy="7" r="7" fill="#666666"></circle>
                                            <path d="M14.7471 5.108C14.6725 5.33667 14.5558 5.50467 14.3971 5.612C14.2431 5.71933 14.0378 5.773 13.7811 5.773V4.933C14.0331 4.933 14.2291 4.905 14.3691 4.849C14.5091 4.793 14.6071 4.70667 14.6631 4.59C14.7191 4.46867 14.7471 4.30533 14.7471 4.1H15.7271V9H14.7471V5.108ZM18.2152 9.049C17.7206 9.049 17.2912 8.93233 16.9272 8.699C16.5679 8.46567 16.3322 8.139 16.2202 7.719L17.1582 7.502C17.2469 7.73533 17.3822 7.915 17.5642 8.041C17.7509 8.167 17.9772 8.23 18.2432 8.23C18.4906 8.23 18.6842 8.16933 18.8242 8.048C18.9642 7.92667 19.0342 7.75167 19.0342 7.523C19.0342 7.313 18.9572 7.145 18.8032 7.019C18.6492 6.893 18.4276 6.83 18.1382 6.83H17.6202L17.7462 6.088H18.1872C18.4019 6.088 18.5722 6.03433 18.6982 5.927C18.8289 5.81967 18.8942 5.66333 18.8942 5.458C18.8942 5.28067 18.8289 5.13833 18.6982 5.031C18.5676 4.92367 18.3949 4.87 18.1802 4.87C17.7182 4.87 17.4359 5.10333 17.3332 5.57L16.4232 5.367C16.4652 5.11033 16.5702 4.88167 16.7382 4.681C16.9062 4.48033 17.1209 4.32633 17.3822 4.219C17.6482 4.107 17.9352 4.051 18.2432 4.051C18.7472 4.051 19.1486 4.16533 19.4472 4.394C19.7459 4.618 19.8952 4.92367 19.8952 5.311C19.8952 5.58167 19.8089 5.808 19.6362 5.99C19.4636 6.172 19.2372 6.298 18.9572 6.368C19.2886 6.43333 19.5522 6.57333 19.7482 6.788C19.9489 7.00267 20.0492 7.26167 20.0492 7.565C20.0492 8.01767 19.8882 8.37933 19.5662 8.65C19.2442 8.916 18.7939 9.049 18.2152 9.049ZM22.3293 9.049C21.8673 9.049 21.4683 8.92767 21.1323 8.685C20.801 8.43767 20.584 8.11567 20.4813 7.719L21.4123 7.502C21.4917 7.72133 21.6153 7.89867 21.7833 8.034C21.9513 8.16467 22.1427 8.23 22.3573 8.23C22.6 8.23 22.7937 8.146 22.9383 7.978C23.0877 7.81 23.1623 7.586 23.1623 7.306C23.1623 7.03067 23.09 6.809 22.9453 6.641C22.8007 6.473 22.607 6.389 22.3643 6.389C22.1823 6.389 22.012 6.452 21.8533 6.578C21.6993 6.69933 21.5873 6.83933 21.5173 6.998H20.5233L20.9783 4.1H24.0093L23.9393 4.87H21.8533L21.6083 6.333C21.7343 6.123 21.9 5.962 22.1053 5.85C22.3107 5.73333 22.544 5.675 22.8053 5.675C23.23 5.675 23.5637 5.822 23.8063 6.116C24.0537 6.40533 24.1773 6.79733 24.1773 7.292C24.1773 7.642 24.1003 7.95 23.9463 8.216C23.797 8.482 23.5823 8.68733 23.3023 8.832C23.027 8.97667 22.7027 9.049 22.3293 9.049Z" fill="white"></path>
                                        </svg>	
                                    </div>
									 </div>
                                    <span class="hideclass" id="cardCvvError"></span>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="bottomfix">
                            <div class="countinue-1 pay-button">
                                <input type="hidden" id="krpAmount" name="krpAmount" value="<?php echo esc_attr($krepling_total)?>">
                                <input type="hidden" id="cardExpDate" name="cardExpDate" value="">
                                <input type="hidden" id="payCardNumber" name="payCardNumber" value="">
                                <input type="hidden" id="cardName" name="cardName" value="">
                                <input type="hidden" id="cardIdNum" name="cardIdNum" value="">
                                <input type="hidden" id="current_currency_symbol" name="current_currency_symbol" value="<?php echo esc_attr($krepling_default_currency_symbol)?>">                 
                                <input type="hidden" id="current_currency_name" name="current_currency_name" value="<?php echo esc_attr($krepling_default_currency_name)?>">
                                <?php if(!empty($krepling_user_detail->paymentMethodVM)) { ?>
                                    <button type="button" class="btn currentbtn w-50 disabled" id="complete_payment" data-toggle="modal">Pay</button>
                                <?php } else { ?>
                                    <button type="button" class="btn currentbtn w-50" id="saveCard_payment" data-toggle="modal">Pay</button>
                                <?php } ?>
                                <div id="rightArrow"></div>
                                <div id="crossArrow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <div class="card-header1" id="myHeader">
                <div class="card-svg">
                    <div class="backBtn">
                      <div class="backbutton stepBackBtn" style="display: block;">
                          <a class="back" href="javascript:void(0);" aria-label="Back to sign in">
                              <span class="backIconSvg" aria-hidden="true">
                                  <svg viewBox="0 0 12 20" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M10 2L2 10L10 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                  </svg>
                              </span>
                              <p>Sign in</p>
                          </a>
                      </div>
                        <?php if(empty($krepling_logo_file)){ ?>
                            <div class="svglogo">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="470.000000pt" height="210.000000pt" viewBox="0 0 470.000000 210.000000" preserveAspectRatio="xMidYMid meet">
                                    <g transform="translate(0.000000,210.000000) scale(0.100000,-0.100000)" stroke="none">
                                        <path d="M875 2085 c-430 -81 -755 -394 -852 -820 -20 -88 -23 -326 -5 -410 62 -287 220 -522 455 -679 296 -197 658 -229 988 -87 119 51 219 120 320 221 400 394 423 999 55 1430 -61 71 -91 90 -146 90 l-44 0 -354 -353 c-331 -331 -354 -356 -359 -394 -12 -93 -6 -102 195 -305 l187 -188 138 0 c75 0 137 3 137 7 0 4 -100 108 -222 230 l-223 223 276 276 276 276 42 -58 c57 -79 107 -184 132 -280 31 -112 31 -316 0 -428 -65 -243 -224 -438 -446 -546 -119 -58 -197 -78 -327 -87 -342 -22 -663 168 -812 480 -55 114 -75 194 -83 319 -21 344 171 667 482 814 137 64 205 78 370 78 121 -1 151 -4 225 -27 l85 -26 75 74 74 74 -56 26 c-155 71 -413 102 -583 70z" />
                                        <path d="M660 1050 l0 -460 100 0 100 0 0 460 0 460 -100 0 -100 0 0 -460z" />
                                        <path d="M2760 1050 l0 -320 80 0 80 0 0 255 0 255 74 0 c91 0 132 -14 151 -51 19 -36 19 -62 0 -100 -18 -33 -50 -49 -100 -49 -62 0 -94 -59 -59 -108 13 -19 24 -22 78 -22 81 0 141 22 188 67 45 44 61 88 62 168 0 109 -58 187 -159 214 -22 6 -120 11 -217 11 l-178 0 0 -320z" />
                                        <path d="M3645 1361 c-48 -21 -76 -73 -184 -340 -61 -151 -111 -278 -111 -283 0 -5 39 -7 86 -6 l86 3 89 214 c49 118 90 216 92 218 3 2 18 -30 35 -72 l31 -75 -27 -6 c-56 -14 -112 -76 -112 -124 0 -6 29 -10 70 -10 105 0 133 -15 161 -89 l24 -61 89 0 c66 0 87 3 83 13 -240 608 -245 618 -338 623 -30 2 -63 0 -74 -5z" />
                                        <path d="M4100 1364 c0 -3 44 -79 97 -167 54 -90 103 -184 110 -212 7 -27 13 -96 13 -152 l0 -103 80 0 80 0 0 113 c1 141 9 166 127 370 l92 158 -97 -3 -97 -3 -47 -79 c-25 -43 -50 -82 -56 -88 -6 -6 -25 18 -52 64 -66 112 -60 108 -162 108 -48 0 -88 -3 -88 -6z" />
                                    </g>
                                </svg>
                            </div>
                        <?php } else { ?>
                            <img src="<?php echo  esc_url($krepling_logo_url); ?>" alt="Krepling logo">
                        <?php } ?>
                    </div>
                </div>
                <div class="signup_section toster_msg_section hideclass">
                    <div class="common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="heading-1">
                    <h2>Guest Checkout</h2>
                    <ul id="accordion" class="accordion">
                        <li>
                            <div class="link currency_dropdown"><span id="link"><?php if(!empty($krepling_default_currency_and_symbol)) { echo esc_html($krepling_default_currency_and_symbol);}?></span>
                                <i class="rotatearrow">
                                    <svg width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.31592 2.18311L6.94459 6.37095L11.5733 2.18311" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="2.7772" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </i>
                            </div>
                            <?php if(!empty($krepling_list_currency['first'])){ ?>
                                <ul class="submenu" id="submenu" style="display:none">
                                    <input type="text" placeholder="Search..." class="form-control" id="searchCurrency" onkeyup="searchCurrencyFunction()">
                                    <div class="star"><i class="fa fa-star" aria-hidden="true"></i></div>
                                    <?php 
                                    $krepling_data_currency = $krepling_list_currency;
                                    echo wp_kses_post(krepling_render_currency_options($krepling_data_currency)); ?>
                                </ul>
                            <?php } ?>
                        </li>
                    </ul>  
                </div>
            </div>
            
            <div id="stepFirstId" class="payment-form">
                <div class="pannel-carddetails-sec mb-20">
                    <div class="row cart cart_input dropdown-toggle" id="flipDivId" style="display: flex;">
                        <div class="cart-1 shopping-card-icon">
                            <svg width="21" height="24" viewBox="0 0 21 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20.3632 22.546L19.6333 0.660004C19.6276 0.482966 19.5532 0.315105 19.4259 0.191925C19.2986 0.0687454 19.1284 -9.20898e-05 18.9513 9.24636e-08H1.4133C1.23617 -9.20898e-05 1.06585 0.0687454 0.938565 0.191925C0.81128 0.315105 0.737004 0.482966 0.73129 0.660004L0.000210958 22.582C-0.00205554 22.673 0.013963 22.7636 0.047208 22.8484C0.0804531 22.9331 0.130271 23.0104 0.193814 23.0756C0.257358 23.1408 0.333326 23.1927 0.417203 23.2281C0.50108 23.2636 0.591161 23.2819 0.682218 23.282H19.6822C19.8631 23.282 20.0366 23.2101 20.1645 23.0822C20.2924 22.9543 20.3642 22.7809 20.3642 22.6C20.3652 22.585 20.3642 22.566 20.3632 22.546ZM1.3873 21.923L2.07321 1.36401H18.2933L18.9782 21.923H1.3873Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                <path d="M14.5671 4.38478C14.4775 4.38465 14.3888 4.40217 14.3059 4.43638C14.223 4.4706 14.1477 4.52084 14.0842 4.58418C14.0208 4.64752 13.9705 4.72274 13.9362 4.80555C13.9018 4.88836 13.8842 4.97711 13.8842 5.06675C13.8842 6.04805 13.4943 6.98917 12.8004 7.68306C12.1065 8.37694 11.1655 8.76677 10.1842 8.76677C9.2029 8.76677 8.26176 8.37694 7.56787 7.68306C6.87399 6.98917 6.48413 6.04805 6.48413 5.06675C6.47737 4.89026 6.40252 4.72326 6.27527 4.60078C6.14801 4.4783 5.97825 4.40989 5.80164 4.40989C5.62502 4.40989 5.45526 4.4783 5.328 4.60078C5.20075 4.72326 5.1259 4.89026 5.11914 5.06675C5.11914 6.41061 5.65302 7.69942 6.60327 8.64967C7.55352 9.59992 8.84231 10.1338 10.1862 10.1338C11.53 10.1338 12.8188 9.59992 13.769 8.64967C14.7193 7.69942 15.2532 6.41061 15.2532 5.06675C15.2532 4.97685 15.2355 4.88786 15.2009 4.80485C15.1664 4.72185 15.1158 4.64649 15.052 4.58311C14.9882 4.51973 14.9126 4.46957 14.8293 4.43553C14.7461 4.40149 14.657 4.38425 14.5671 4.38478Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                            </svg>
                            <p>Total: <span class="total_cart_amount">
                                <label id="lblAmountId"> 
                                    <span id="current_currency"><?php echo esc_html($krepling_default_currency_symbol)?> </span><?php echo esc_html($krepling_total); ?>
                                </label>
                                </span>
                            </p>

                        </div>
                        <div class="quantity-1">
                            <div class="rotate">
                                <svg width="29" height="17" viewBox="0 0 29 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2 2.06836L14.5 14.5684L27 2.06836" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>               
                        </div>
                    </div>
                    <div id="panelproductId" class="cart dropdown">
                        <h3>Order Summary</h3>
                        <table class="table table-xs">
                            <tbody>
                                <?php if(!empty($krepling_cart_data)){
                                    foreach( $krepling_cart_data as $krepling_cart_item ) { 
                                        $krepling_product_id = $krepling_cart_item['product_id'];
                                        $krepling_product = wc_get_product( $krepling_product_id );
                                        $krepling_show_total = number_format((float) $krepling_cart_item['quantity'] * $krepling_product->get_price(), 2, '.', '');
                                        ?>
                                        <tr class="item-row">
                                            <td class="product-item">
                                                <?php echo wp_kses_post($krepling_product->get_image());?>
                                            </td>
                                            <td class="product-name">
                                                <p><?php echo esc_html($krepling_cart_item['data']->get_name());?></p>
                                                <p>Price: <b aria-hidden="true" class="order_summary_price_symbol"><?php echo esc_html($krepling_default_currency_symbol)?></b><strong><?php echo esc_html(number_format((float) $krepling_product->get_price(), 2, '.', ''))?></strong></p>
                                            </td>
                                            <td class="text-left product_quantity" title="quantity">
                                                <strong class="ProductPrice">
                                                    <div class="productQuantity">
                                                        <span class="qnt_back"><?php echo esc_html((string) $krepling_cart_item['quantity'])?></span>
                                                    </div>
                                                </strong>
                                            </td>
                                            <td class="text-right" title="price">
                                                <strong class="ProductPrice">
                                                        <span class="current_currency_type"><?php echo esc_html($krepling_default_currency_symbol)?><b class="product_price"><?php echo esc_html($krepling_show_total)?></b></span><br> 
                                                </strong>
                                            </td>
                                        </tr>
                                <?php } }?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row info">
                                    <td class="text-left" colspan="3">Subtotal</td>
                                    <td class="text-right cart_subtotal">
                                        <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_subtotal); ?></span>
                                    </td>
                                </tr>
                                <?php if(isset($krepling_discount) && $krepling_discount > 0){ ?>
                                <tr class="total-row info">
                                    <td class="text-left" colspan="3">Discount</td>
                                    <td class="text-right cart_discount">
                                        <span><i class="fa fa current_currency_type" aria-hidden="true">-<?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_discount); ?></span>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php if(isset($krepling_shipping) && $krepling_shipping > 0){ ?>
                                <tr class="total-row info">
                                    <td class="text-left" colspan="3">Shipping</td>
                                    <td class="text-right cart_shipping">
                                        <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_shipping); ?></span>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php if(isset($krepling_tax) && $krepling_tax > 0){ ?>
                                <tr class="total-row info">
                                    <td class="text-left" colspan="3">Tax</td>
                                    <td class="text-right cart_tax">
                                        <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_tax); ?></span>
                                    </td>
                                </tr> 
                                <?php } ?> 
                                <tr class="total-row info">
                                    <td class="text-left" colspan="3"><strong>Total</strong></td>
                                    <td class="text-right cart_total">
                                        <strong>
                                            <span><i class="fa fa current_currency_type" aria-hidden="true"><?php echo esc_html($krepling_default_currency_symbol)?></i><?php echo  esc_html($krepling_total); ?></span>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <!--new section email and phone-->
                <div id="stepSecondId">
                    <div class="form-group fadeIn ">
                        <label for="emailAddress">Email Address</label>
                        <input type="email" class="form-control" id="emailAddress" autocomplete="off" placeholder="e.g. john.doe@example.com" >
                        <img src="<?php echo esc_url($krepling_plugin_dir_url . 'assets/images/emailotploader.gif')?>" class="email_otp_loader" style="display:none" >
                    </div>
                    <span class="hideclass" id="registerEmailError"></span>
                    <div class="email_section toster_msg_section hideclass">
                        <div class="common_toster">
                            <span class="toster_message"></span>
                            <span><i class="fa fa-times"></i></span>
                        </div>
                    </div>
                    <div id="stepThirdId" style="display:none">
                        <div class="body-content otp_verification">
                            <div class="otp_blocks">
                                <div id="SMSArea" class="SMSArea mt-4">
                                    <div class="code-col">
                                        <h5 class=" animate fadeInDown two">Enter the verification code sent to your email address.</h5>
                                        <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <input type="tel" id="verifyEmailOtp" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                        <span class="hideclass" id="verifySignupOtpError"></span>
                                    </div>
                                </div>
                                <div class="text-center mt-3 box animate fadeInDown two" id="sendEmailForVerificationId">
                                    <span class="sms_content"> <strong>Didnt receive an email?</strong> <a class="changeBtnStatusId" id="sendSmsOtpAgain">Send again</a></span>
                                    <span class="countdown"></span>
                                </div>
                            </div>
                        </div>
                        <div class="sendotp_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group fadeIn">
                        <label for="phoneNumberIds">Phone Number</label>
                        <input type="tel" class="form-control" style="padding-left: 65px;" id="phoneNumberIds" autocomplete="off">
                        <div class="phone_tooltip">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="9" cy="9" r="8.5" fill="<?php echo esc_attr($krepling_config_data['plugin-bg-color'] ?? '')?>" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" opacity="0.5" />
                                <path d="M8.21341 10.6542C8.21341 10.192 8.27504 9.81067 8.3983 9.51022C8.52926 9.20978 8.68334 8.97867 8.86052 8.81689C9.03771 8.64741 9.26497 8.47407 9.5423 8.29689C9.758 8.15052 9.92363 8.02726 10.0392 7.92711C10.1547 7.82696 10.251 7.7037 10.3281 7.55733C10.4128 7.40326 10.4552 7.21452 10.4552 6.99111C10.4552 6.63674 10.3396 6.35941 10.1085 6.15911C9.87741 5.95881 9.55 5.85867 9.1263 5.85867C8.67178 5.85867 8.30585 5.98193 8.02852 6.22844C7.75119 6.47496 7.55859 6.84474 7.45074 7.33778L6.3183 7.08356C6.46467 6.39022 6.78052 5.84326 7.26585 5.44267C7.75889 5.03437 8.39445 4.83022 9.17252 4.83022C9.68097 4.83022 10.1239 4.91881 10.5014 5.096C10.8789 5.27319 11.1639 5.51585 11.3565 5.824C11.5568 6.12444 11.657 6.46341 11.657 6.84089C11.657 7.18756 11.5992 7.4803 11.4836 7.71911C11.3758 7.95022 11.241 8.13896 11.0792 8.28533C10.9251 8.4317 10.7133 8.59733 10.4436 8.78222C10.1817 8.95941 9.96985 9.12119 9.80808 9.26756C9.654 9.40622 9.52304 9.59111 9.41519 9.82222C9.30734 10.0456 9.25341 10.323 9.25341 10.6542V10.8507H8.21341V10.6542ZM8.17874 11.6942H9.29963V13H8.17874V11.6942Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>" opacity="0.5"/>
                            </svg>
                            <span class="phone_tooltiptext">Receive instant order confirmation and shipping alerts via SMS.</span>
                        </div>                
                    </div>
                    <span class="hideclass" id="registerPhoneNumberError"></span>
                    <div class="otpverified_section toster_msg_section hideclass">
                        <div class="success_toster_messsage common_toster">
                            <span class="toster_message">Your account has been created  
                                <p>Your details won't be saved on this device unless you choose to</p>
                            </span>
                            <span><i class="fa fa-times"></i></span>
                        </div>
                    </div>
                </div>
			    <!--new section email and phone end-->
				
                <div class="text-1">
                    <p class="already-acnt">Already have an <strong> &nbsp; account</strong>? <a id="firstLoginId">
  Sign in
  <svg width="10" height="10" viewBox="0 0 20 20" aria-hidden="true" style="margin-left:6px; vertical-align:middle;">
    <path d="M7 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/>
  </svg>
</a></p>
                </div>
                <?php if($krepling_config_data['hide_Address'] != 'yes'){ ?>
                    <div class="form-group" id="full_name_feild">
                        <label for="first_last">Full Name</label>
                        <input type="text" class="form-control filladdress-input" id="first_last" placeholder="First and Last Name" autocomplete="off" required>
                    </div>
                <?php } ?>
                <span class="hideclass" id="fullNameError"></span>
                <!--checkout company name-->
                <input type="hidden" id="productCartName" value="
                    <?php foreach( $krepling_cart_data as $krepling_cart_item ) {
                            echo esc_attr($krepling_cart_item['data']->get_name());
                            break;
                    } ?>">
                <div class="checkout-company_name" >
                    <button type="button" class="btn  addcompany_button common_btn" id="addcompany_button" data-toggle="modal" data-target="">+ Add a company name</button>
                </div>
                <div class="form-group" id="addcompany" style="display:none;">
                    <label for="companyName">Company</label>
                    <input type="text" class="form-control filladdress-input" id="companyName" placeholder="Company Name (Optional)" autocomplete="off">
                </div>
                <!--checkout company name end-->
                <?php if($krepling_config_data['hide_Address'] != 'yes'){ ?>
                    <div class="checkout-delivery">
                        <div class="form-group">
                            <form id="address-form">
                                <label for="registerSearchAddress">Delivery Address </label>
                                <input type="text" class="form-control filladdress-input mb-0" id="registerSearchAddress" placeholder="&#x1F50E;&#xFE0E; Start typing your shipping address" autocomplete="off" maxlength="20" required >
                                <span class="hideclass" id="searchAddressError"></span>

                                <!--Add additional information  name-->
                                <div class="add_additional_unitAddress" >
                                    <button type="button" class="btn addcompany_button common_btn" id="addinformation_button" data-toggle="modal" data-target="">+ Add additional information (Apt, Unit, Suite)</button>
                                </div>
                                <div class="form-group mt-21 mb-0" id="additional_information" style="display:none;">
                                    <input type="text" class="form-control mt-2 optadd " id="registerUnitAddress" placeholder="# Apt, Unit, Suite (Optional)" autocomplete="on" maxlength="20">
                                </div>
						        <!--Add additional information  end-->
                            </form>

                            <form id="address-form2" class="address-form2">
                                <label for="address1">Address Line 1</label>
                                <input type="text" class="form-control filladdress-input" id="address1" placeholder="Street Address" autocomplete="off" maxlength="20">
                                <span class="hideclass" id="address1Error"></span>
                                <!--Add additional information  name-->
                                <div class="add_additional_shippingAddress" >
                                    <button type="button" class="btn addcompany_button common_btn" id="additional_shippingAddress" data-toggle="modal" data-target="">+ Add an additional shipping address</button>
                                </div>
                                <div class="optional_shipping_address" style="display:none">
                                    <label for="address2">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control mt-2 filladdress-input" id="address2" placeholder="Street Address (Optional)" autocomplete="on" maxlength="20">
                                </div>
                                <!--Add additional information  end-->
                                <div class="secondaddress mb-21">                                    
                                    <div class="select_country mb-21">
                                        <label for="registerCountry">Country </label>
                                        <div class="state-select country1" id="registerCountry"></div>
                                        <span class="hideclass" id="countryError"></span>
                                    </div>
                                    <div class="statezipcode">
                                        <label for="registerZip">Zip Code </label>
                                        <input type="text" class="form-control zip1" id="registerZip" placeholder="e.g. 12345" autocomplete="on" maxlength="10">
                                        <span class="hideclass" id="zipError"></span>
                                    </div>
                                </div>
                                <div class="secondaddress">
                                    <div class="addstate mb-21">
                                        <label for="registerState">State </label>
                                        <div class="state1 state-select stateDropdown">
                                            <input type="text" class="form-control state1" id="registerState" placeholder="State" autocomplete="on">
                                        </div>
                                        <span class="hideclass" id="stateError"></span>
                                    </div>
                                    <div class="addcity">
                                        <label for="registerCity">City </label>
                                        <input type="text" class="form-control mt-2 filladdress-input city1" id="registerCity" placeholder="City" autocomplete="on" maxlength="20">
                                        <span class="hideclass" id="cityError"></span>
                                    </div>
                                </div>
                            </form>
                            <button type="button" class="btn btn-primary search-dilvery-button" id="cantFind-button" data-toggle="modal" data-target="">Enter address manually</button>
                            <button type="button" class="btn btn-primary search-dilvery-button" id="searchAddress" data-toggle="modal" data-target="">Search address</button>
                        </div>
                    </div>
                <?php } ?>

                <div class="form-group" id="card_holder_group">
                    <div class="card-information">
                        <div class="card-info-1">
                            <div class="card_label-1">
                                <label for="card" class="card_number">Card Number</label>
                            </div>
                            <input type="tel" class="form-control card-input" id="card" maxlength="19" placeholder="1234 1234 1234 1234" autocomplete="off">
                            <div id="cardlogo" class="unkown">
                                <svg width="32" height="21" viewBox="0 0 32 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 7H31.56V18.51C31.56 19.8852 30.4452 21 29.07 21H2.49C1.11481 21 0 19.8852 0 18.51V7Z" fill="#BBBBBB"/>
                                    <path d="M0 2.49C0 1.11481 1.11481 0 2.49 0H29.07C30.4452 0 31.56 1.11481 31.56 2.49V4H0V2.49Z" fill="#BBBBBB"/>
                                    <line x1="3" y1="17" x2="11.1562" y2="17" stroke="#EFEFEF" stroke-width="2"/>
                                    <rect x="24" y="14" width="4.53125" height="4.53125" rx="1.5" fill="#EFEFEF"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-info-2">
                            <label for="cardvalidityDateId" class="card_number">Expiration Date</label>
                            <input type="tel" maxlength="5" class="form-control numbersOnly" id="cardvalidityDateId" placeholder="MM / YY" autocomplete="off">
                        </div>
                        <div class="card-info-3">
                            <label for="txtcvvNumberId" class="card_number"> Security Code</label>
                            <input type="tel" class="form-control numbersOnly" id="txtcvvNumberId" maxlength="3" placeholder="000" autocomplete="off">
                            <div id="securitylogo" class="unkown">
                                <svg width="26" height="19" viewBox="0 0 26 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 8.28125H24V15.5485C24 16.9197 22.8884 18.0312 21.5172 18.0312H2.48276C1.11157 18.0312 0 16.9197 0 15.5485V8.28125Z" fill="#E5E5E5"/>
                                    <path d="M0 4.76401C0 3.39282 1.11157 2.28125 2.48276 2.28125H21.5172C22.8884 2.28125 24 3.39282 24 4.76401V5.28125H0V4.76401Z" fill="#E5E5E5"/>
                                    <rect x="2" y="10" width="20" height="3" rx="1" fill="white"/>
                                    <rect y="5" width="22" height="3.5" fill="#BBBBBB"/>
                                    <circle cx="19" cy="7" r="7" fill="#666666"/>
                                    <path d="M14.7471 5.108C14.6725 5.33667 14.5558 5.50467 14.3971 5.612C14.2431 5.71933 14.0378 5.773 13.7811 5.773V4.933C14.0331 4.933 14.2291 4.905 14.3691 4.849C14.5091 4.793 14.6071 4.70667 14.6631 4.59C14.7191 4.46867 14.7471 4.30533 14.7471 4.1H15.7271V9H14.7471V5.108ZM18.2152 9.049C17.7206 9.049 17.2912 8.93233 16.9272 8.699C16.5679 8.46567 16.3322 8.139 16.2202 7.719L17.1582 7.502C17.2469 7.73533 17.3822 7.915 17.5642 8.041C17.7509 8.167 17.9772 8.23 18.2432 8.23C18.4906 8.23 18.6842 8.16933 18.8242 8.048C18.9642 7.92667 19.0342 7.75167 19.0342 7.523C19.0342 7.313 18.9572 7.145 18.8032 7.019C18.6492 6.893 18.4276 6.83 18.1382 6.83H17.6202L17.7462 6.088H18.1872C18.4019 6.088 18.5722 6.03433 18.6982 5.927C18.8289 5.81967 18.8942 5.66333 18.8942 5.458C18.8942 5.28067 18.8289 5.13833 18.6982 5.031C18.5676 4.92367 18.3949 4.87 18.1802 4.87C17.7182 4.87 17.4359 5.10333 17.3332 5.57L16.4232 5.367C16.4652 5.11033 16.5702 4.88167 16.7382 4.681C16.9062 4.48033 17.1209 4.32633 17.3822 4.219C17.6482 4.107 17.9352 4.051 18.2432 4.051C18.7472 4.051 19.1486 4.16533 19.4472 4.394C19.7459 4.618 19.8952 4.92367 19.8952 5.311C19.8952 5.58167 19.8089 5.808 19.6362 5.99C19.4636 6.172 19.2372 6.298 18.9572 6.368C19.2886 6.43333 19.5522 6.57333 19.7482 6.788C19.9489 7.00267 20.0492 7.26167 20.0492 7.565C20.0492 8.01767 19.8882 8.37933 19.5662 8.65C19.2442 8.916 18.7939 9.049 18.2152 9.049ZM22.3293 9.049C21.8673 9.049 21.4683 8.92767 21.1323 8.685C20.801 8.43767 20.584 8.11567 20.4813 7.719L21.4123 7.502C21.4917 7.72133 21.6153 7.89867 21.7833 8.034C21.9513 8.16467 22.1427 8.23 22.3573 8.23C22.6 8.23 22.7937 8.146 22.9383 7.978C23.0877 7.81 23.1623 7.586 23.1623 7.306C23.1623 7.03067 23.09 6.809 22.9453 6.641C22.8007 6.473 22.607 6.389 22.3643 6.389C22.1823 6.389 22.012 6.452 21.8533 6.578C21.6993 6.69933 21.5873 6.83933 21.5173 6.998H20.5233L20.9783 4.1H24.0093L23.9393 4.87H21.8533L21.6083 6.333C21.7343 6.123 21.9 5.962 22.1053 5.85C22.3107 5.73333 22.544 5.675 22.8053 5.675C23.23 5.675 23.5637 5.822 23.8063 6.116C24.0537 6.40533 24.1773 6.79733 24.1773 7.292C24.1773 7.642 24.1003 7.95 23.9463 8.216C23.797 8.482 23.5823 8.68733 23.3023 8.832C23.027 8.97667 22.7027 9.049 22.3293 9.049Z" fill="white"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <span id="credit-card-type-text" class="help-inline"></span>
                    <span class="hideclass" id="cardDetailsError"></span>
                    <div class="card-holder">
                        <label for="card_holder_name" class="card_details_heading">Cardholder Name</label>
                        <input type="text" class="form-control filladdress-input" id="card_holder_name" placeholder="Full name on card" autocomplete="off" required>
                    </div>
                    <span class="hideclass" id="cardHolderNameError"></span>
                </div>
                <?php if($krepling_config_data['hide_Address'] != 'yes'){ ?>
                    <!--billing_checkbox-->
                    <div class="billing_checkbox">
                        <div class="biling_flex">
                            <input type="checkbox" class="checkbox-round custom_checkbox" id="signupBillingCheckbox" checked="checked">
                        </div>
                        <div class="biling_flex">
                            <p>Billing address same as shipping</p>
                        </div>
                    </div>
                    <!--billing_checkbox end-->
                    <form id="billingAddressForm" class="address-form2" style="display:none">
                        <label for="billingAddress">Address Line 1</label>
                        <input type="text" class="form-control filladdress-input" id="billingAddress" placeholder="Street Address" autocomplete="off" maxlength="20">
                        <span class="hideclass" id="billingAddressError"></span>                      
                        
                        <!--Add additional information  name-->
                        <div class="add_additional_billingAddress" >
                            <button type="button" class="btn addcompany_button common_btn" id="additional_billingAddress" data-toggle="modal" data-target="">+ Add an additional billing address</button>
                        </div>
                        <div class="optional_billing_address" style="display:none">
                            <label for="billingAddress1">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control mt-2 filladdress-input" id="billingAddress1" placeholder="Street Address (Optional)" autocomplete="on" maxlength="20">
                        </div>
                        <!--Add additional information  end-->
                        
                        <div class="biling_address2 mb-21">
						  <div class="select_country mb-21">
                                <label for="billingCountry 1">Country </label>
                                <div class="state-select country1" id="billingCountry"></div>
                                <span class="hideclass" id="billingCountryError"></span>
                            </div>
                            <div class="statezipcode">
                                <label for="billingZip">Zip Code </label>
                                <input type="text" class="form-control zip1" id="billingZip" placeholder="e.g. 12345" autocomplete="on" maxlength="10">
                                <span class="hideclass" id="billingZipError"></span>
                            </div>
                        </div>
                        <div class="biling_address2  mb-21">
                            <div class="addstate mb-21">
                                <label for="billingState">State </label>
                                <div class="state1 state-select billingStateDropdown">
                                    <input type="text" class="form-control state1" id="billingState" placeholder="State" autocomplete="on">
                                </div>
                                <span class="hideclass" id="billingStateError"></span>
                            </div>
							<div class="addcity">
                                <label for="billingCity">City </label>
                                <input type="text" class="form-control mt-2 filladdress-input city1" id="billingCity" placeholder="City" autocomplete="on" maxlength="20">
                                <span class="hideclass" id="billingCityError"></span>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <div class="form-group mt-md-4 mt-lg-3 mt-3">
                    <div class="checkinform">
                        <div class="content1">
                            <h4>Save Payment Details</h4>
                            <p>one-click checkout experience</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="iskreplingFastId">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                <div class="registration_section toster_msg_section hideclass">
                    <div class="common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="justify-content-end">
                    <div class=" text-right">
                        <input type="button" class="btn currentbtn disabled" id="btnFirstStep" value="Pay">
                    </div>
                </div>
                <div id="rightArrow"></div>
                <div id="crossArrow"></div>
            </div>

            <div id="loginDivId" style="display:none">
                <div class="form-group fadeIn">
                    <label for="user_email">Email Address</label>
                    <input type="email" class="form-control" id="user_email" autocomplete="off" placeholder="e.g. john.doe@example.com" value="<?php echo esc_attr($krepling_remembered_email); ?>">  
                    <span class="hideclass" id="userLoginEmailError"></span>
                </div>
                <div class="loginemail_section toster_msg_section hideclass">
                    <div class="common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="form-group fadeIn showpass">
                    <label for="user_password">Password</label>
                    <input type="password" class="form-control" id="user_password">
                    <i id="togglePassword">
                        <svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>
                            <path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>
                            <rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>
                            <rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>
                        </svg>
                    </i>
                    <span class="hideclass" id="userLoginPasswordError"></span>
                </div>
                <span class="forgetpass" onclick="forgotPassword()">Forgotten your password?</span>
                <div class="login_section toster_msg_section hideclass">
                    <div class="common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="flex-btn">
                    <input type="button" id="loginSubmit" value="Continue" class="btn currentbtn float-right">
                </div>
            </div>
        <?php } ?>

        <!-- Include Wallet Setting Section -->
        <?php include_once('wallet-setting.php'); ?>

        <!--copyright section-->
        <footer id="copyright_section" class="account_card_copyright">
            <div class="copyright_group">
                <div class="copyright_text">
                    <h4>Powered by Krepling</h4>
                </div>
                <div class="copyright_email">
                    <h4>
  <a href="mailto:support@krepling.com">
    <span class="copyright_email_icon" aria-hidden="true">
      <svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="1" y="1" width="17" height="13" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
        <path d="M2.5 3L9.5 8.2L16.5 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
    <span>support@krepling.com</span>
  </a>
</h4>
                </div>
            </div>
        </footer>
        <!--copyright section-->
    </div>
    <?php if(isset($krepling_user_detail) && !empty($krepling_user_detail)){ ?>
        <div class="addcardmodel" id="addcard" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none">
            <div class="modal-dialog setCenter">
                <div class="modal-content addcard-content bbox">
                    <div class="card-header3">
                        <div class="modal-header modalheaderBg box2">
                            <div class="add-card-svg">
                                <?php include('setting-header.php'); ?>
                                <div class="addcard-heading  customClose" data-dismiss="modal" aria-hidden="true">
                                    <h2 class="cssanimation sequence zoomInDown back_checkout_aadCard"><i class="fa fa-angle-left" aria-hidden="true"></i>Add New Card</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="addcard-body">
                        <div class="add_heading">
                            <h4 class="head-txt mb-0 text-success text-left" style="padding-left: 3px;" id="modelheadingMsg"></h4>
                        </div>
                        <div class="add_newcard" id="divCardId">
                            <div class="mt-2">
                                <div class="card-information">
                                    <div class="card-info-1">
                                        <div class="card_label-1">
                                            <label for="addCardNumber" class="card_number">Card Number</label>
                                        </div>
                                        <input type="tel" class="form-control card-input" id="addCardNumber" maxlength="19" placeholder="1234 1234 1234 1234" autocomplete="off">
                                        <div id="cardlogo" class="unkown">
                                            <svg width="32" height="21" viewBox="0 0 32 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0 7H31.56V18.51C31.56 19.8852 30.4452 21 29.07 21H2.49C1.11481 21 0 19.8852 0 18.51V7Z" fill="#BBBBBB"/>
                                                <path d="M0 2.49C0 1.11481 1.11481 0 2.49 0H29.07C30.4452 0 31.56 1.11481 31.56 2.49V4H0V2.49Z" fill="#BBBBBB"/>
                                                <line x1="3" y1="17" x2="11.1562" y2="17" stroke="#EFEFEF" stroke-width="2"/>
                                                <rect x="24" y="14" width="4.53125" height="4.53125" rx="1.5" fill="#EFEFEF"/>
                                            </svg>
                                        </div>                                    
                                    </div>
                                    <div class="card-info-2">
                                        <label for="addCardExpiry" class="card_number">Expiration Date</label>
                                        <input type="tel" maxlength="5" class="form-control numbersOnly" id="addCardExpiry" placeholder="MM / YY" autocomplete="off">
                                    </div>
                                    <div class="card-info-3">
                                        <label for="addCardCvv" class="card_number"> Security Code</label>
                                        <input type="tel" class="form-control numbersOnly" id="addCardCvv" maxlength="3" placeholder="000" autocomplete="off">
                                        <div id="securitylogo" class="unkown">
                                            <svg width="26" height="19" viewBox="0 0 26 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0 8.28125H24V15.5485C24 16.9197 22.8884 18.0312 21.5172 18.0312H2.48276C1.11157 18.0312 0 16.9197 0 15.5485V8.28125Z" fill="#E5E5E5"/>
                                                <path d="M0 4.76401C0 3.39282 1.11157 2.28125 2.48276 2.28125H21.5172C22.8884 2.28125 24 3.39282 24 4.76401V5.28125H0V4.76401Z" fill="#E5E5E5"/>
                                                <rect x="2" y="10" width="20" height="3" rx="1" fill="white"/>
                                                <rect y="5" width="22" height="3.5" fill="#BBBBBB"/>
                                                <circle cx="19" cy="7" r="7" fill="#666666"/>
                                                <path d="M14.7471 5.108C14.6725 5.33667 14.5558 5.50467 14.3971 5.612C14.2431 5.71933 14.0378 5.773 13.7811 5.773V4.933C14.0331 4.933 14.2291 4.905 14.3691 4.849C14.5091 4.793 14.6071 4.70667 14.6631 4.59C14.7191 4.46867 14.7471 4.30533 14.7471 4.1H15.7271V9H14.7471V5.108ZM18.2152 9.049C17.7206 9.049 17.2912 8.93233 16.9272 8.699C16.5679 8.46567 16.3322 8.139 16.2202 7.719L17.1582 7.502C17.2469 7.73533 17.3822 7.915 17.5642 8.041C17.7509 8.167 17.9772 8.23 18.2432 8.23C18.4906 8.23 18.6842 8.16933 18.8242 8.048C18.9642 7.92667 19.0342 7.75167 19.0342 7.523C19.0342 7.313 18.9572 7.145 18.8032 7.019C18.6492 6.893 18.4276 6.83 18.1382 6.83H17.6202L17.7462 6.088H18.1872C18.4019 6.088 18.5722 6.03433 18.6982 5.927C18.8289 5.81967 18.8942 5.66333 18.8942 5.458C18.8942 5.28067 18.8289 5.13833 18.6982 5.031C18.5676 4.92367 18.3949 4.87 18.1802 4.87C17.7182 4.87 17.4359 5.10333 17.3332 5.57L16.4232 5.367C16.4652 5.11033 16.5702 4.88167 16.7382 4.681C16.9062 4.48033 17.1209 4.32633 17.3822 4.219C17.6482 4.107 17.9352 4.051 18.2432 4.051C18.7472 4.051 19.1486 4.16533 19.4472 4.394C19.7459 4.618 19.8952 4.92367 19.8952 5.311C19.8952 5.58167 19.8089 5.808 19.6362 5.99C19.4636 6.172 19.2372 6.298 18.9572 6.368C19.2886 6.43333 19.5522 6.57333 19.7482 6.788C19.9489 7.00267 20.0492 7.26167 20.0492 7.565C20.0492 8.01767 19.8882 8.37933 19.5662 8.65C19.2442 8.916 18.7939 9.049 18.2152 9.049ZM22.3293 9.049C21.8673 9.049 21.4683 8.92767 21.1323 8.685C20.801 8.43767 20.584 8.11567 20.4813 7.719L21.4123 7.502C21.4917 7.72133 21.6153 7.89867 21.7833 8.034C21.9513 8.16467 22.1427 8.23 22.3573 8.23C22.6 8.23 22.7937 8.146 22.9383 7.978C23.0877 7.81 23.1623 7.586 23.1623 7.306C23.1623 7.03067 23.09 6.809 22.9453 6.641C22.8007 6.473 22.607 6.389 22.3643 6.389C22.1823 6.389 22.012 6.452 21.8533 6.578C21.6993 6.69933 21.5873 6.83933 21.5173 6.998H20.5233L20.9783 4.1H24.0093L23.9393 4.87H21.8533L21.6083 6.333C21.7343 6.123 21.9 5.962 22.1053 5.85C22.3107 5.73333 22.544 5.675 22.8053 5.675C23.23 5.675 23.5637 5.822 23.8063 6.116C24.0537 6.40533 24.1773 6.79733 24.1773 7.292C24.1773 7.642 24.1003 7.95 23.9463 8.216C23.797 8.482 23.5823 8.68733 23.3023 8.832C23.027 8.97667 22.7027 9.049 22.3293 9.049Z" fill="white"/>
                                            </svg>                                        
                                        </div>
                                    </div>
                                </div>
                                <span class="hideclass" id="addCardDetailsError"></span>
                            </div>
                        </div>
                        <div class="add_cardholder">
                            <div class="form-group fade-in form-bg">
                                <label for="cardFirstName">Cardholder Name</label>
                                <input type="text" class="form-control" id="cardFirstName" placeholder="Full name on card" autocomplete="off">
                            </div>
                        </div>
                        <span class="hideclass" id="addCardHolderNameError"></span>
                    </div>
                    <div class="addcard_section toster_msg_section hideclass">
                        <div class="common_toster">
                            <span class="toster_message"></span>
                            <span><i class="fa fa-times"></i></span>
                        </div>
                    </div>
                    <div class="modal-footer add-btn-modelfooter">
                        <button type="button" id="btnAddCard" class=" add-btn-addcard customClose" >Add</button>
                    </div>
                    <!--copyright section-->
                    <footer id="copyright_section">
                        <div class="copyright_group">
                            <div class="copyright_text">
                                <h4>Powered by Krepling</h4>
                            </div>
                            <div class="copyright_email">
                                <h4>
  <a href="mailto:support@krepling.com">
    <span class="copyright_email_icon" aria-hidden="true">
      <svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="1" y="1" width="17" height="13" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
        <path d="M2.5 3L9.5 8.2L16.5 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
    <span>support@krepling.com</span>
  </a>
</h4>
                            </div>
                        </div>
                    </footer>
                    <!--copyright section-->
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<?php } ?>
