define(
    [
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/model/subscription/loader'
    ],
    function ($, storage, errorProcessor, subscriptionLoader) {
        "use strict";
        return function (callback) {
            subscriptionLoader.isLoading(true);
            return storage.get(
                '/rest/V1/swarming_subscribepro/me/subscriptions',
                false
            ).done(
                function (response) {
                    callback(response);
                }
            ).error(
                function (response) {
                    errorProcessor.process(response);
                    subscriptionLoader.isLoading(false);
                }
            );
        };
    }
);
