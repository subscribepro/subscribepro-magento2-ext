/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/config',
        'Magento_Checkout/js/model/quote',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function ($, Component, CcForm, config, quote, VaultEnabler) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                template: 'Swarming_SubscribePro/payment/cc-form',
            },

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(config.getVaultCode());

                quote.billingAddress.subscribe(function (address) {
                    this.isPlaceOrderActionAllowed(address !== null && this.isValidHostedFields && this.isValidExpDate);
                }, this);
            },

            updateSaveActionAllowed: function () {
                this.isPlaceOrderActionAllowed(quote.billingAddress() != null && this.isValidHostedFields && this.isValidExpDate)
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
                        'payment_method_token': this.paymentMethodToken()
                    }
                };

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            getPaymentData: function () {
                return {
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
            },

            submitPayment: function () {
                this.placeOrder();
            }
        });
    }
);
