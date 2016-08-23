define([
    'jquery',
    'uiComponent', 
    'ko',
    'mageUtils',
    'uiLayout',
    'Magento_Ui/js/model/messages',
    'Swarming_SubscribePro/js/model/subscription/loader',
    'Swarming_SubscribePro/js/action/subscription/load-list'
], function($, Component, ko, utils, layout, Messages, subscriptionLoader, loadSubscriptions) {
    'use strict';

    return Component.extend({
        
        isLoading: subscriptionLoader.isLoading,
        noSubscriptions: ko.observable(false),

        initialize: function () {
            this._super();
            loadSubscriptions($.proxy(this.renderSubscriptions, this));
        },

        renderSubscriptions: function (subscriptions) {
            var self = this;
            if ($.isArray(subscriptions) && subscriptions.length > 0) {
                $.each(subscriptions, function() {
                    self.elems().push(self.createRendererComponent(this));
                });
                layout(this.elems());
            } else {
                this.noSubscriptions(true);
            }
         },

        createRendererComponent: function (subscription) {
            var rendererComponent = utils.template(
                {
                    name: '${ $.$data.name }',
                    parent: '${ $.$data.parentName }',
                    component: 'Swarming_SubscribePro/js/view/subscription/item',
                    children: {
                        payments: this.createPaymentInfoComponent(subscription),
                        'shipping-address': this.createShippingAddressComponent(subscription)
                    }
                }, {
                    name: 'subscription-' + subscription.id,
                    parentName: this.name
                }
            );
            var configOptions = {
                subscription: subscription, 
                priceFormat: this.priceFormat
            };
            utils.extend(rendererComponent, configOptions);
            return rendererComponent;
        },

        createPaymentInfoComponent: function (subscription) {
            var messageContainer = new Messages();
            var childrenConfig = this.paymentInfoOptions.children;
            childrenConfig.messages.messageContainer = messageContainer;
            
            var rendererComponent = utils.template(
                {
                    name: '${ $.$data.name }',
                    parent: '${ $.$data.parentName }',
                    component: this.paymentInfoOptions.component,
                    children: childrenConfig,
                    displayArea: this.paymentInfoOptions.displayArea
                }, {
                    name: 'subscription-payments-' + subscription.id,
                    parentName: this.name
                }
            );
            var configOptions = this.paymentInfoOptions.config;
            configOptions.subscriptionId = subscription.id;
            configOptions.paymentProfileId = ''+subscription.payment_profile_id;
            configOptions.paymentProfile = subscription.payment_profile;
            configOptions.messageContainer = messageContainer;

            utils.extend(rendererComponent, configOptions);
            return rendererComponent;
        },

        createShippingAddressComponent: function (subscription) {
            var componentName = 'shipping-address-'+subscription.id;
            var messageContainer = new Messages();
            var childrenConfig = this.shippingAddressOptions.children;
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
                    component: this.shippingAddressOptions.component,
                    children: childrenConfig,
                    displayArea: this.shippingAddressOptions.displayArea,
                    dataScopePrefix: componentName,
                    provider: this.shippingAddressOptions.provider,
                    deps: this.shippingAddressOptions.deps
                }, {
                    name: componentName,
                    parentName: this.name
                }
            );
            var configOptions = this.shippingAddressOptions.config;
            configOptions.subscriptionId = subscription.id;
            configOptions.shippingAddress = subscription.shipping_address;
            configOptions.messageContainer = messageContainer;

            utils.extend(rendererComponent, configOptions);
            return rendererComponent;
        }
    });
});
