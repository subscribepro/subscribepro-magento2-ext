define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/model/subscription/loader'
    ],
    function ($t, storage, messageContainer, errorProcessor, subscriptionLoader) {
        'use strict';
        return function (subscriptionId, nextOrderDate, callback) {
            subscriptionLoader.isLoading(true);

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/update-next-order-date',
                JSON.stringify({subscriptionId: subscriptionId, nextOrderDate: nextOrderDate}),
                false
            ).done(
                function () {
                    messageContainer.addSuccessMessage({'message': $t('Subscription updated.')});
                    callback();
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                }
            ).always(
                function () {
                    subscriptionLoader.isLoading(false);
                }
            );
        };
    }
);
