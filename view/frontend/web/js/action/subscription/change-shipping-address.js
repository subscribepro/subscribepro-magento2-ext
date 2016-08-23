define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor',
        'Swarming_SubscribePro/js/action/address/save-in-address-book'
    ],
    function ($t, storage, globalMessageContainer, errorProcessor, saveInAddressBook) {
        'use strict';
        return function (subscriptionId, addressData, address, isLoading, messageContainer, saveToAddressBookCallback, successCallback) {
            isLoading(true);
            if (address.saveInAddressBook) {
                saveInAddressBook(
                    address,
                    addressData,
                    isLoading,
                    messageContainer,
                    function (addressData, address, successMessage) {
                        saveToAddressBookCallback(addressData, address);
                        return changeShippingAddress(
                            subscriptionId,
                            address,
                            isLoading,
                            messageContainer,
                            successCallback,
                            successMessage
                        )
                    }
                )
            } else {
                changeShippingAddress(subscriptionId, address, isLoading, messageContainer, successCallback);
            }
        };

        function changeShippingAddress(subscriptionId, address, isLoading, messageContainer, successCallback, saveAddressMessage) {
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
                    successCallback(response);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    if (saveAddressMessage) {
                        messageContainer.getSuccessMessages().push(saveAddressMessage);
                    }
                }
            ).always(
                function () {
                    isLoading(false);
                }
            );
        }
    }
);
