define(
    [
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (storage, globalMessageContainer, errorProcessor) {
        'use strict';
        return function (subscriptionId, address, messageContainer, deferred) {

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/update-shipping-address',
                JSON.stringify({subscriptionId: subscriptionId, address: address}),
                false
            ).done(
                function (response) {
                    deferred.resolve(response, globalMessageContainer);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    deferred.reject(response, messageContainer);
                }
            );
        }
    }
);
