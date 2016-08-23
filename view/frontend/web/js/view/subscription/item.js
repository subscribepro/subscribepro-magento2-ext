define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'mage/storage',
        'Magento_Catalog/js/price-utils',
        'Swarming_SubscribePro/js/action/subscription/change-next-order-date',
        'Swarming_SubscribePro/js/action/subscription/skip',
        'Swarming_SubscribePro/js/action/subscription/pause',
        'Swarming_SubscribePro/js/action/subscription/cancel',
        'Swarming_SubscribePro/js/action/subscription/restart',
        'Swarming_SubscribePro/js/action/subscription/change-qty',
        'Swarming_SubscribePro/js/action/subscription/change-interval'
    ],
    function ($, Component, ko, storage, priceUtils, changeNextOrderDate, skip, pause, cancel, restart, changeQty, changeInterval) {
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
                self.nextOrderDate = ko.observable(self.subscription.next_order_date);
                self.product = ko.observable(self.subscription.product);
                self.shippingAddress = ko.observable(self.subscription.shipping_address);
                self.paymentProfile = ko.observable(self.subscription.payment_profile);
                self.showDetails = ko.observable(false);
                self.qtyValues = ko.observableArray(self.getQtyValues(self.product));
                self.selectedQty = ko.observable(self.qty());
                self.selectedInterval = ko.observable(self.interval());
                self.selectedNextOrderDate = ko.observable(self.nextOrderDate());

                self.priceWithDiscount = ko.pureComputed(function() {
                    var discount = parseFloat(self.product().discount);
                    if (self.product().is_discount_percentage) {
                        discount = parseFloat(self.product().price) * self.product().discount;
                    }

                    return self.getFormattedPrice(parseFloat(self.product().price) - discount);
                });

                self.discountText = ko.pureComputed(function() {
                    return self.product().is_discount_percentage
                        ? 100*parseFloat(self.product().discount) + '%'
                        : self.getFormattedPrice(self.product().discount);
                });

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

            getFormattedPrice: function (price) {
                return '$'+priceUtils.formatPrice(price);
            },
            
            qtyChanged: function () {
                var self = this;
                changeQty(this.id(), this.selectedQty(), function() {self.interval(self.selectedInterval())});
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
            },

            getQtyValues: function(product) {
                var values = [];
                for (var i = product().min_qty; i <= product().max_qty; i++) {
                    values.push(i);
                }
                
                return values;
            }
        });
    }
);
