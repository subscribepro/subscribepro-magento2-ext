/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        var config = window.checkoutConfig.payment;

        if (config['subscribe_pro'].isActive) {
            rendererList.push(
                {
                    type: 'subscribe_pro',
                    component: 'Swarming_SubscribePro/js/view/payment/method-renderer/cc-form'
                }
            );
        }

        return Component.extend({});
    }
);
