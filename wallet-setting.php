<?php
defined('ABSPATH') || exit;

if(isset($userDetail) && !empty($userDetail)) {
    if (!function_exists('krepling_wc_session_get')) {
        require_once __DIR__ . '/krepling-session.php';
    }
    $krepling_config_data = get_option( 'woocommerce_krepling_settings' );
    $krepling_remembered_email = sanitize_email((string) krepling_cookie_value('kp_user_email', ''));
    $krepling_plugin_text_color = esc_attr($krepling_config_data['plugin-text-color'] ?? '');
    $krepling_login_email = krepling_wc_session_login_email();
    $krepling_current_browser_name = sanitize_text_field((string) krepling_wc_session_get('current_browserName', ''));
    $krepling_current_device_location = sanitize_text_field((string) krepling_wc_session_get('current_deviceLocation', ''));
    $krepling_user_id = krepling_wc_session_user_id();
    $krepling_phone_set_default = (int) krepling_wc_session_get('phoneSetDefault', 0);
    $krepling_email_set_default = (int) krepling_wc_session_get('emailSetDefault', 0);
    $krepling_is_phone_verified = (int) krepling_wc_session_get('isPhoneVerified', 0);
    $krepling_is_email_verified = (int) krepling_wc_session_get('isEmailVerified', 0);
    $krepling_review_device_data = krepling_wc_session_review_devices();
    $krepling_user_vm = isset($userDetail->userVM) && is_object($userDetail->userVM) ? $userDetail->userVM : null;
?>
<!-- Start wallet setting page -->
<div class="addchangedmodel wallet_setting_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_wallet_setting"><i class="fa fa-angle-left" aria-hidden="true"></i>  Account Settings</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="walletsetting-body">
                <div class="row">
                    <div class="col-md-12 wallet-mb">
                        <span class="pass-security">Password and Security</span>
                        <div class="wallet-content common-flex wallet_change_password">
                            <div class="walletsetting-group common-wallet-content">
                                <h4>Change Password</h4>
                                <p>Update your login password.</p>
                            </div>
                            <i class="fa fa-angle-right" ></i>
                        </div>
                        <div class="wallet-content common-flex wallet_authentication">							  
                            <div class="walletsetting-group common-wallet-content">
                                <h4>Two-Factor Authentication</h4>
                                <p>Update your security method.</p>
                            </div>
                            <i class="fa fa-angle-right"></i>
                        </div>
                        <div class="wallet-content common-flex wallet_loggedIn_devices">	
                            <div class="walletsetting-group common-wallet-content">
                                <h4>Where you’re logged in</h4>
                                <p>Keep your account safe by reviewing your login<br> activity. <span >Set up alerts</span></p>
                            </div>
                            <i class="fa fa-angle-right" ></i>
                        </div>
                        <div class="save_payment_details_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 wallet-mb">
                        <span class="pass-security">Preferences</span>
                        <div class="kreplingfast-content">
                            <div class="kreplingfast-group common-wallet-content">
                                <h4>Save Payment Details</h4>
                                <p>Bypass confirming your billing profile and<br> instantly check out.</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="login_iskreplingFastId" <?php if(!empty($krepling_remembered_email) && $krepling_remembered_email == $krepling_login_email){ echo "checked";} ?> >
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <span class="pass-security">Login</span>
                        <div class="wallet-login common-flex wallet_logout">
                            <div class="walletsetting-login-group common-wallet-content">
                                <h4>Log out</h4>
                                <p>Unauthorize and sign out of this device.</p>
                            </div>
                            <i class="fa fa-angle-right" ></i>
                        </div>
                        <div class="wallet-login common-flex wallet_delete">
                            <div class="walletsetting-login-group common-wallet-content">
                                <h4>Delete Account</h4>
                                <p>Permanently close your account.</p>
                            </div>
                            <i class="fa fa-angle-right" ></i>
                        </div>
                    </div>	
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet setting page -->

<!-- Start wallet change password -->
<div class="addchangedmodel wallet_changePassword_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_change_password"><i class="fa fa-angle-left" aria-hidden="true"></i> Change Password</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="changepassword-content">
                    <p>You’ll be logged out of all sessions except this <br>one to protect your account if anyone is trying <br>to gain access.<br></p>
                    <p>Your password must be at least six characters <br>and should include a combination of numbers<br> letters and special characters (!$@%)</p>
                </div>
                <div class="change_pass">
                    <div class="form-group fade-in form-bg">
                        <input type="password" class="form-control" id="user_password" placeholder="Current Password">
                        <i id="togglePassword" class="hidePassword">
                            <svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>
                                <path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>
                                <rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>
                                <rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>
                            </svg>
                        </i>
                        <span class="hideclass" id="userOldPasswordError"></span>                           
                    </div>							
                    <div class="form-group fade-in form-bg">
                        <input type="password" class="form-control" id="newPassword" placeholder="New Password">
                        <i id="toggleNewPassword">
                            <svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>
                                <path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>
                                <rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>
                                <rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>
                            </svg>
                        </i>
                        <span class="hideclass" id="userNewPasswordError"></span>                           
                    </div>
                    <div class="form-group fade-in form-bg ">
                        <input type="password" class="form-control" id="repassword" placeholder="Retype Password">
                        <i id="toggleRePassword">
                            <svg width="25" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.8357 7.37087C23.6394 7.15182 18.916 2 13 2C7.08399 2 2.3607 7.15182 2.16429 7.37087C1.94524 7.61571 1.94524 7.98584 2.16429 8.23068C2.3607 8.44973 7.08408 13.6015 13 13.6015C18.9159 13.6015 23.6393 8.44973 23.8357 8.23068C24.0547 7.98584 24.0547 7.61571 23.8357 7.37087ZM13 12.3125C10.5125 12.3125 8.4883 10.2883 8.4883 7.80077C8.4883 5.31327 10.5125 3.28906 13 3.28906C15.4875 3.28906 17.5117 5.31327 17.5117 7.80077C17.5117 10.2883 15.4875 12.3125 13 12.3125Z" fill="#AAAAAA"/>
                                <path d="M13.6445 6.51172C13.6445 5.86332 13.9664 5.29291 14.4561 4.94207C14.0167 4.71713 13.5266 4.57812 13 4.57812C11.2231 4.57812 9.77734 6.02389 9.77734 7.80078C9.77734 9.57766 11.2231 11.0234 13 11.0234C14.5909 11.0234 15.9076 9.86181 16.1677 8.34399C14.8698 8.76186 13.6445 7.78024 13.6445 6.51172Z" fill="#AAAAAA"/>
                                <rect x="3.57251" width="25.0993" height="2.52328" rx="1.26164" transform="rotate(31.6448 3.57251 0)" fill="#AAAAAA"/>
                                <rect x="2.24878" y="2.14844" width="25.0993" height="2.52328" transform="rotate(31.6448 2.24878 2.14844)" fill="white"/>
                            </svg>
                        </i>
                        <span class="hideclass" id="userRetypePasswordError"></span>                           
                    </div>
                    <span class="forgetpass" onclick="forgotPassword('<?php echo esc_js($krepling_login_email)?>')">Forgotten your password?</span>
                </div>
                <div class="changepassword_section toster_msg_section hideclass">
                    <div class=" common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="forget-alert" style="display:none">
                    <span>We sent an email to <b><?php 
                        if(!empty($krepling_login_email)){
                            echo esc_html($krepling_login_email);
                        }?></b> with a link to reset your password</span>
                </div>
            
                <div class="flex-btn">
                    <input type="button" id="commonbtn" value="Change Password" class="btn currentbtn float-right change_password_btn">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet change password -->

<!-- Start wallet logout -->
<div class="addchangedmodel wallet_logout_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_logout"><i class="fa fa-angle-left" aria-hidden="true"></i> Log Out</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="row">
                    <div class="col-md-12">
                        <div class="logout-content delet-content">
                            <p>Logging out will unauthorize this device.</p>
                            <div class="logout_section toster_msg_section hideclass">
                                <div class="common_toster">
                                    <span class="toster_message"></span>
                                    <span><i class="fa fa-times"></i></span>
                                </div>
                            </div>
                        </div>
						<div class="flex-btns">
                    <input type="button" id="commonbtn" value="Confirm" class="btn currentbtn logout_btn">
                    <input type="button" id="cancel" value="Cancel" class="btn back_logout">
                </div>
                    </div>	
                </div>
                
            </div>
        </div>
    </div>
</div>
<!-- End wallet logout -->

<!-- Start wallet delete account -->
<div class="addchangedmodel wallet_delete_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_delete"><i class="fa fa-angle-left" aria-hidden="true"></i> Delete Account</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="row">
                    <div class="col-md-12">
                        <div class="delet-content">
                            <p>Deleting your account will result in permanent closure and the irreversible loss of all your data.</p>
                            <div class="deleteaccount_section toster_msg_section hideclass">
                                <div class="common_toster">
                                    <span class="toster_message"></span>
                                    <span><i class="fa fa-times"></i></span>
                                </div>
                            </div>
                        </div>
						<div class="flex-btns">
                            <input type="button" id="commonbtn" onclick="deleteUserAccount(<?php echo absint($krepling_user_id)?>)" value="Confirm" class="btn currentbtn delete_btn">
                            <input type="button" id="cancel" value="Cancel" class="btn back_delete">
                        </div>
                    </div>	
                </div>
                
            </div>
        </div>
    </div>
</div>
<!-- End wallet delete account -->

<!-- Start wallet two-step authentication -->
<div class="addchangedmodel wallet_authentication_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_authentication"><i class="fa fa-angle-left" aria-hidden="true"></i>  Two-Factor Authentication</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="authentication-body">
                <div class="authentication_section toster_msg_section hideclass">
                    <div class="common_toster">
                        <span class="toster_message"></span>
                        <span><i class="fa fa-times"></i></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 wallet-mb">	 	 
                        <div class="authentication-content common-flex">
                            <div class="authentication-group common-wallet-content">
                                <h4>Text message <b style="display: <?php echo esc_attr($krepling_phone_set_default === 1 ? 'inline-block' : 'none'); ?>">(Default) </b> 
                                <a class="defaltbtn" style="display: <?php echo esc_attr($krepling_phone_set_default === 1 ? 'none' : 'initial'); ?>">Set Default</a></h4>
                                <p>(<?php echo esc_html((string) $krepling_user_vm->countryCode); ?>) <?php echo esc_html((string) $krepling_user_vm->phone); ?>
								<span class="authentication_phone_group">
                                    <?php if($krepling_is_phone_verified === 1){ ?>
                                        <span class="authentication-verified"><a>Verified</a></span>
                                    <?php }else{?>
                                        <span class="authentication-email">Not yet verified </span>
                                        <span class="resend" id="resend_sms"> <i class="fa fa-circle"></i>Resend SMS</span>
                                    <?php } ?>
								</span>
								<br>
                                    Verification codes are sent by text message.</p>
                            </div>
                            <span class="authentication-change" id="update_phoneNumber"><a>Change</a></span>
                        </div>
                        <div class="verifyphoneotp_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                        <div class="authentication-content common-flex">							  
                            <div class="authentication-group common-wallet-content">
                                <h4>Email Verification <b style="display: <?php echo esc_attr($krepling_email_set_default === 1 ? 'inline-block' : 'none'); ?>">(Default) </b>
                                    <a class="defaltbtn" style="display: <?php echo esc_attr($krepling_email_set_default === 1 ? 'none' : 'initial'); ?>">Set Default</a></h4>
                                <p><?php 
                                    if(!empty($krepling_login_email)){
                                        echo esc_html($krepling_login_email);
                                    }?>
									<span class="authentication_email_group">
                                        <?php if($krepling_is_email_verified === 1){ ?>
                                            <span class="authentication-verified"><a>Verified</a></span>
                                        <?php }?>
                                    </span>
                                    <br>
                                        Verification codes are sent by email.</p>
                            </div>
                            <span class="authentication-change" id="update_emailAddress"><a>Change</a></span>
                        </div>
                        <div class="verifyemailotp_section toster_msg_section hideclass">
                            <div class="common_toster">
                                <span class="toster_message"></span>
                                <span><i class="fa fa-times"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet two-step authentication -->

<!-- Start wallet logged in devices -->
<div class="addchangedmodel wallet_loggedIn_devices_page" style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?>                           
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_loggedIn_devices"><i class="fa fa-angle-left" aria-hidden="true"></i>   Where You’re Logged In</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="whereyoulogged-body">
                <div class="whereloginmsg">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.0086 1.51398e-05C17.0291 -0.00998715 21.9815 4.93739 21.9996 10.9807C22.0177 17.0927 17.091 22.0269 10.8999 21.9994C4.90318 21.9732 0.0364408 17.1427 0.00019181 11.0682C-0.0354322 4.94177 4.89568 0.0100174 11.0086 1.51398e-05ZM11.0143 2.00422C6.05065 2.00297 2.00389 6.02139 1.99701 10.9581C1.99014 15.9537 6.00003 20.0002 10.9649 20.0083C15.9491 20.0165 19.9947 15.978 19.9959 10.9938C19.9978 6.04827 15.9585 2.0061 11.0136 2.00485L11.0143 2.00422Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                        <path d="M11.9909 9.51967C11.9909 10.6912 11.9959 11.8627 11.9884 13.0342C11.9859 13.4656 11.7097 13.8238 11.3166 13.9551C10.9185 14.0876 10.4854 13.9626 10.2197 13.6337C10.0779 13.4587 10.0022 13.2574 10.0022 13.0298C10.0022 10.6787 9.99787 8.32814 10.0054 5.97698C10.0072 5.42435 10.456 5.00426 10.9991 5.00488C11.541 5.00613 11.9834 5.42623 11.9884 5.98136C11.9984 7.16038 11.9916 8.34002 11.9916 9.51967H11.9909Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                        <path d="M10.0011 15.9867C10.0092 15.4372 10.4661 14.9927 11.0098 15.0046C11.5711 15.0165 12.0036 15.4729 11.9892 16.0392C11.9761 16.5875 11.5204 17.0195 10.9692 17.0076C10.4223 16.9951 9.99236 16.5425 10.0011 15.9874V15.9867Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                    </svg>
                    <p>We detect unrecognized logins. <a href ="#" class="wallet_review_devices" id="review_devices">Review devices</a></p>              
                </div> 
                <div class="whereyoulogged common-flex wallet_login_alerts">
                    <div class="whereyoulogged-group">
                        <h4>Login Alerts</h4>
                        <p>Manage how you'd like to be notified about<br> unrecognized logins to your account.</p>
                    </div>
                    <i class="fa fa-angle-right"></i>
                </div>
                <div class="whereyoulogged common-flex">
                    <div class="whereyoulogged-group apple-content">
                        <svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="wherelogeed-img">
                            <rect width="15.8317" height="10.4868" rx="1" transform="matrix(-1 0 0 1 18.6904 1)" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                            <path d="M18.972 12.0172C18.7984 11.6908 18.4588 11.4868 18.0891 11.4868H3.48104C3.09975 11.4868 2.75163 11.7036 2.58351 12.0459L0.707564 15.8644C0.381124 16.5288 0.864774 17.3053 1.6051 17.3053H20.1205C20.8749 17.3053 21.3577 16.5017 21.0033 15.8356L18.972 12.0172Z" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                            <path d="M13.0361 15H8.58923" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4><?php echo esc_html($krepling_current_browser_name) ?></h4>
                        <p><?php echo esc_html($krepling_current_device_location) ?><i class="fa fa-circle"></i> <span>This device</span></p>
                    </div>
                </div>
                <?php if(!empty($krepling_review_device_data)){ ?>
                    <div class="whereyoulogged-option common-flex">
                        <h4>Other logins</h4>
                        <span class="selectallDevices">Select all</span>
                    </div>
                    <?php foreach($krepling_review_device_data as  $krepling_device){ ?>
                        <div class="whereyourother-option common-flex" id="whereyourother-option-<?php echo absint($krepling_device->id) ?>">
                            <div class="whereyoulogged-group apple-content">
                                <svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="wherelogeed-img">
                                    <rect width="15.8317" height="10.4868" rx="1" transform="matrix(-1 0 0 1 18.6904 1)" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                    <path d="M18.972 12.0172C18.7984 11.6908 18.4588 11.4868 18.0891 11.4868H3.48104C3.09975 11.4868 2.75163 11.7036 2.58351 12.0459L0.707564 15.8644C0.381124 16.5288 0.864774 17.3053 1.6051 17.3053H20.1205C20.8749 17.3053 21.3577 16.5017 21.0033 15.8356L18.972 12.0172Z" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                    <path d="M13.0361 15H8.58923" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <h4><?php echo esc_html((string) $krepling_device->deviceName) ?></h4>
                                <p><?php echo esc_html((string) $krepling_device->location) ?> <i class="fa fa-circle"></i> <?php echo esc_html((string) $krepling_device->time) ?></p>
                            </div>
                            <input type="checkbox" name="radiosButton" class="selectDevices" value="<?php echo absint($krepling_device->id) ?>">
                        </div>
                    <?php } ?>
                    <div class="flex-btn">
                        <input type="button" id="commonbtn" value="Log Out" class="btn currentbtn float-right fadeInOut logout_allDevices disabled" disabled>                 
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<!-- End wallet logged in devices -->

<!-- Start wallet login alert -->
<div class="addchangedmodel wallet_login_alert_page" style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_loginAlert"><i class="fa fa-angle-left" aria-hidden="true"></i>  Login Alerts</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="authentication-body">
                <div class="wallet-mb">
                        <div class="authentication-content common-flex loginalert">
                            <div class="authentication-group common-wallet-content login-alert-flex">
                            <svg width="22" height="18" viewBox="0 0 48 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M47.3998 4.99962L45.4998 3.09961L27.8998 20.6996C25.6998 22.8996 21.9998 22.8996 19.7998 20.6996L2.19981 3.19962L0.299805 5.09961L13.3998 18.1996L0.299805 31.2996L2.19981 33.1996L15.2998 20.0996L17.8998 22.6996C19.4998 24.2996 21.5998 25.1996 23.7998 25.1996C25.9998 25.1996 28.0998 24.2996 29.6998 22.6996L32.2998 20.0996L45.3998 33.1996L47.2998 31.2996L34.1998 18.1996L47.3998 4.99962Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                <path d="M43.7 36.6H4.2C1.9 36.6 0 34.7 0 32.4V4.2C0 1.9 1.9 0 4.2 0H43.7C46 0 47.9 1.9 47.9 4.2V32.4C47.9 34.7 46 36.6 43.7 36.6ZM4.1 2.7C3.3 2.7 2.7 3.29999 2.7 4.09999V32.3C2.7 33.1 3.3 33.7 4.1 33.7H43.6C44.4 33.7 45 33.1 45 32.3V4.09999C45 3.29999 44.4 2.7 43.6 2.7H4.1Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                            </svg>
                            <h4>Email</h4>
                            <p><?php 
                                if(!empty($krepling_login_email)){
                                    echo esc_html($krepling_login_email);
                                }?>
                            <span class="authentication-verified"><a>Always on</a></span><br></p>
                        </div>
                    </div>
                    <div class="authentication-content common-flex loginalert">							  
                        <div class="authentication-group common-wallet-content login-alert-flex">
                            <svg width="22" height="18" viewBox="0 0 219 209" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="59.75" y="43.7158" width="99.5" height="10.8946" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="2"/>
                                <rect x="59.75" y="73.4727" width="99.5" height="10.8946" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="2"/>
                                <rect x="59.75" y="103.229" width="99.5" height="10.8946" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="2"/>
                                <path d="M192.545 8H26.4545C21.5601 8 16.8661 9.88105 13.4052 13.2293C9.94431 16.5776 8 21.1189 8 25.8541V132.978C8 137.714 9.94431 142.255 13.4052 145.603C16.8661 148.951 21.5601 150.832 26.4545 150.832H40.2955V191.5L91.0455 150.832H192.545C197.44 150.832 202.134 148.951 205.595 145.603C209.056 142.255 211 137.714 211 132.978V25.8541C211 21.1189 209.056 16.5776 205.595 13.2293C202.134 9.88105 197.44 8 192.545 8Z" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-width="15" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M33 207.5L33.5 183H35.5L43.5 184L54 189.5L33 207.5Z" fill="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                            </svg>
                            <h4>SMS</h4>
                            <p>(<?php echo esc_html((string) $krepling_user_vm->countryCode)?>) <?php echo esc_html((string) $krepling_user_vm->phone); ?><br></p>
                        </div>
                        <span class="authentication-change login-alert">
                            <input type="checkbox" class="login-alert-sms" <?php if(isset($krepling_user_vm->smsLoginAlert) && $krepling_user_vm->smsLoginAlert == true){ echo "checked";} ?>>
                        </span>
                    </div>
                    <div class="smsloginalert_section toster_msg_section hideclass">
                        <div class="common_toster">
                            <span class="toster_message"></span>
                            <span><i class="fa fa-times"></i></span>
                        </div>
                    </div>
                </div>
            </div>       
        </div>
    </div>
</div>
<!-- End wallet login alert -->                 

<!-- Start wallet change phone number(two step authentication) -->
<div class="addchangedmodel change_phonenumber_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_to_authentication"><i class="fa fa-angle-left" aria-hidden="true"></i>   Change Phone Number</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="form-group fadeIn">
                    <label for="otp_phoneNumber">New Phone Number</label><br>
                    <input type="tel" class="form-control" id="otp_phoneNumber" maxlength="14" style="padding-left: 65px;" autocomplete="off">
                    <span class="hideclass" id="updatePhoneNumberError"></span>
                </div>
            </div>
            <div class="changephonenumber_section toster_msg_section hideclass">
                <div class="common_toster">
                    <span class="toster_message"></span>
                    <span><i class="fa fa-times"></i></span>
                </div>
            </div>
        </div>
        <div class="flex-btn">
            <input type="button" id="commonsubmit" value="Continue" class="btn currentbtn float-right btn_change_phonenumber">
        </div>   
    </div>
</div>
<!-- End wallet change phone number(two step authentication) --> 

<!-- Start wallet change email address(two step authentication) -->
<div class="addchangedmodel change_emailaddress_page" style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_to_authentication"><i class="fa fa-angle-left" aria-hidden="true"></i>  Change Email Address</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cahnge_email">
                <div class="form-group fadeIn">
                    <label for="otp_email">New Email Address</label>
                    <input type="email" class="form-control" id="otp_email" placeholder="e.g. john.doe@example.com" autocomplete="off">  
                    <span class="hideclass" id="updateEmailAddressError"></span>
                </div>
            </div>
            <div class="changeemail_section toster_msg_section hideclass">
                <div class="common_toster">
                    <span class="toster_message"></span>
                    <span><i class="fa fa-times"></i></span>
                </div>
            </div>
        </div>
        <div class="flex-btn">
            <input type="button" id="commonsubmit" value="Continue" class="btn currentbtn float-right btn_change_emailaddress">
        </div>    
    </div>
</div>
<!-- End wallet change email address(two step authentication) --> 

<!-- Start wallet change phone number otp page(two step authentication) -->
<div class="addchangedmodel change_phonenumber_otp_page"  style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_change_phonenumber"><i class="fa fa-angle-left" aria-hidden="true"></i>   Change Phone Number</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="form-group phone_code">
                    <label class="fadeInBottom"><b>New Phone Number</b></label><br>
                    <span id="display_otp_phone"></span>
                    <div class="phonecodeborder"></div>
                </div>
                <h5 class=" animate fadeInDown phonecodetext">Enter the verification code sent to your phone number.</h5>
                <div class="otp_blocks">
                    <div id="phonecodeinput" class="phonecodeinputmt-4">
                        <div class="verification-code">
                            <div class="phone-verification-code">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" id="verify_phone_number_otp" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <span class="hideclass" id="verifyPhoneNumberOtpError"></span>
                            </div>
                        </div>
                    </div>           
                </div>
            </div>
            <div class="text-center mt-3 box animate fadeInDown two" id="sendEmailForVerificationId">
                <span class="sms_content"> <strong>Didnt receive an SMS?</strong> <a class="changeBtnStatusId" id="sendChangePhoneOtpAgain">Send again</a></span>
                <span class="countdown"></span>
            </div>
            <div class="sendphoneotp_section toster_msg_section hideclass">
                <div class="common_toster">
                    <span class="toster_message"></span>
                    <span><i class="fa fa-times"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet change phone number otp page(two step authentication) -->

<!-- Start wallet change email address otp page(two step authentication) -->
<div class="addchangedmodel change_emailaddress_otp_page"  style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_change_emailaddress"><i class="fa fa-angle-left" aria-hidden="true"></i>   Change Email Address</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="change_email_code">
                <div class="form-group emai_code">
                    <label class="fadeInBottom"><b>New Email Address</b></label><br>
                    <span id="display_otp_email"></span>
                    <div class="emailcodeborder"></div>
                </div>
                <h5 class=" animate fadeInDown phonecodetext">Enter the verification code sent to your email address.</h5>
                <div class="otp_blocks_email">
                    <div id="otp_blocks" class="otp_blocks">
                        <div class="emailverification-code">
                            <div class="SMSArea-code">
                                <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <input type="tel" id="verify_email_address_otp" maxlength="1" class="smsCode text-center rounded-lg checkvalue">
                                <span class="hideclass" id="verifyEmailAddressOtpError"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3 box animate fadeInDown two" id="sendEmailForVerificationId">
                <span class="sms_content"> <strong>Didnt receive an email?</strong> <a class="changeBtnStatusId" id="sendChangeEmailOtpAgain">Send again</a></span>
                <span class="countdown"></span>
            </div>
            <div class="sendemailotp_section toster_msg_section hideclass">
                <div class="common_toster">
                    <span class="toster_message"></span>
                    <span><i class="fa fa-times"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet change email address otp page(two step authentication) -->

<!-- Start wallet Review Devices -->
<div class="addchangedmodel review_devices_page" style="display:none">
    <div class="setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_loginAlert"><i class="fa fa-angle-left" aria-hidden="true"></i>   Unrecognized Logins</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reviewdevices-body">
                <div class="reviewdevicesmsg">
                    <p>If you don’t recognize a login, we’ll help you log out and secure your account.</p>
                </div>
                <h4 class="recognize-text">Do you recognize these logins?</h4>
                <?php if(!empty($krepling_review_device_data)){
                    foreach($krepling_review_device_data as  $krepling_device){ ?>
                        <div class="reviewdevice common-flex " id="reviewdevice-<?php echo absint($krepling_device->id) ?>">
                            <div class="reviewdevice-group reviewdevice-content">
                                <svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="wherelogeed-img">
                                    <rect width="15.8317" height="10.4868" rx="1" transform="matrix(-1 0 0 1 18.6904 1)" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                    <path d="M18.972 12.0172C18.7984 11.6908 18.4588 11.4868 18.0891 11.4868H3.48104C3.09975 11.4868 2.75163 11.7036 2.58351 12.0459L0.707564 15.8644C0.381124 16.5288 0.864774 17.3053 1.6051 17.3053H20.1205C20.8749 17.3053 21.3577 16.5017 21.0033 15.8356L18.972 12.0172Z" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>"/>
                                    <path d="M13.0361 15H8.58923" stroke="<?php echo esc_attr($krepling_plugin_text_color); ?>" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <h4><?php echo esc_html((string) $krepling_device->deviceName) ?></h4>
                                <p><?php echo esc_html((string) $krepling_device->location) ?> <i class="fa fa-circle"></i> <?php echo esc_html((string) $krepling_device->time) ?></p>
                            </div>
                        </div>
                        <div class="flex-btns review-btns" id="review-btns-<?php echo absint($krepling_device->id)?>">
                            <input type="button" id="reviewbtn" value="This Wasn’t Me" class="btn" onClick="thisDeviceWasnotMe(<?php echo wp_json_encode((string) $krepling_device->location)?>, <?php echo wp_json_encode((string) $krepling_device->deviceName)?>, <?php echo absint($krepling_device->id); ?>)">
                            <input type="button" id="reviewbtn" value="This Was Me" class="btn validUserDevice" onClick="thisDeviceWasMe(<?php echo absint($krepling_device->id)?>)">
                        </div>
                        <!--review device popup-->
                        <div class="toasts review_toasts" style="display: none;" id="review_device_popup_<?php echo absint($krepling_device->id)?>">
                            <div class="flex_group">
                                <div class="toast__content">
                                    <p class="toast__message" id="review_device_text"></p>
                                </div>
                                <div class="undo_messsage" id="review_device_reset_password">
                                    <span><a>Change Password</a></span>
                                </div>
                                <div class="toast__close">
                                    <span class="close_btn close_review_device_btn" deviceid="<?php echo absint($krepling_device->id)?>">×</span>
                                </div>
                            </div>
                        </div>
                        <!--review device popup end-->
                <?php } } ?>
            </div>
        </div>
    </div>
</div>
<!-- End wallet Review Devices -->

<!-- Start wallet verify resend otp page(two step authentication) -->
<div class="addchangedmodel verify_resend_otp_page"  style="display:none">
    <div class=" setCenter">
        <div class=" addcard-content bbox">
            <div class="card-header6">
                <div class="modal-header modalheaderBg box2">
                    <div class="add-card-svg">
                        <?php include('setting-header.php'); ?> 
                        <div class="wallet-heading">
                            <h2 class="cssanimation sequence zoomInDown back_resend_otp"><i class="fa fa-angle-left" aria-hidden="true"></i>   Verify OTP</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addnewpass">
                <div class="verify_otp_resend_phoneNumber">
                    <div class="form-group phone_code">
                        <label class="fadeInBottom"><b>Phone Number</b></label><br>
                        <span id="display_resend_otp_field"></span>
                        <div class="phonecodeborder"></div>
                    </div>
                    <h5 class=" animate fadeInDown phonecodetext">Enter the verification code sent to your phone number.</h5>
                </div>
                <div class="otp_blocks">
                    <div id="phonecodeinput" class="phonecodeinputmt-4">
                        <div class="verification-code">
                            <div class="resendPhone-verification-code">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <input type="tel" id="verify_resendOtp_phoneNumber" maxlength="1" class="phoneCode text-center rounded-lg checkvalue">
                                <span class="hideclass" id="verifyResendPhoneNumberOtpError"></span>
                            </div>
                        </div>
                    </div>           
                </div>
            </div>
            <div class="text-center mt-3 box animate fadeInDown two" id="sendEmailForVerificationId">
                <span class="sms_content"> <strong>Didnt receive an SMS?</strong> <a class="changeBtnStatusId" id="resendSmsOtpAgain">Send again</a></span>
                <span class="countdown"></span>
            </div>
            <div class="resendsmsotp_section toster_msg_section hideclass">
                <div class="common_toster">
                    <span class="toster_message"></span>
                    <span><i class="fa fa-times"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End wallet verify resend otp page(two step authentication) -->
<?php } ?>
