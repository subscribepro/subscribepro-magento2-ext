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
        'Swarming_SubscribePro/js/model/payment/spreedly',
        'Magento_Ui/js/lib/view/utils/dom-observer'
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
        spreedly,
        domObserver
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                $orderForm: null,
                orderFormSelector: 'edit_form',
                container: 'payment_form_subscribe_pro',
                active: false,
                spreedlyInitialized: false,
                isValidHostedFields: false,
                isValidExpDate: false,
                imports: {
                    onActiveChange: 'active'
                }
            },

            initObservable: function () {
                var self = this;

                this.$orderForm = $('#' + this.orderFormSelector);

                this._super()
                    .observe([
                        'active'
                    ]);

                this.$orderForm.off('changePaymentMethod.' + config.getCode())
                    .on('changePaymentMethod.' + config.getCode(), this.changePaymentMethod.bind(this));

                domObserver.get('#' + this.container, function () {
                    self.initSpreedly();
                });

                domObserver.remove('#' + this.container, function () {
                    self.spreedlyInitialized = false;
                });

                this.$orderForm
                    .on('focusin', this.getSelector('expiration'), $.proxy(this.validationCreditCardExpMonth, this, true))
                    .on('focusout', this.getSelector('expiration'), $.proxy(this.validationCreditCardExpMonth, this, false))
                    .on('focusin', this.getSelector('expiration_yr'), $.proxy(this.validationCreditCardExpYear, this, true))
                    .on('focusout', this.getSelector('expiration_yr'), $.proxy(this.validationCreditCardExpYear, this, false));

                return this;
            },

            changePaymentMethod: function (event, method) {
                this.active(method === config.getCode());
            },

            onActiveChange: function (isActive) {
                if (!isActive) {
                    this.$orderForm.off('submitOrder.subscribe_pro');
                    return;
                }

                this.disableEventListeners();

                window.order.addExcludedPaymentMethod(config.getCode());

                this.enableEventListeners();

                this.initSpreedly();
            },

            disableEventListeners: function () {
                this.$orderForm.off('submitOrder');
                this.$orderForm.off('submit');
            },

            enableEventListeners: function () {
                this.$orderForm.on('submitOrder.subscribe_pro', this.submitOrder.bind(this));
            },

            submitOrder: function () {
                this.$orderForm.validate().form();
                this.$orderForm.trigger('afterValidate.beforeSubmit');
                $('body').trigger('processStop');

                if (this.$orderForm.validate().errorList.length) {
                    return false;
                }

                if (!this.isValidHostedFields || !this.isValidExpDate) {
                    alert({content: $.mage.__('Enter valid payment information.')});
                    return false;
                }

                spreedly.validate();
            },

            initSpreedly: function () {
                if (!this.spreedlyInitialized) {
                    spreedly.init(
                        $.proxy(this.onFieldEvent, this),
                        $.proxy(this.onPaymentMethod, this),
                        $.proxy(this.validationPaymentData, this),
                        $.proxy(this.onErrors, this)
                    );
                    this.spreedlyInitialized = true;
                }
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

            validationPaymentData: function (inputProperties) {
                if (inputProperties['validNumber'] && (inputProperties['validCvv'] || !config.hasVerification())) {
                    this.tokenizeCreditCard();
                }

                if (!inputProperties['validNumber']) {
                    hostedFields.addClass('number', 'invalid');
                }

                if (!inputProperties['validCvv'] && config.hasVerification()) {
                    hostedFields.addClass('cvv', 'invalid');
                }
            },

            tokenizeCreditCard: function () {
                spreedly.tokenizeCreditCard(this.getPaymentData());
            },

            getPaymentData: function () {
                return {
                    'first_name': $('#order-billing_address_firstname').val(),
                    'last_name': $('#order-billing_address_lastname').val(),
                    'phone_number': $('#order-billing_address_telephone').val(),
                    'address1': $('#order-billing_address_street0').val(),
                    'address2': $('#order-billing_address_street1').val(),
                    'city': $('#order-billing_address_city').val(),
                    'state': $("#order-billing_address_region_id option:selected").text(),
                    'zip': $('#order-billing_address_postcode').val(),
                    'country': $('#order-billing_address_country_id').val(),
                    'year': $(this.getSelector('expiration_yr')).val(),
                    'month': $(this.getSelector('expiration')).val()
                };
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

            validationCreditCardExpMonth: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'month',
                    $(this.getSelector('expiration')).val(),
                    $(this.getSelector('expiration_yr')).val()
                );
            },

            validationCreditCardExpYear: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'year',
                    $(this.getSelector('expiration')).val(),
                    $(this.getSelector('expiration_yr')).val()
                );
            },

            getSelector: function (field) {
                return '#' + config.getCode() + '_' + field;
            }
        });
    }
);
