
define(
    ['underscore'],
    function(_) {
        'use strict';

        var code = 'subscribe_pro';

        var config = window.checkoutConfig.payment[code];

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

            getAvailableCardTypes: function () {
                return config.availableCardTypes;
            },

            getCcTypesMapper: function () {
                return config.ccTypesMapper;
            }
        };
    }
);
