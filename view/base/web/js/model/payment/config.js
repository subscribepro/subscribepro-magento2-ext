
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
                return this.getConfig().vaultCode
            },

            isActive: function () {
                return this.getConfig().isActive
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
                if (window.checkoutConfig) {
                    config = window.checkoutConfig.payment[code];
                } else if (window.subscribeProPaymentConfig) {
                    config = window.subscribeProPaymentConfig;
                }
                return config;
            }
        };
    }
);
