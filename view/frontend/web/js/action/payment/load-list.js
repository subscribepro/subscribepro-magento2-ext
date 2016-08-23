define(
    [
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($, storage, errorProcessor) {
        "use strict";
        return function (successCallback, itemsLoaded, isLoading, messageContainer) {
            isLoading(true);
            return storage.get(
                '/rest/V1/swarming_subscribepro/me/payment-tokens',
                false
            ).done(
                function (response) {
                    successCallback(response);
                }
            ).error(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                }
            ).always(function () {
                isLoading(false);
                itemsLoaded(true);
            });
        };
    }
);
