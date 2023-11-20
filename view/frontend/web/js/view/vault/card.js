/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'uiComponent',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/cc-form',
        'Swarming_SubscribePro/js/action/vault/save-cart',
        'Magento_Customer/js/customer-data',
        'mage/url',
        'paymentFields',
        'mage/translate',
        'Swarming_SubscribePro/js/lib/jquery.serializejson.min'
    ],
    function ($, Component, config, CcForm, saveCart, customerData, urlBuilder, PaymentFields, $t) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                formSelector: "#vault-edit",
                formSubmitSelector: "#vault-edit .save",
                isLoading: false,
                creditCardLastDigits: null,
                paymentMethodToken: null,
                selectedCardType: null,
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading',
                        'creditCardLastDigits',
                        'paymentMethodToken',
                        'selectedCardType',
                    ]);
                var self = this;
                $(self.formSubmitSelector).click(function(e) {
                    if ($(self.formSelector).valid() && self.paymentMethodToken() === null) {
                        e.preventDefault();
                        $('body').trigger('processStart');
                        self.tokenizeCard();
                        return false;
                    }
                });
                return this;
            },

            initPaymentFields: function () {
                PaymentFields.on('tokenize', (data) => {
                    if (data.isSuccessful === true) {
                        this.selectedCardType(data.creditCard.cardType)
                        this.creditCardLastDigits(data.creditCard.cardLastDigits)
                        this.paymentMethodToken(data.tokenString);
                        this.submitPayment();
                    }
                });

                PaymentFields.on('error', (data) => {
                    $('body').trigger('processStop');
                    console.log(`'error' event received.`);
                    console.log(data);
                });

                PaymentFields.on('inputEvent', (data) => {
                    console.log(`'inputEvent' event received.`);
                });

                PaymentFields.on('challengeShown', (data) => {
                    $('body').trigger('processStop');
                    console.log(`'challengeShown' event received.`);
                    console.log(data);
                });

                PaymentFields.on('challengeHidden', (data) => {
                    $('body').trigger('processStop');
                    console.log(`'challengeHidden' event received.`);
                    console.log(data);
                });
                let authConfig = config.getConfig().sessionAccessToken;
                PaymentFields.init({
                    apiBaseUrl: 'https://api.subscribepro.com/',
                    oauthApiToken: authConfig.access_token,
                    spVaultEnvironmentId: authConfig.sp_vault_environment_id,
                    paymentMethodType: 'credit_card',
                    enableThreeDs: config.isThreeDSActive(),
                    enableCvv: true,
                    numberIframe: {
                        container: this.getCode() + '_cc_number',
                        inputStyle:
                            'width: 100%; padding: 5px 8px; line-height: 20px; font-size: 14px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";',
                    },
                    cvvIframe: {
                        container: this.getCode() + '_cc_cid',
                        inputStyle:
                            'width: 100%; padding: 5px 8px; line-height: 20px; font-size: 14px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";',
                    },
                    threeDsChallengeIframe: {
                        container: 'challenge',
                    },
                });
            },

            getPaymentData: function () {
                return {
                    'first_name': $("#first_name").val(),
                    'last_name': $("#last_name").val(),
                    'company': $("#company").val(),
                    'phone_number': $("#telephone").val(),
                    'address1': $("#street1").val(),
                    'address2': $("#street2").val(),
                    'address3': $("#street3").val(),
                    'city': $("#city").val(),
                    'state': $("#region_id option:selected").text(),
                    'zip': $("#postcode").val(),
                    'country': $("#country").val(),
                    'year': $("#subscribe_pro_expiration_yr").val(),
                    'month': $("#subscribe_pro_expiration").val()
                };
            },

            initPayment: function () {
                this.initPaymentFields();
            },

            tokenizeCard: function () {
                PaymentFields.tokenize({
                    authenticateCardholder: config.isThreeDSActive(),
                    authenticationType: 'non_payment',
                    paymentDetails: {
                        amount: config.getConfig().wallet_authorization_amount,
                    },
                    cardDetails: {
                        creditcardMonth: $("#subscribe_pro_expiration").val(),
                        creditcardYear: $("#subscribe_pro_expiration_yr").val(),
                    },
                    customerEmail: customerData.get('customer')().email,
                    billingAddress: this.getPaymentData(),
                    shippingAddress: this.getPaymentData(),
                });
            },

            submitPayment: function () {
                var cartData = $(this.formSelector).serializeJSON();

                this.isLoading(true);
                saveCart(cartData).done(this.onSaveCart.bind(this));
                $(this.formSubmitSelector).attr('disabled', 'disabled');
            },

            onSaveCart: function (response) {
                $(window).scrollTop(0);
                this.isLoading(false);
                if (response.state === 'succeeded') {
                    this.onOrderSuccess();
                }
            },

            onOrderSuccess: function () {
                $(window).scrollTop(0);
                $.cookieStorage.set('mage-messages', [{'type': 'success', 'text': 'The card was successfully saved.'}]);
                window.location.href = urlBuilder.build('vault/cards/listaction');
            }
        });
    }
);
