
define(
    [
        'jquery',
        'underscore',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ],
    function(
        $,
        _,
        config,
        expirationFieldValidator,
        hostedFieldValidator,
        expirationFields,
        hostedFields,
        alert,
        $t
    ) {
        'use strict';

        return {
            defaults: {
                isValidNumber: false,
                isValidCvv: false,
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
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isValidNumber',
                        'isValidCvv',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus',
                        'creditCardFirstDigits',
                        'creditCardLastDigits',
                        'paymentMethodToken',
                        'selectedCardType',
                        'ccValidationData'
                    ]);

                return this;
            },

            initialize: function () {
                this._super();
                this.creditCardExpMonthFocus.subscribe($.proxy(this.validationCreditCardExpMonth, this));
                this.creditCardExpYearFocus.subscribe($.proxy(this.validationCreditCardExpYear, this));
            },

            updateSaveActionAllowed: function () {},

            getCode: function () {
                return config.getCode();
            },

            onFieldEvent: function (name, event, activeElement, inputData) {
                var hostedField = hostedFieldValidator(name, event, inputData);
                if (hostedField.isValid !== undefined) {
                    this.isValidHostedFields = hostedField.isValid;
                }
                if (hostedField.cardType !== undefined) {
                    this.selectedCardType(hostedField.cardType);
                }
                this.updateSaveActionAllowed();
            },

            validatePayment: function () {
                this.validationPaymentData('number');
                this.validationPaymentData('cvv');
                this.validationCreditCardExpMonth(false);
                this.validationCreditCardExpYear(false);
            },

            validationCreditCardExpMonth: function (isFocused) {
                if (!this.creditCardExpMonth()) {
                    expirationFields.addClass('month', 'invalid');
                } else {
                    this.isValidExpDate = expirationFieldValidator(
                        isFocused,
                        'month',
                        this.creditCardExpMonth(),
                        this.creditCardExpYear()
                    );
                }

                this.updateSaveActionAllowed();
            },

            validationCreditCardExpYear: function (isFocused) {
                if (!this.creditCardExpYear()) {
                    expirationFields.addClass('year', 'invalid');
                } else {
                    this.isValidExpDate = expirationFieldValidator(
                        isFocused,
                        'year',
                        this.creditCardExpMonth(),
                        this.creditCardExpYear()
                    );
                }
                this.updateSaveActionAllowed();
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

            getPaymentData: function () {
                return {};
            },

            onPaymentMethod: function (token) {
                this.paymentMethodToken(token);
                this.submitPayment();
            },

            submitPayment: function () {},

            onOrderSuccess: function () {},

            onErrors: function (errors) {
                this.paymentMethodToken(null);

                for(var i = 0; i < errors.length; i++) {
                    if (errors[i]['attribute'] == 'number' || errors[i]['attribute'] == 'cvv') {
                        hostedFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                    if (errors[i]['attribute'] == 'month' || errors[i]['attribute'] == 'year') {
                        expirationFields.addClass(errors[i]['attribute'], 'invalid');
                    }
                }
            },

            getIcons: function (type) {
                return config.getIcons().hasOwnProperty(type) ? config.getIcons()[type] : false;
            },

            getCcAvailableTypesValues: function () {
                return _.map(config.getAvailableCardTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },

            hasVerification: function () {
                return config.hasVerification();
            },

            getCvvImageHtml: function () {
                return '<img src="' + config.getCvvImageUrl() +
                    '" alt="' + $t('Card Verification Number Visual Reference') +
                    '" title="' + $t('Card Verification Number Visual Reference') +
                    '" />';
            }
        };
    }
);
