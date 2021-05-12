
define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader'
], function (storage, urlBuilder, errorProcessor, fullScreenLoader) {
    'use strict';

    return function (orderId, messageContainer) {
        var serviceUrl = urlBuilder.createUrl('/swarming_subscribepro/me/get-order-status/:orderId', {
            orderId: orderId
        });

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl, '', true, 'application/json'
        ).fail(
            function (response) {
                errorProcessor.process(response, messageContainer);
            }
        ).always(
            function () {
                fullScreenLoader.stopLoader();
            }
        );
    };
});
