
define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (placeOrderFunction) {
        return wrapper.wrap(placeOrderFunction, function (originalPlaceOrderFunction, paymentData, messageContainer) {
            return originalPlaceOrderFunction(paymentData, messageContainer).done(
                function (response) {
                    if (paymentData.method === 'subscribe_pro') {
                        $(document).trigger('subscribepro:orderPlaceAfter', [response]);
                    }
                }
            );
        });
    };
});
