define(
    [
        'jquery',
        'mageUtils',
        'Magento_Ui/js/model/messages'
    ],
    function ($, utils, Messages) {
        'use strict';
        return {
            create: function (subscription, config) {
                var componentName = 'shipping-address-' + subscription.id;
                var messageContainer = new Messages();

                var childrenConfig = config.shippingAddressOptions.children;
                childrenConfig.messages.messageContainer = messageContainer;

                $.each(childrenConfig['shipping-address-fieldset'].children, function(name, child) {
                    if (child.config && child.config.customScope) {
                        child.config.customScope = componentName;
                    }
                    if (child.dataScope) {
                        child.dataScope = componentName + '.' + name;
                    }
                    if (name == 'region_id') {
                        child.config.customEntry = componentName + '.region';
                        child.filterBy.target = '${ $.provider }:${ $.parentScope }.country_id';
                    }
                });

                var rendererComponent = utils.template(
                    {
                        name: '${ $.$data.name }',
                        parent: '${ $.$data.parentName }',
                        parentScope: componentName,
                        component: config.shippingAddressOptions.component,
                        children: childrenConfig,
                        displayArea: config.shippingAddressOptions.displayArea,
                        dataScopePrefix: componentName,
                        provider: config.shippingAddressOptions.provider,
                        deps: config.shippingAddressOptions.deps
                    }, {
                        name: componentName,
                        parentName: config.name
                    }
                );
                var configOptions = config.shippingAddressOptions.config;
                configOptions.subscriptionId = subscription.id;
                configOptions.shippingAddress = subscription.shipping_address;
                configOptions.messageContainer = messageContainer;

                utils.extend(rendererComponent, configOptions);
                return rendererComponent;
            }
        };
    }
);
