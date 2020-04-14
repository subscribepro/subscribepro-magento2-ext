define([
    'underscore',
    'uiComponent',
    'uiLayout',
    'Swarming_SubscribePro/js/model/subscription/loader',
    'Swarming_SubscribePro/js/model/subscription/subscription-component-factory',
    'Swarming_SubscribePro/js/action/subscription/load-list'
], function(_, Component, layout, subscriptionLoader, subscriptionComponentFactory, loadSubscriptions) {
    'use strict';

    return Component.extend({
        defaults: {
            isLoading: subscriptionLoader.isLoading,
            subscriptionCount: 0,
            noSubscriptions: false
        },

        initObservable: function () {
            this._super()
                .observe([
                    'noSubscriptions'
                ]);

            var self = this;
            this.elems.subscribe(function (elems) {
                if (self.subscriptionCount && self.subscriptionCount == elems.length) {
                    self.isLoading(false);
                }
            });
            return this;
        },

        initialize: function () {
            this._super();
            loadSubscriptions(this.onSubscriptionsLoaded.bind(this));
        },

        onSubscriptionsLoaded: function (subscriptions) {
            if (_.isArray(subscriptions) && subscriptions.length > 0) {
                this.subscriptionCount = subscriptions.length;
                _.each(subscriptions, this.renderSubscription.bind(this));
                layout(this.elems());
            } else {
                this.noSubscriptions(true);
                this.isLoading(false);
            }
        },

        renderSubscription: function (subscription) {
            var config = {
                name: this.name,
                subscriptionConfig: this.subscriptionConfig,
                priceConfig: this.priceConfig,
                paymentConfig: this.paymentConfig,
                shippingAddressOptions: this.shippingAddressOptions,
                paymentInfoOptions: this.paymentInfoOptions
            };
            this.elems().push(subscriptionComponentFactory.create(subscription, config));
        }
    });
});
