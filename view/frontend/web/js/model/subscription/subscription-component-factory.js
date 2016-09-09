define(
    [
        'mageUtils',
        'Swarming_SubscribePro/js/model/subscription/payment-info-component-factory',
        'Swarming_SubscribePro/js/model/subscription/shipping-address-component-factory'
    ],
    function (utils, paymentInfoComponentFactory, shippingAddressComponentFactory) {
        'use strict';
        return {
            create: function (subscription, config) {
                var rendererComponent = utils.template(
                    {
                        name: '${ $.$data.name }',
                        parent: '${ $.$data.parentName }',
                        component: 'Swarming_SubscribePro/js/view/subscription/item',
                        children: {
                            'payments': paymentInfoComponentFactory.create(subscription, config),
                            'shipping-address': shippingAddressComponentFactory.create(subscription, config)
                        }
                    }, {
                        name: 'subscription-' + subscription.id,
                        parentName: config.name
                    }
                );
                var configOptions = {
                    subscription: subscription,
                    subscriptionConfig: config.subscriptionConfig,
                    priceConfig: config.priceConfig
                };
                utils.extend(rendererComponent, configOptions);
                return rendererComponent;
            }
        };
    }
);
