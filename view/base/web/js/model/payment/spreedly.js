
define(
    [
        'Swarming_SubscribePro/js/model/payment/config',
        'spreedly'
    ],
    function(config) {
        'use strict';

        return {
            init: function (onFieldEvent, onPaymentMethod, validationPaymentData, onErrors) {
		console.log(config.getEnvironmentKey());
                Spreedly.init(config.getEnvironmentKey(), {
                    'numberEl': config.getCode() + '_cc_number',
                    'cvvEl': config.getCode() + '_cc_cid'
                });
                Spreedly.on('ready', this.styleIFrameFields);
                Spreedly.on('fieldEvent', onFieldEvent);
                Spreedly.on('paymentMethod', onPaymentMethod);
                Spreedly.on('validation', validationPaymentData);
                Spreedly.on('errors', onErrors);
            },

            validate: function () {
                Spreedly.validate();
            },

	    reload: function() {
               Spreedly.reload();
            },

            tokenizeCreditCard: function (options) {
                Spreedly.tokenizeCreditCard(options);
            },

            styleIFrameFields: function () {
                Spreedly.setFieldType('text');
                Spreedly.setNumberFormat('prettyFormat');
                Spreedly.setStyle('number','padding: .45em .35em; font-size: 91%;');
                Spreedly.setStyle('cvv', 'padding: .45em .35em; font-size: 91%; width: 45px;');
            }
        };
    }
);
