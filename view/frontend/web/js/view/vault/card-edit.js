
define(
    [
        'jquery',
        'uiComponent',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-field-validator'
    ],
    function (
        $,
        Component,
        config,
        expirationFieldValidator
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isValidExpDate: false,
                creditCardExpMonth: null,
                creditCardExpYear: null,
                creditCardExpMonthFocus: null,
                creditCardExpYearFocus: null,
                formSubmitSelector: "#vault-edit .save"
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardExpMonth',
                        'creditCardExpYear',
                        'creditCardExpMonthFocus',
                        'creditCardExpYearFocus'
                    ]);
                return this;
            },

            initialize: function () {
                this._super();

                this.creditCardExpMonthFocus.subscribe($.proxy(this.validationCreditCardExpMonth, this));
                this.creditCardExpYearFocus.subscribe($.proxy(this.validationCreditCardExpYear, this));
            },

            getCode: function () {
                return config.getCode();
            },

            updateSaveActionAllowed: function () {
                $(this.formSubmitSelector).prop('disabled', !this.isValidExpDate);
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
            }
        });
    }
);
