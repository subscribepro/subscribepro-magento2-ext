/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/cc-form',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/action/checkout/get-order-status',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate'
    ],
    function ($, Component, CcForm, config, getOrderStatus, quote, customer, alert, redirectOnSuccessAction, $t) {
        'use strict';

        return Component.extend(CcForm).extend({
            defaults: {
                template: 'Swarming_SubscribePro/payment/cc-form',
                canPlaceOrder: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'canPlaceOrder'
                    ]);

                if (config.isThreeDSActive()) {
                    $(document).on('subscribepro:orderPlaceAfter', $.proxy(this.onOrderPlaceAfter, this));
                }
                return this;
            },

            initialize: function () {
                this._super();

                quote.billingAddress.subscribe(function (address) {
                    this.canPlaceOrder(address !== null && this.isValidHostedFields && this.isValidExpDate);
                }, this);
            },

            updateSaveActionAllowed: function () {
                this.canPlaceOrder(quote.billingAddress() != null && this.isValidHostedFields && this.isValidExpDate)
            },

            isActive: function () {
                return this.getCode() == this.isChecked();
            },

            initSpreedly: function () {
                this._super();

                if (config.isThreeDSActive()) {
                    this.redirectAfterPlaceOrder = false;
                }
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'is_active_payment_token_enabler': customer.isLoggedIn(),
                        'payment_method_token': this.paymentMethodToken()
                    }
                };

                if (config.isThreeDSActive()) {
                    data.additional_data.browser_info = this.getThreeDSBrowserInfo();
                }
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
            },

            onOrderPlaceAfter: function (event, orderId) {
                getOrderStatus(orderId)
                    .done(function (response) {
                        if (response.state === 'pending') {
                            this.initializeThreeDSLifecycle(response.token);
                        } else {
                            this.onOrderSuccess();
                        }
                    }.bind(this));
            },

            onOrderSuccess: function () {
                redirectOnSuccessAction.execute();
            }
        });
    }
);
