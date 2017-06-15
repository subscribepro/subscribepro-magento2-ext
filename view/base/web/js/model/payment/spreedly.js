
define(
    [
        'Swarming_SubscribePro/js/model/payment/config',
        'spreedly'
    ],
    function(config) {
        'use strict';

        return {
            init: function (onFieldEvent, onPaymentMethod, validationPaymentData, onErrors) {
                window.SubscribeProSpreedlyIframe = new SpreedlyPaymentFrame();
                window.SubscribeProSpreedlyIframe.init(config.getEnvironmentKey(), {
                    'numberEl': config.getCode() + '_cc_number',
                    'cvvEl': config.getCode() + '_cc_cid'
                });
                window.SubscribeProSpreedlyIframe.on('ready', this.styleIFrameFields);
                window.SubscribeProSpreedlyIframe.on('fieldEvent', onFieldEvent);
                window.SubscribeProSpreedlyIframe.on('paymentMethod', onPaymentMethod);
                window.SubscribeProSpreedlyIframe.on('validation', validationPaymentData);
                window.SubscribeProSpreedlyIframe.on('errors', onErrors);
                return window.SubscribeProSpreedlyIframe;
            },

            validate: function () {
                window.SubscribeProSpreedlyIframe.validate();
            },

            reload: function() {
                window.SubscribeProSpreedlyIframe.reload();
            },

            tokenizeCreditCard: function (options) {
                window.SubscribeProSpreedlyIframe.tokenizeCreditCard(options);
            },

            styleIFrameFields: function () {
                window.SubscribeProSpreedlyIframe.setFieldType('text');
                window.SubscribeProSpreedlyIframe.setNumberFormat('prettyFormat');
                window.SubscribeProSpreedlyIframe.setStyle('number','padding: .45em .35em; font-size: 91%;');
                window.SubscribeProSpreedlyIframe.setStyle('cvv', 'padding: .45em .35em; font-size: 91%; width: 45px;');
            }
        };
    }
);
