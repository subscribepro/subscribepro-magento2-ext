
define(
    ['underscore'],
    function(_) {
        'use strict';

        var code = 'subscribe_pro';

        return {
            getCode: function () {
                return code;
            },

            getVaultCode: function () {
                return this.getConfig().vaultCode;
            },

            isActive: function () {
                return this.getConfig().isActive;
            },

            getBrowserSize: function () {
                return this.getConfig().browserSize;
            },

            getAcceptHeader: function () {
                return this.getConfig().acceptHeader;
            },

            getEnvironmentKey: function () {
                return this.getConfig().environmentKey;
            },

            hasVerification: function () {
                return this.getConfig().hasVerification;
            },

            getIcons: function () {
                return this.getConfig().icons;
            },

            getAvailableCardTypes: function () {
                return this.getConfig().availableCardTypes;
            },

            getCcTypesMapper: function () {
                return this.getConfig().ccTypesMapper;
            },

            getCvvImageUrl: function () {
                return this.getConfig().cvvImageUrl;
            },

            getConfig: function () {
                var config = {};
                if (window['checkoutConfig'] != undefined) { // frontend checkout
                    config = window.checkoutConfig.payment[code];
                } else if (window['subscribeProPaymentConfigs'] != undefined && window.order) { // backend checkout
                    config = window.subscribeProPaymentConfigs[window.order.storeId];
                } else if (window['subscribeProPaymentConfig'] != undefined ) { // adding a card in My Stored Methods
                    config = window.subscribeProPaymentConfig;
                }
                return config;
            }
        };
    }
);
