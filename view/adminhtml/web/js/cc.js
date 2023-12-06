/*browser:true*/
/*global define*/

define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Ui/js/modal/alert',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields',
        'Swarming_SubscribePro/js/model/payment/config',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'paymentFields'
    ],
    function (
        ko,
        $,
        Component,
        alert,
        expirationFieldValidator,
        hostedFieldValidator,
        expirationFields,
        hostedFields,
        config,
        domObserver,
        PaymentFields
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                $orderForm: null,
                orderFormSelector: 'edit_form',
                container: 'payment_form_subscribe_pro',
                active: false,
                isValidHostedFields: false,
                isValidExpDate: false,
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardExpMonthFocus: null,
                creditCardExpYearFocus: null,
                creditCardFirstDigits: null,
                creditCardLastDigits: null,
                paymentMethodToken: null,
                selectedCardType: null,
                ccValidationData: null,
                isValidNumber: false,
                isValidCvv: false,
                imports: {
                    onActiveChange: 'active'
                }
            },

            initObservable: function () {
                var self = this;
                this.$orderForm = $('#' + this.orderFormSelector);
                this._super()
                    .observe([
                        'active',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus',
                        'creditCardFirstDigits',
                        'creditCardLastDigits',
                        'paymentMethodToken',
                        'selectedCardType',
                        'ccValidationData',
                        'isValidNumber',
                        'isValidCvv',
                    ]);

                this.$orderForm.off('changePaymentMethod.' + config.getCode())
                    .on('changePaymentMethod.' + config.getCode(), this.changePaymentMethod.bind(this));
                domObserver.get('#' + this.container, function () {
                    self.initPaymentFields();
                });

                this.$orderForm
                    .on('focusout', this.getSelector('expiration'), $.proxy(this.validationCreditCardExpMonth, this, false))
                    .on('focusout', this.getSelector('expiration_yr'), $.proxy(this.validationCreditCardExpYear, this, false));

                return this;
            },

            getThreeDSBrowserInfo: function () {
                return JSON.stringify([config.getBrowserSize(), config.getAcceptHeader()])
            },

            changePaymentMethod: function (event, method) {
                this.active(method === config.getCode());
            },

            onActiveChange: function (isActive) {
                this.$orderForm.off('submitOrder.subscribe_pro');
                if (!isActive) {
                    return;
                }

                this.disableEventListeners();

                window.order.addExcludedPaymentMethod(config.getCode());

                this.enableEventListeners();
            },

            disableEventListeners: function () {
                this.$orderForm.off('submitOrder');
                this.$orderForm.off('submit');
            },

            enableEventListeners: function () {
                this.$orderForm.on('submitOrder.subscribe_pro', this.submitOrder.bind(this));
            },

            validatePayment: function () {
                this.validationPaymentData('number');
                this.validationPaymentData('cvv');
                this.validationCreditCardExpMonth(false);
                this.validationCreditCardExpYear(false);
            },

            validationCreditCardExpMonth: function (isFocused) {
                if (!$(this.getSelector('expiration')).val()) {
                    expirationFields.addClass('month', 'invalid');
                } else {
                    this.isValidExpDate = expirationFieldValidator(
                        isFocused,
                        'month',
                        $(this.getSelector('expiration')).val(),
                        $(this.getSelector('expiration_yr')).val()
                    );
                }
            },

            validationCreditCardExpYear: function (isFocused) {
                if (!$(this.getSelector('expiration_yr')).val()) {
                    expirationFields.addClass('year', 'invalid');
                } else {
                    this.isValidExpDate = expirationFieldValidator(
                        isFocused,
                        'year',
                        $(this.getSelector('expiration')).val(),
                        $(this.getSelector('expiration_yr')).val()
                    );
                }
            },

            validationPaymentData: function (input) {
                if (input === 'number') {
                    if (!this.ccValidationData() || this.ccValidationData().isNumberValid === false) {
                        this.isValidNumber(false);
                        hostedFields.addClass('number', 'invalid');
                    } else {
                        this.isValidNumber(true);
                        hostedFields.removeClass('number', 'invalid');
                    }
                }

                if (input === 'cvv') {
                    if (!this.ccValidationData() || this.ccValidationData().isCvvValid === false) {
                        this.isValidCvv(false);
                        hostedFields.addClass('cvv', 'invalid');
                    } else {
                        this.isValidCvv(true);
                        hostedFields.removeClass('cvv', 'invalid');
                    }
                }
            },


            submitOrder: function () {
                this.validatePayment();
                this.$orderForm.validate().form();
                this.$orderForm.trigger('afterValidate.beforeSubmit');
                $('body').trigger('processStop');
                if (this.$orderForm.validate().errorList.length) {
                    return false;
                }
                if (!this.isValidNumber() || !this.isValidCvv() || !this.isValidExpDate) {
                    alert({content: $.mage.__('Enter valid payment information.')});
                    return false;
                }
                if (this.isValidNumber() && this.isValidCvv() && this.isValidExpDate) {
                    $('body').trigger('processStart');
                    this.tokenizeCard();
                }
            },

            initPaymentFields: function () {
                PaymentFields.on('tokenize', (data) => {
                    if (data.isSuccessful === true) {
                        $('body').trigger('processStop');
                        this.selectedCardType(data.creditCard.cardType)
                        this.creditCardFirstDigits(data.creditCard.cardIssuerIdentificationNumber)
                        this.creditCardLastDigits(data.creditCard.cardLastDigits)
                        this.paymentMethodToken(data.tokenString);
                        this.addAdditionalData();
                        this.placeOrder()
                    }
                });

                PaymentFields.on('error', (data) => {
                    $('body').trigger('processStop');
                });

                PaymentFields.on('inputEvent', (data) => {
                    if (data.eventType === 'blur') {
                        this.validationPaymentData(data.fieldCode);
                    }
                    $('body').trigger('processStop');
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
                });
                let authConfig = config.getConfig().sessionAccessToken;
                let apiBaseUrl = config.getConfig().apiBaseUrl;
                PaymentFields.init({
                    apiBaseUrl: apiBaseUrl,
                    oauthApiToken: authConfig.access_token,
                    spVaultEnvironmentId: authConfig.sp_vault_environment_id,
                    paymentMethodType: 'credit_card',
                    enableThreeDs: false,
                    enableCvv: true,
                    numberIframe: {
                        container: config.getCode() + '_cc_number',
                        inputStyle:
                            'width: 100%; padding: 5px 8px; line-height: 20px; font-size: 14px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";',
                    },
                    cvvIframe: {
                        container: config.getCode() + '_cc_cid',
                        inputStyle:
                            'width: 100%; padding: 5px 8px; line-height: 20px; font-size: 14px; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";',
                    },
                    threeDsChallengeIframe: {
                        container: 'challenge',
                    },
                });
            },

            onFieldEvent: function (name, event, activeElement, inputData) {
                var hostedField = hostedFieldValidator(name, event, inputData);
                if (hostedField.isValid !== undefined) {
                    this.isValidHostedFields = hostedField.isValid;
                }
                if (hostedField.cardType !== undefined) {
                    this.setSelectedCardType(hostedField.cardType);
                }
            },

            setSelectedCardType: function (cardType) {
                var $cardType = $('#' + this.container).find('.icon-type');
                $cardType.attr('class', 'icon-type');
                $cardType.addClass('icon-type-' + cardType);
            },

            getPaymentData: function () {
                return {
                    'first_name': $('#order-billing_address_firstname').val(),
                    'last_name': $('#order-billing_address_lastname').val(),
                    'phone_number': $('#order-billing_address_telephone').val(),
                    'address1': $('#order-billing_address_street0').val(),
                    'address2': $('#order-billing_address_street1').val(),
                    'address3': $('#order-billing_address_street2').val(),
                    'city': $('#order-billing_address_city').val(),
                    'state': $("#order-billing_address_region_id option:selected").text(),
                    'zip': $('#order-billing_address_postcode').val(),
                    'country': $('#order-billing_address_country_id').val(),
                    'year': $(this.getSelector('expiration_yr')).val(),
                    'month': $(this.getSelector('expiration')).val()
                };
            },

            tokenizeCard: function () {
                var self = this;
                $.ajax({
                    url: config.getAdminOrderAmountUrl(),
                    data: {
                        form_key: window.FORM_KEY,
                        quote_id: window.order.quoteId
                    },
                    type: 'POST',
                    dataType: 'json',

                }).done( function (response) {
                    PaymentFields.tokenize({
                        authenticateCardholder: false,
                        authenticationType: 'payment',
                        paymentDetails: {
                            amount: response.grand_total,
                        },
                        cardDetails: {
                            creditcardMonth: $(self.getSelector('expiration')).val(),
                            creditcardYear: $(self.getSelector('expiration_yr')).val()
                        },
                        customerEmail: $('#email').val(),
                        billingAddress: self.getPaymentData(),
                        shippingAddress: self.getPaymentData(),
                    });
                });

            },

            onErrors: function (errors) {
                for(var i = 0; i < errors.length; i++) {
                    if (errors[i]['attribute'] == 'number' || errors[i]['attribute'] == 'cvv') {
                        hostedFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                    if (errors[i]['attribute'] == 'month' || errors[i]['attribute'] == 'year') {
                        expirationFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                }
            },

            onPaymentMethod: function (token) {
                this.setPaymentDetails(token);
                this.placeOrder();
            },

            setPaymentDetails: function (token) {
                var $container = $('#' + this.container);
                $container.find('[name="payment[payment_method_token]"]').val(token);
            },

            placeOrder: function () {
                this.$orderForm.trigger('realOrder');
            },

            addAdditionalData: function () {
                var orderForm = this.$orderForm;
                var additionalData = {
                    cc_type: this.selectedCardType(),
                    browser_info: this.getThreeDSBrowserInfo(),
                    cc_exp_year: $(this.getSelector('expiration_yr')).val(),
                    cc_exp_month: $(this.getSelector('expiration')).val(),
                    payment_method_token: this.paymentMethodToken(),
                    creditcard_first_digits: this.creditCardFirstDigits(),
                    creditcard_last_digits: this.creditCardLastDigits(),
                };

                $.each(additionalData, function (key, value) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'payment[' + key + ']',
                        value: value
                    }).appendTo(orderForm);
                });
            },

            getSelector: function (field) {
                return '#' + config.getCode() + '_' + field;
            }
        });
    }
);
