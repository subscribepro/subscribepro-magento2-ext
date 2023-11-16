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
        'mage/translate',
        'Swarming_SubscribePro/js/lib/jquery.serializejson.min'
    ],
    function ($, Component, config, CcForm, saveCart, customerData, urlBuilder, $t) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                formSelector: "#vault-edit",
                formSubmitSelector: "#vault-edit .save",
                isLoading: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading'
                    ]);

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
                    'address3': $("#street3").val(),
                    'city': $("#city").val(),
                    'state': $("#region_id option:selected").text(),
                    'zip': $("#postcode").val(),
                    'country': $("#country").val(),
                    'year': this.creditCardExpYear(),
                    'month': this.creditCardExpMonth()
                };
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
