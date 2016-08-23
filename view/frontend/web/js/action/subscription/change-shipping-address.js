define(
    [
        'jquery',
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/action/address/save-in-address-book'
    ],
    function ($, $t, storage, globalMessageContainer, errorProcessor, saveInAddressBook) {
        'use strict';
        return function (subscriptionId, addressData, address, isLoading, messageContainer, saveToAddressBookCallback, updateAddressDeferred) {
            isLoading(true);

            var deferred = $.Deferred();
            $.when(deferred).done(function (addressData, address, successMessage) {
                address = saveToAddressBookCallback(addressData, address);
                changeShippingAddress(
                    subscriptionId,
                    address,
                    isLoading,
                    messageContainer,
                    updateAddressDeferred,
                    successMessage
                )
            });

            saveInAddressBook(address, addressData, isLoading, messageContainer, deferred);
        };

        function changeShippingAddress(subscriptionId, address, isLoading, messageContainer, updateAddressDeferred, saveAddressMessage) {
            return storage.post(
                '/rest/V1/swarming_subscribepro/me/subscriptions/update-shipping-address',
                JSON.stringify({subscriptionId: subscriptionId, address: address}),
                false
            ).done(
                function (response) {
                    if (saveAddressMessage) {
                        globalMessageContainer.addSuccessMessage({'message': saveAddressMessage});
                    }
                    globalMessageContainer.addSuccessMessage({'message': $t('Subscription shipping address has been updated.')});
                    updateAddressDeferred.resolve(response);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    if (saveAddressMessage) {
                        messageContainer.getSuccessMessages().push(saveAddressMessage);
                    }
                    updateAddressDeferred.reject();
                }
            ).always(
                function () {
                    isLoading(false);
                }
            );
        }
    }
);
