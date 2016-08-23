define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/modal/confirm',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/model/subscription/loader'
    ],
    function ($t, storage, confirmation, messageContainer, errorProcessor, subscriptionLoader) {
        'use strict';
        return function (subscriptionId, status) {
            confirmation({
                title: $t('Restart subscription'),
                content: $t('Are you sure you want to restart subscription?'),
                actions: {
                    confirm: function() {restart(subscriptionId, status)}
                }
            });
        };

        function restart(subscriptionId, status) {
            subscriptionLoader.isLoading(true);

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/restart',
                JSON.stringify({subscriptionId: subscriptionId}),
                false
            ).done(
                function () {
                    messageContainer.addSuccessMessage({'message': $t('The subscription has been restarted.')});
                    status('Active');
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
        }
    }
);
