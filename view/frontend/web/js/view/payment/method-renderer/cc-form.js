/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/config',
        'Magento_Checkout/js/model/quote',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields',
        'Magento_Vault/js/view/payment/vault-enabler',
        'spreedly'
    ],
    function (
        $,
        Component,
        config,
        quote,
        expirationFieldValidator,
        hostedFieldValidator,
        expirationFields,
        hostedFields,
        VaultEnabler
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/payment/cc-form',
                isValidHostedFields: false,
                isValidExpDate: false,
                creditCardExpMonthFocus: null,
                creditCardExpYearFocus: null,
                paymentMethodToken: null
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus'
                    ]);
                return this;
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(config.getVaultCode());

                this.creditCardExpMonthFocus.subscribe($.proxy(this.validationCreditCardExpMonth, this));
                this.creditCardExpYearFocus.subscribe($.proxy(this.validationCreditCardExpYear, this));

                quote.billingAddress.subscribe(function (address) {
                    this.isPlaceOrderActionAllowed(address !== null && this.isValidHostedFields && this.isValidExpDate);
                }, this);
            },

            updatePlaceOrderActionAllowed: function () {
                this.isPlaceOrderActionAllowed(quote.billingAddress() != null && this.isValidHostedFields && this.isValidExpDate)
            },

            getCode: function () {
                return config.getCode();
            },

            isActive: function () {
                return this.getCode() == this.isChecked();
            },

            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_token': this.paymentMethodToken
                    }
                };

                this.vaultEnabler.visitAdditionalData(data);

                return data;
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
                    'first_name': quote.billingAddress().firstname,
                    'last_name': quote.billingAddress().lastname,
                    'phone_number': quote.billingAddress().telephone,
                    'address1': quote.billingAddress().street[0],
                    'address2': quote.billingAddress().street[1] || '',
                    'city': quote.billingAddress().city,
                    'state': quote.billingAddress().regionCode,
                    'zip': quote.billingAddress().postcode,
                    'country': quote.billingAddress().countryId,
                    'year': this.creditCardExpYear(),
                    'month': this.creditCardExpMonth()
                };

                Spreedly.tokenizeCreditCard(options);
            },

            onPaymentMethod: function (token) {
                this.paymentMethodToken = token;
                this.placeOrder();
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
            }
        });
    }
);
