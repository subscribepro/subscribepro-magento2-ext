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
                title: $t('Skip subscription'),
                content: $t('Are you sure you want to skip subscription?'),
                actions: {
                    confirm: function() {skip(subscriptionId, deferred)}
                }
            });
        };

        function skip(subscriptionId, deferred) {
            subscriptionLoader.isLoading(true);

            deferred = deferred || $.Deferred();
            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/skip',
                JSON.stringify({subscriptionId: subscriptionId}),
                false
            ).done(
                function (response) {
                    messageContainer.addSuccessMessage({'message': $t('The next delivery has been skipped.')});
                    deferred.resolve(response);
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
