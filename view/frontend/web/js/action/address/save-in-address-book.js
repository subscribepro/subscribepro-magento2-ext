define(
    [
        'mage/translate',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($t, storage, globalMessageContainer, errorProcessor) {
        'use strict';
        return function (address, addressData, isLoading, messageContainer, successCallback) {
            isLoading(true);

            return storage.post(
                '/rest/V1/swarming_subscribepro/me/address/save-in-address-book',
                JSON.stringify({address: address}),
                false
            ).done(
                function (response) {
                    var successMessage = $t('The address has been successfully saved in the address book.');
                    addressData.inline = response;
                    successCallback(addressData, address, successMessage);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    isLoading(false);
                }
            );
        };
    }
);
