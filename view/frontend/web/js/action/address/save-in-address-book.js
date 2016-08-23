define(
    [
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (storage, globalMessageContainer, errorProcessor) {
        'use strict';
        return function (address, messageContainer, deferred) {

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/address/save-in-address-book',
                JSON.stringify({address: address}),
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
