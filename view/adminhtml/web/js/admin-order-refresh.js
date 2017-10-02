define(['mage/utils/wrapper', "Swarming_SubscribePro/js/model/payment/config"], function(wrapper, config){
    'use strict';

    return function() {
        var setStoreId = window.order.setStoreId;
        var setStoreId = wrapper.wrap(setStoreId, function(original, id) {
            original(id);
            // The store ID is set when the page loads as store 1.
            // The SP configuration is pulled based on that store ID and is
            // stored in a javascript variable to be used during the payment
            // step.
            // The page is dynamically changed without a reload throughout the
            // checkout process and I couldn't figure out how to update the
            // config. A simple page refresh retains the checkout progress and
            // reloads the config using the correct store ID.
            location.reload();
        });
        window.order.setStoreId = setStoreId;

        return window.order;
    }
});
