define(
    [
        'mageUtils',
        'Magento_Ui/js/model/messages'
    ],
    function (utils, Messages) {
        'use strict';
        return {
            create: function (subscription, config) {
                var messageContainer = new Messages();

                var childrenConfig = config.paymentInfoOptions.children;
                childrenConfig.messages.messageContainer = messageContainer;

                var rendererComponent = utils.template(
                    {
                        name: '${ $.$data.name }',
                        parent: '${ $.$data.parentName }',
                        component: config.paymentInfoOptions.component,
                        children: childrenConfig,
                        displayArea: config.paymentInfoOptions.displayArea
                    }, {
                        name: 'subscription-payments-' + subscription.id,
                        parentName: config.name
                    }
                );
                var configOptions = config.paymentInfoOptions.config;
                configOptions.subscriptionId = subscription.id;
                configOptions.paymentProfileId = ''+subscription.payment_profile_id;
                configOptions.paymentProfile = subscription.payment_profile;
                configOptions.ccIcons = config.paymentConfig.ccIcons;
                configOptions.ccTypesMapper = config.paymentConfig.ccTypesMapper;
                configOptions.messageContainer = messageContainer;

                utils.extend(rendererComponent, configOptions);
                return rendererComponent;
            }
        };
    }
);
