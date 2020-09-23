define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (setPaymentInformationAction) {
        return wrapper.wrap(setPaymentInformationAction, function (originalAction, messageContainer, paymentData, skipBilling) {
            // Remove the id off the end of the subscribe pro vault payment method before posting to the server. Example: "subscribe_pro_vault_1234"
            if (paymentData.method
                && paymentData.method.indexOf('subscribe_pro_vault') === 0) {
                paymentData.method = 'subscribe_pro_vault';
            }

            return originalAction(messageContainer, paymentData, skipBilling);
        });
    };
});
