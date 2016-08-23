define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($t, storage, globalMessageContainer, errorProcessor) {
        'use strict';
        return function (subscriptionId, paymentProfileId, isLoading, messageContainer, successCallback) {
            isLoading(true);

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/update-payment-profile',
                JSON.stringify({subscriptionId: subscriptionId, paymentProfileId: paymentProfileId}),
                false
            ).done(
                function (response) {
                    globalMessageContainer.addSuccessMessage({'message': $t('Subscription payment profile has been updated.')});
                    successCallback(response);
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
