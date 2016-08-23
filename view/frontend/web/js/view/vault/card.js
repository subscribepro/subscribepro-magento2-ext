/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'uiComponent',
        'Swarming_SubscribePro/js/model/payment/cc-form'
    ],
    function ($, Component, CcForm) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                formSelector: "#vault-edit",
                formSubmitSelector: "#vault-edit .save"
            },

            initObservable: function () {
                this._super();

                var self = this;
                $(self.formSubmitSelector).click(function() {
                    if ($(self.formSelector).valid()) {
                        self.startPlaceOrder();
                    }
                    return false;
                });
                return this;
            },

            updateSaveActionAllowed: function () {
                $(this.formSubmitSelector).prop('disabled', !(this.isValidExpDate && this.isValidHostedFields));
            },

            getPaymentData: function () {
                return {
                    'first_name': $("#first_name").val(),
                    'last_name': $("#last_name").val(),
                    'company': $("#company").val(),
                    'phone_number': $("#telephone").val(),
                    'address1': $("#street1").val(),
                    'address2': $("#street2").val(),
                    'city': $("#city").val(),
                    'state': $("#region_id option:selected").text(),
                    'zip': $("#postcode").val(),
                    'country': $("#country").val(),
                    'year': this.creditCardExpYear(),
                    'month': this.creditCardExpMonth()
                };
            },

            submitPayment: function () {
                $(this.formSelector).submit();
            }
        });
    }
);
