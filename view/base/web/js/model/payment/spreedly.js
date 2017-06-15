
define(
    [
        'Swarming_SubscribePro/js/model/payment/config',
        'spreedly'
    ],
    function(config) {
        'use strict';

        return {
            init: function (onFieldEvent, onPaymentMethod, validationPaymentData, onErrors) {

                this.SpreedlyInstance = new SpreedlyPaymentFrame();
                this.SpreedlyInstance.init(config.getEnvironmentKey(), {
                    'numberEl': config.getCode() + '_cc_number',
                    'cvvEl': config.getCode() + '_cc_cid'
                });
                this.SpreedlyInstance.on('ready', this.styleIFrameFields);
                this.SpreedlyInstance.on('fieldEvent', onFieldEvent);
                this.SpreedlyInstance.on('paymentMethod', onPaymentMethod);
                this.SpreedlyInstance.on('validation', validationPaymentData);
                this.SpreedlyInstance.on('errors', onErrors);
            },

            validate: function () {
                this.SpreedlyInstance.validate();
            },

            reload: function() {
                this.SpreedlyInstance.reload();
            },

            tokenizeCreditCard: function (options) {
                this.SpreedlyInstance.tokenizeCreditCard(options);
            },

            styleIFrameFields: function () {
                this.SpreedlyInstance.setFieldType('text');
                this.SpreedlyInstance.setNumberFormat('prettyFormat');
                this.SpreedlyInstance.setStyle('number','padding: .45em .35em; font-size: 91%;');
                this.SpreedlyInstance.setStyle('cvv', 'padding: .45em .35em; font-size: 91%; width: 45px;');
            }
        };
    }
);
