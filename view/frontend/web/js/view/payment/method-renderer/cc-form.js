/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/action/checkout/get-order-status',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'paymentFields',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate'
    ],
    function ($, Component, CcForm, config, getOrderStatus, quote, customer,placeOrder, PaymentFields, redirectOnSuccessAction, $t) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                template: 'Swarming_SubscribePro/payment/cc-form',
                canPlaceOrder: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'canPlaceOrder'
                    ]);

                $(document).on('subscribepro:orderPlaceAfter', $.proxy(this.onOrderPlaceAfter, this));
                return this;
            },

            initialize: function () {
                this._super();
                this.canPlaceOrder(true);
            },

            updateSaveActionAllowed: function () {
                this.canPlaceOrder(true)
            },

            isActive: function () {
                return this.getCode() == this.isChecked();
            },

            initPayment: function () {
                this.initPaymentFields();
                this.redirectAfterPlaceOrder = false;
            },
            startPlaceOrder: function () {
                this.validatePayment();
                if (this.isValidNumber() && this.isValidCvv() && this.isValidExpDate) {
                    $('body').trigger('processStart');
                    this.tokenizeCard();
                }
            },

            initPaymentFields: function () {
                PaymentFields.on('tokenize', (data) => {
                    $('body').trigger('processStop');
                    if (data.isSuccessful === true) {
                        this.selectedCardType(data.creditCard.cardType)
                        this.creditCardFirstDigits(data.creditCard.cardIssuerIdentificationNumber.substring(0, 4))
                        this.creditCardLastDigits(data.creditCard.cardLastDigits)
                        this.paymentMethodToken(data.tokenString);
                        placeOrder(this.getData()).done(function () {
                            this.onOrderSuccess();
                        }.bind(this));
                    }
                });

                PaymentFields.on('error', (data) => {
                    $('body').trigger('processStop');
                    console.log(`'error' event received.`);
                    console.log(data);
                });

                PaymentFields.on('inputEvent', (data) => {
                    if (data.eventType === 'blur') {
                        this.validationPaymentData(data.fieldCode);
                    }
                    console.log(data);
                    console.log(`'inputEvent' event received.`);
                });

                PaymentFields.on('validationResultChanged', (data) => {
                    if (data !== undefined) {
                        this.ccValidationData(data.validationResult);
                    }
                    if (data.validationResult.cardType !== undefined) {
                        if (config.getCcTypesMapper()[data.validationResult.cardType] !== undefined) {
                            this.selectedCardType(config.getCcTypesMapper()[data.validationResult.cardType]);
                        }
                    }
                    console.log(data);
                    console.log(`'validationResultChanged' event received.`);
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
                let apiBaseUrl = config.getConfig().apiBaseUrl;
                PaymentFields.init({
                    apiBaseUrl: apiBaseUrl,
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

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'is_active_payment_token_enabler': customer.isLoggedIn(),
                        'payment_method_token': this.paymentMethodToken(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'creditcard_first_digits': this.creditCardFirstDigits(),
                        'creditcard_last_digits': this.creditCardLastDigits(),
                        'creditcard_type': this.selectedCardType(),
                    },
                };

                return data;
            },

            getPaymentData: function () {
                return {
                    'first_name': quote.billingAddress().firstname,
                    'last_name': quote.billingAddress().lastname,
                    'phone_number': quote.billingAddress().telephone,
                    'address1': quote.billingAddress().street[0],
                    'address2': quote.billingAddress().street[1] || '',
                    'address3': quote.billingAddress().street[2] || '',
                    'city': quote.billingAddress().city,
                    'state': quote.billingAddress().regionCode,
                    'zip': quote.billingAddress().postcode,
                    'country': quote.billingAddress().countryId,
                    'year': this.creditCardExpYear(),
                    'month': this.creditCardExpMonth()
                };
            },

            submitPayment: function () {
                this.placeOrder();
            },

            onOrderSuccess: function () {
                redirectOnSuccessAction.execute();
            },

            tokenizeCard: function () {
                PaymentFields.tokenize({
                    authenticateCardholder: config.isThreeDSActive(),
                    authenticationType: 'payment',
                    paymentDetails: {
                        amount: quote.getCalculatedTotal(),
                    },
                    cardDetails: {
                        creditcardMonth: this.creditCardExpMonth(),
                        creditcardYear: this.creditCardExpYear(),
                    },
                    customerEmail: customer.customerData.email,
                    billingAddress: {
                        firstName: quote.billingAddress().firstname,
                        lastName: quote.billingAddress().lastname,
                        street1: quote.billingAddress().street[0],
                        street2: quote.billingAddress().street[1] || '',
                        street3: quote.billingAddress().street[2] || '',
                        city: quote.billingAddress().city,
                        region: quote.billingAddress().regionCode,
                        postcode: quote.billingAddress().postcode,
                        country: quote.billingAddress().countryId,
                    },
                    shippingAddress: {
                        firstName: quote.shippingAddress().firstname,
                        lastName: quote.shippingAddress().lastname,
                        street1: quote.shippingAddress().street[0],
                        street2: quote.shippingAddress().street[1] || '',
                        street3: quote.shippingAddress().street[2] || '',
                        city: quote.shippingAddress().city,
                        region: quote.shippingAddress().regionCode,
                        postcode: quote.shippingAddress().postcode,
                        country: quote.shippingAddress().countryId,
                    },
                });
            }
        });
    }
);
