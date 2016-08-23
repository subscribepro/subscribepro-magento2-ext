define(
    [
        'mageUtils',
        'Swarming_SubscribePro/js/action/payment/create-payment-info-component',
        'Swarming_SubscribePro/js/action/address/create-shipping-address-component'
    ],
    function (utils, createPaymentInfoComponent, createShippingAddressComponent) {
        'use strict';
        return function (subscription, config) {
            var rendererComponent = utils.template(
                {
                    name: '${ $.$data.name }',
                    parent: '${ $.$data.parentName }',
                    component: 'Swarming_SubscribePro/js/view/subscription/item',
                    children: {
                        payments: createPaymentInfoComponent(subscription, config),
                        'shipping-address': createShippingAddressComponent(subscription, config)
                    }
                }, {
                    name: 'subscription-' + subscription.id,
                    parentName: config.name
                }
            );
            var configOptions = {
                subscription: subscription,
                priceFormat: config.priceFormat
            };
            utils.extend(rendererComponent, configOptions);
            return rendererComponent;
        };
    }
);
