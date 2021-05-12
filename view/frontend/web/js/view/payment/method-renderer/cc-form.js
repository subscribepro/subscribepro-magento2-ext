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
                canPlaceOrder: false,
                show3DSiFrame: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'canPlaceOrder',
                        'show3DSiFrame'
                    ]);

                if (config.isThreeDSActive()) {
                    $(document).on('subscribepro:orderPlaceAfter', $.proxy(this.onOrderPlaceAfter, this));
                }
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
                    Spreedly.on('3ds:status', $.proxy(this.on3DSstatusUpdates, this));
                    this.redirectAfterPlaceOrder = false;
                }
            },

            on3DSstatusUpdates: function (event) {
                console.log('event.action', event.action)

                switch (event.action) {
                    case 'challenge':
                        this.show3DSiFrame(true);
                        break;
                    case 'succeeded':
                        redirectOnSuccessAction.execute();
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

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'is_active_payment_token_enabler': customer.isLoggedIn(),
                        'payment_method_token': this.paymentMethodToken()
                    }
                };

                if (config.isThreeDSActive()) {
                    data.additional_data.browser_info = Spreedly.ThreeDS.serialize(config.getBrowserSize(), config.getAcceptHeader());
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
                            redirectOnSuccessAction.execute();
                        }
                    }.bind(this));
            }
        });
    }
);
