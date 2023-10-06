define(
    [
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($, storage, errorProcessor) {
        "use strict";
        return function (messageContainer, deferred) {

            deferred = deferred || $.Deferred();
            return storage.get(
                '/rest/V1/swarming_subscribepro/me/payment-tokens',
                false
            ).done(
                function (response) {
                    deferred.resolve(response);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    deferred.reject(response);
                }
            );
        };
    }
);
