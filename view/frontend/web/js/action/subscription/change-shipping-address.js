define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($t, storage, globalMessageContainer, errorProcessor) {
        'use strict';
        return function (subscriptionId, addressData, address, isLoading, messageContainer, successCallback) {
            isLoading(true);

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/update-shipping-address',
                JSON.stringify({subscriptionId: subscriptionId, address: address}),
                false
            ).done(
                function (response) {
                    globalMessageContainer.addSuccessMessage({'message': $t('Subscription shipping address has been updated.')});
                    addressData.inline = response.address_inline;
                    successCallback(response, addressData, address);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                }
            ).always(
                function () {
                    isLoading(false);
                }
            );
        };
    }
);
