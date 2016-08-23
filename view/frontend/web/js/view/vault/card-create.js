/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields',
        'mage/translate',
        'spreedly'
    ],
    function (
        $,
        _,
        Component,
        config,
        expirationFieldValidator,
        hostedFieldValidator,
        expirationFields,
        hostedFields,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isValidHostedFields: false,
                isValidExpDate: false,
                creditCardType: null,
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardExpMonthFocus: null,
                creditCardExpYearFocus: null,
                paymentMethodToken: null,
                selectedCardType: null,
                formSelector: "#vault-edit",
                formSubmitSelector: "#vault-edit .save"
            },

            initObservable: function () {
                var self = this;

                this._super()
                    .observe([
                        'creditCardType',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus',
                        'paymentMethodToken',
                        'selectedCardType'
                    ]);

                $(self.formSubmitSelector).click(function() {
                    if ($(self.formSelector).valid()) {
                        self.startPlaceOrder();
                    }
                    return false;
                });
                return this;
            },

            initialize: function () {
                this._super();

                this.creditCardExpMonthFocus.subscribe($.proxy(this.validationCreditCardExpMonth, this));
                this.creditCardExpYearFocus.subscribe($.proxy(this.validationCreditCardExpYear, this));
            },

            updatePlaceOrderActionAllowed: function () {
                $(this.formSubmitSelector).prop('disabled', !(this.isValidExpDate && this.isValidHostedFields));
            },

            getCode: function () {
                return config.getCode();
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_token': this.paymentMethodToken
                    }
                };
            },

            initSpreedly: function () {
                Spreedly.init(config.getEnvironmentKey(), {
                    'numberEl': this.getCode() + '_cc_number',
                    'cvvEl': this.getCode() + '_cc_cid'
                });
                Spreedly.on('ready', this.styleIFrameFields);
                Spreedly.on('fieldEvent', $.proxy(this.onFieldEvent, this));
                Spreedly.on('paymentMethod', $.proxy(this.onPaymentMethod, this));
                Spreedly.on('validation', $.proxy(this.validationPaymentData, this));
                Spreedly.on('errors', $.proxy(this.onErrors, this));
            },

            styleIFrameFields: function () {
                Spreedly.setFieldType('text');
                Spreedly.setNumberFormat('prettyFormat');
                Spreedly.setStyle('number','padding: .45em .35em; font-size: 91%;');
                Spreedly.setStyle('cvv', 'padding: .45em .35em; font-size: 91%;');
            },

            onFieldEvent: function (name, event, activeElement, inputData) {
                var hostedField = hostedFieldValidator(name, event, inputData);
                if (hostedField.isValid !== undefined) {
                    this.isValidHostedFields = hostedField.isValid;
                }
                if (hostedField.cardType !== undefined) {
                    this.selectedCardType(hostedField.cardType);
                }
                this.updatePlaceOrderActionAllowed();
            },

            validationCreditCardExpMonth: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'month',
                    this.creditCardExpMonth(),
                    this.creditCardExpYear()
                );
                this.updatePlaceOrderActionAllowed();
            },

            validationCreditCardExpYear: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'year',
                    this.creditCardExpMonth(),
                    this.creditCardExpYear()
                );
                this.updatePlaceOrderActionAllowed();
            },

            startPlaceOrder: function () {
                if (this.isValidHostedFields && this.isValidExpDate) {
                    Spreedly.validate();
                }
            },

            validationPaymentData: function (inputProperties) {
                if (inputProperties['validCvv'] && inputProperties['validNumber']) {
                    this.tokenizeCreditCard();
                }

                if (!inputProperties['validNumber']) {
                    hostedFields.addClass('number', 'invalid');
                }

                if (!inputProperties['validCvv']) {
                    hostedFields.addClass('cvv', 'invalid');
                }
            },

            tokenizeCreditCard: function () {
                var options = {
                    'first_name': $("#first_name").val(),
                    'last_name': $("#last_name").val(),
                    'company': $("#company").val(),
                    'phone_number': $("#phone").val(),
                    'address1': $("#street1").val(),
                    'address2': $("#street2").val(),
                    'city': $("#city").val(),
                    'state': $("#region_id option:selected").text(),
                    'zip': $("#postcode").val(),
                    'country': $("#country").val(),
                    'year': this.creditCardExpYear(),
                    'month': this.creditCardExpMonth()
                };

                Spreedly.tokenizeCreditCard(options);
            },

            onPaymentMethod: function (token) {
                this.paymentMethodToken(token);
                $(this.formSelector).submit();
            },

            onErrors: function (errors) {
                this.paymentToken = null;

                for(var i = 0; i < errors.length; i++) {
                    if (errors[i]['attribute'] == 'number' || errors[i]['attribute'] == 'cvv') {
                        hostedFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                    if (errors[i]['attribute'] == 'month' || errors[i]['attribute'] == 'year') {
                        expirationFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                }
            },

            /**
             * @param {String} type
             * @returns {Boolean}
             */
            getIcons: function (type) {
                return config.getIcons().hasOwnProperty(type) ? config.getIcons()[type] : false;
            },

            /**
             * @returns {Object}
             */
            getCcAvailableTypesValues: function () {
                return _.map(config.getAvailableCardTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },

            /**
             * @returns {Boolean}
             */
            hasVerification: function () {
                return config.hasVerification();
            },

            /**
             * Get image for CVV
             * @returns {String}
             */
            getCvvImageHtml: function () {
                return '<img src="' + config.getCvvImageUrl() +
                    '" alt="' + $t('Card Verification Number Visual Reference') +
                    '" title="' + $t('Card Verification Number Visual Reference') +
                    '" />';
            }
        });
    }
);
