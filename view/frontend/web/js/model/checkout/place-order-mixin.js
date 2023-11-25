define([
    'jquery',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/payment/place-order-hooks',
    'mage/storage',
    'mage/utils/wrapper'
], function ($, errorProcessor, fullScreenLoader, customerData, hooks, storage, wrapper) {
    'use strict';

    return function (originalRequest) {
        return wrapper.wrap(originalRequest, function (originalFunction, serviceUrl, payload, messageContainer) {
            var headers = {}, redirectURL = '';

            fullScreenLoader.startLoader();
            _.each(hooks.requestModifiers, function (modifier) {
                modifier(headers, payload);
            });

            return storage.post(
                serviceUrl, JSON.stringify(payload), true, 'application/json', headers
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    redirectURL = response.getResponseHeader('errorRedirectAction');
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                }
            ).done(
                function (response) {
                    var clearData = {
                        'selectedShippingAddress': null,
                        'shippingAddressFromData': null,
                        'newCustomerShippingAddress': null,
                        'selectedShippingRate': null,
                        'selectedPaymentMethod': null,
                        'selectedBillingAddress': null,
                        'billingAddressFromData': null,
                        'newCustomerBillingAddress': null
                    };

                    if (response.responseType !== 'error') {
                        customerData.set('checkout-data', clearData);
                    }
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                    _.each(hooks.afterRequestListeners, function (listener) {
                        listener();
                    });
                }
            );
        });
    };
});
