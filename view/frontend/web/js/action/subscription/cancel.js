define(
    [
        'jquery',
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/modal/confirm',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/model/subscription/loader'
    ],
    function ($, $t, storage, confirmation, messageContainer, errorProcessor, subscriptionLoader) {
        'use strict';
        return function (subscriptionId, deferred) {
            confirmation({
                title: $t('Cancel subscription'),
                content: $t('Are you sure you want to cancel subscription?'),
                actions: {
                    confirm: function() {cancel(subscriptionId, deferred)}
                }
            });
        };
        
        function cancel(subscriptionId, deferred) {
            subscriptionLoader.isLoading(true);

            deferred = deferred || $.Deferred();
            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/cancel',
                JSON.stringify({subscriptionId: subscriptionId}),
                false
            ).done(
                function () {
                    messageContainer.addSuccessMessage({'message': $t('The subscription has been cancelled.')});
                    deferred.resolve();
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                    deferred.reject(response);
                }
            ).always(
                function () {
                    subscriptionLoader.isLoading(false);
                }
            );
        }
    }
);
