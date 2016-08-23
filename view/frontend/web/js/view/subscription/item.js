define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Swarming_SubscribePro/js/action/subscription/change-next-order-date',
        'Swarming_SubscribePro/js/action/subscription/skip',
        'Swarming_SubscribePro/js/action/subscription/pause',
        'Swarming_SubscribePro/js/action/subscription/cancel',
        'Swarming_SubscribePro/js/action/subscription/restart',
        'Swarming_SubscribePro/js/action/subscription/change-qty',
        'Swarming_SubscribePro/js/action/subscription/change-interval',
        'Swarming_SubscribePro/js/model/product/item'
    ],
    function ($, Component, ko, changeNextOrderDate, skip, pause, cancel, restart, changeQty, changeInterval, productModel) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/subscription/item'
            },

            initialize: function () {
                var self = this;
                self._super();
                
                self.id = ko.observable(self.subscription.id);
                self.interval = ko.observable(self.subscription.interval);
                self.qty = ko.observable(self.subscription.qty);
                self.status = ko.observable(self.subscription.status);
                self.product = productModel.create(self.subscription.product, self.priceFormat);
                self.nextOrderDate = ko.observable(self.subscription.next_order_date);
                self.shippingAddress = ko.observable(self.subscription.shipping_address);
                self.paymentProfile = ko.observable(self.subscription.payment_profile);
                self.showDetails = ko.observable(false);
                self.qtyValues = ko.observableArray(self.product.getQtyValues());
                self.selectedQty = ko.observable(self.qty());
                self.selectedInterval = ko.observable(self.interval());
                self.selectedNextOrderDate = ko.observable(self.nextOrderDate());

                self.canChangeNextOrderDate = ko.pureComputed(function() {
                    var nextOrderDate = new Date(self.nextOrderDate());
                    var dateToCompare = new Date();
                    dateToCompare.setDate(dateToCompare.getDate() + 2);

                    return self.isStatus(['Active']) && nextOrderDate >= dateToCompare;
                });

                self.cityRegionPostcodeText = ko.pureComputed(function() {
                    var address = self.shippingAddress();
                    var cityRegionText = [address.city, address.region].filter(function (val) {return val;}).join(', ');

                    return [cityRegionText, address.postcode].filter(function (val) {return val;}).join(' ');
                });

                self.isVisiblePaymentInfo = ko.pureComputed(function() {
                    return self.paymentProfile().creditcard_last_digits && self.paymentProfile().creditcard_last_digits.length > 0;
                });
            },

            isStatus: function (statuses) {
                return !($.inArray(this.status(), statuses) == -1);
            },
           
            toggleShowDetails: function() {
                this.showDetails(!this.showDetails());
            },

            qtyChanged: function () {
                var self = this;
                changeQty(this.id(), this.selectedQty(), function() {self.qty(self.selectedQty())});
            },
            
            intervalChanged: function () {
                var self = this;
                changeInterval(this.id(), this.selectedInterval(), function() {self.interval(self.selectedInterval())});
            },

            nextOrderDateChanged: function () {
                var self = this;
                changeNextOrderDate(this.id(), this.selectedNextOrderDate(), function() {self.nextOrderDate(self.selectedNextOrderDate())});
            },

            skip: function () {
                skip(this.id(), this.nextOrderDate)
            },

            pause: function () {
                pause(this.id(), this.status);
            },

            cancel: function () {
                cancel(this.id(), this.status);
            },

            restart: function () {
                restart(this.id(), this.status);
            }
        });
    }
);
