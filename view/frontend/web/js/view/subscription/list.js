define([
    'jquery',
    'uiComponent', 
    'ko',
    'uiLayout',
    'Swarming_SubscribePro/js/model/subscription/loader',
    'Swarming_SubscribePro/js/action/subscription/load-list',
    'Swarming_SubscribePro/js/action/subscription/create-subscription-component'
], function($, Component, ko, layout, subscriptionLoader, loadSubscriptions, createSubscriptionComponent) {
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
                var config = {
                    name: this.name,
                    priceFormat: this.priceFormat,
                    shippingAddressOptions: this.shippingAddressOptions,
                    paymentInfoOptions: this.paymentInfoOptions
                };
                $.each(subscriptions, function() {
                    self.elems().push(createSubscriptionComponent(this, config));
                });
                layout(this.elems());
            } else {
                this.noSubscriptions(true);
            }
         }
    });
});
