
define(
    ['underscore'],
    function(_) {
        'use strict';

        var code = 'subscribe_pro';

        var config = {};
        if (window.checkoutConfig) {
            config = window.checkoutConfig.payment[code];
        } else if (window.subscribeProPaymentConfig) {
            config = window.subscribeProPaymentConfig;
        }

        return {
            getCode: function () {
                return code;
            },

            getVaultCode: function () {
                return config.vaultCode
            },

            isActive: function () {
                return config.isActive
            },

            getEnvironmentKey: function () {
                return config.environmentKey;
            },

            hasVerification: function () {
                return config.hasVerification;
            },

            getIcons: function () {
                return config.icons;
            },

            getAvailableCardTypes: function () {
                return config.availableCardTypes;
            },

            getCcTypesMapper: function () {
                return config.ccTypesMapper;
            },

            getCvvImageUrl: function () {
                return config.cvvImageUrl;
            }
        };
    }
);
