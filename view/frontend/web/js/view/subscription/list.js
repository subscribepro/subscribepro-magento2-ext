define([
    'jquery',
    'uiComponent', 
    'ko',
    'mageUtils',
    'mage/translate',
    'uiLayout',
    'Swarming_SubscribePro/js/model/subscription/loader',
    'Swarming_SubscribePro/js/action/subscription/load-list'
], function($, Component, ko, utils, $t, layout, subscriptionLoader, loadSubscriptions) {
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
                    component: 'Swarming_SubscribePro/js/view/subscription/item'
                }, {
                    name: 'subscription-' + subscription.id,
                    parentName: this.name
                }
            );
            utils.extend(rendererComponent, {subscription: subscription});
            return rendererComponent;
        }
    });
});
