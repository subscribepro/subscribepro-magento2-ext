/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Swarming_SubscribePro/js/model/payment/config',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        config,
        rendererList
    ) {
        'use strict';

        if (config.isActive()) {
            rendererList.push(
                {
                    type: config.getCode(),
                    component: 'Swarming_SubscribePro/js/view/payment/method-renderer/cc-form'
                }
            );
        }

        return Component.extend({});
    }
);
