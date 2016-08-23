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
                        payments: this.createSubscriptionPaymentsComponent(subscription)
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

        createSubscriptionPaymentsComponent: function (subscription) {
            var messageContainer = new Messages();
            var childrenConfig = this.paymentModalOptions.children;
            childrenConfig.messages.messageContainer = messageContainer;
            
            var rendererComponent = utils.template(
                {
                    name: '${ $.$data.name }',
                    parent: '${ $.$data.parentName }',
                    component: this.paymentModalOptions.component,
                    children: childrenConfig,
                    displayArea: this.paymentModalOptions.displayArea
                }, {
                    name: 'subscription-payments-' + subscription.id,
                    parentName: this.name
                }
            );
            var configOptions = this.paymentModalOptions.config;
            configOptions.subscriptionId = subscription.id;
            configOptions.paymentProfileId = ''+subscription.payment_profile_id;
            configOptions.paymentProfile = subscription.payment_profile;
            configOptions.messageContainer = messageContainer;

            utils.extend(rendererComponent, configOptions);
            return rendererComponent;
        }
    });
});
