
define(
    [
        'jquery',
        'underscore',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-field-validator',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields',
        'Swarming_SubscribePro/js/model/payment/spreedly',
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
        spreedly,
        $t
    ) {
        'use strict';

        return {
            defaults: {
                isValidHostedFields: false,
                isValidExpDate: false,
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardExpMonthFocus: null,
                creditCardExpYearFocus: null,
                paymentMethodToken: null,
                selectedCardType: null,
                show3DSiFrame: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus',
                        'paymentMethodToken',
                        'selectedCardType',
                        'show3DSiFrame'
                    ]);

                this.show3DSiFrame.subscribe(function (isActive) {
                    if (isActive) {
                        $('body').addClass('spro-3ds-active');
                    } else {
                        $('body').removeClass('spro-3ds-active');
                    }
                });
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

            initSpreedly: function () {
                spreedly.init(
                    $.proxy(this.onFieldEvent, this),
                    $.proxy(this.onPaymentMethod, this),
                    $.proxy(this.validationPaymentData, this),
                    $.proxy(this.onErrors, this)
                );

                if (config.isThreeDSActive()) {
                    Spreedly.on('3ds:status', $.proxy(this.on3DSstatusUpdates, this));
                }
            },

            on3DSstatusUpdates: function (event) {
                console.log('event.action', event.action, event)

                switch (event.action) {
                    case 'challenge':
                        this.show3DSiFrame(true);
                        break;
                    case 'succeeded':
                        this.onOrderSuccess();
                        break;
                    case 'finalization-timeout':
                        this.process3DSFailure($t('Time-Out. User did not authenticate within expected timeout.'));
                        break;
                    case 'error':
                        this.process3DSFailure(event.context);
                        break;
                    default:
                        console.log('Event not handled', event);
                }
            },

            initializeThreeDSLifecycle: function (token) {
                var lifecycle = new Spreedly.ThreeDS.Lifecycle({
                    environmentKey: config.getEnvironmentKey(),
                    hiddenIframeLocation: 'spro-3ds-iframe',
                    challengeIframeLocation: 'spro-3ds-challenge-container',
                    transactionToken: token,
                    challengeIframeClasses: '',
                })

                lifecycle.start()
            },

            getThreeDSBrowserInfo: function () {
                return Spreedly.ThreeDS.serialize(config.getBrowserSize(), config.getAcceptHeader());
            },

            process3DSFailure: function (errorMessage) {
                this.show3DSiFrame(false);

                alert({
                    title: $t('Error'),
                    content: errorMessage,
                    actions: {
                        always: function(){
                            document.location.reload();
                        }
                    }
                });
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

            validationCreditCardExpMonth: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'month',
                    this.creditCardExpMonth(),
                    this.creditCardExpYear()
                );
                this.updateSaveActionAllowed();
            },

            validationCreditCardExpYear: function (isFocused) {
                this.isValidExpDate = expirationFieldValidator(
                    isFocused,
                    'year',
                    this.creditCardExpMonth(),
                    this.creditCardExpYear()
                );
                this.updateSaveActionAllowed();
            },

            startPlaceOrder: function () {
                if (this.isValidHostedFields && this.isValidExpDate) {
                    spreedly.validate();
                }
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
