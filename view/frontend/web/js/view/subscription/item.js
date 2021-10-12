define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Swarming_SubscribePro/js/model/product/price',
        'Swarming_SubscribePro/js/action/subscription/change-next-order-date',
        'Swarming_SubscribePro/js/action/subscription/skip',
        'Swarming_SubscribePro/js/action/subscription/pause',
        'Swarming_SubscribePro/js/action/subscription/cancel',
        'Swarming_SubscribePro/js/action/subscription/restart',
        'Swarming_SubscribePro/js/action/subscription/change-qty',
        'Swarming_SubscribePro/js/action/subscription/change-interval',
        'mage/collapsible'
    ],
    function ($, ko, Component, productPriceModel, changeNextOrderDate, skip, pause, cancel, restart, changeQty, changeInterval) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/subscription/item',
                showDetails: false,
                nextOrderDate: null,
                selectedNextOrderDate: null,
                qty: null,
                selectedQty: null,
                interval: null,
                selectedInterval: null,
                status: null,
                priceConfig: {},
                productPrice: {}
            },

            initialize: function () {
                this._super();

                this.nextOrderDate(this.subscription.next_order_date);
                this.selectedNextOrderDate(this.subscription.next_order_date);
                this.qty(this.subscription.qty);
                this.selectedQty(this.subscription.qty);
                this.interval(this.subscription.interval);
                this.selectedInterval(this.subscription.interval);
                this.status(this.subscription.status);

                this.selectedNextOrderDate.subscribe(function (nextOrderDate) {
                    this.nextOrderDateChanged(nextOrderDate);
                }, this);
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'showDetails',
                        'nextOrderDate',
                        'selectedNextOrderDate',
                        'qty',
                        'selectedQty',
                        'interval',
                        'selectedInterval',
                        'status'
                    ]);

                this.productPrice = productPriceModel.create(this.subscription.product, this.priceConfig);

                var self = this;
                this.canChangeNextOrderDate = ko.pureComputed(function() {
                    var nextOrderDate = new Date(self.nextOrderDate());
                    var dateToCompare = new Date();
                    dateToCompare.setDate(dateToCompare.getDate() + self.subscriptionConfig.minDaysToNextOrder);

                    nextOrderDate.setHours(0,0,0,0);
                    dateToCompare.setHours(0,0,0,0);
                    return self.isStatus(['Active'])
                        && (nextOrderDate >= dateToCompare || nextOrderDate < new Date());
                });
                return this;
            },

            getSubscriptionId: function () {
                return this.subscription.id;
            },

            getProductName: function () {
                return this.subscription.product.name;
            },

            getProductUrl: function () {
                return this.subscription.product.url;
            },

            getProductImageUrl: function () {
                return this.subscription.product.image_url;
            },

            getProductOptionList: function () {
                return this.subscription.product.option_list;
            },

            initOptionList: function () {
                $('#subscription-' + this.getSubscriptionId() + ' .option-list').collapsible({"openedState": "active"});
            },

            getProductInterval: function () {
                return this.subscription.product.intervals;
            },

            getProductPriceFormatted: function () {
                return this.productPrice.formattedPrice();
            },

            getDiscountMessage: function () {
                return this.productPrice.priceWithDiscountText();
            },

            getUserDefinedFields: function() {
                var userDefinedFields = $.parseJSON(this.subscription.user_defined_fields);
                return userDefinedFields;
            },

            isShippingRequired: function () {
                return this.subscription.requires_shipping;
            },

            isStatus: function (statuses) {
                return !($.inArray(this.status(), statuses) == -1);
            },

            toggleShowDetails: function() {
                this.showDetails(!this.showDetails());
            },

            getQtyValues: function () {
                var values = [];
                for (var i = this.subscription.product.min_qty; i <= this.subscription.product.max_qty; i++) {
                    values.push(i);
                }
                return values;
            },

            qtyChanged: function () {
                var deferred = $.Deferred();
                changeQty(this.getSubscriptionId(), this.selectedQty(), deferred);

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.qty(self.selectedQty());
                    })
                    .fail(function () {
                        self.selectedQty(self.qty());
                    })
                    .always(this.scrollToTop);
            },

            intervalChanged: function () {
                var deferred = $.Deferred();
                changeInterval(this.getSubscriptionId(), this.selectedInterval(), deferred);

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.interval(self.selectedInterval());
                    })
                    .fail(function () {
                        self.selectedInterval(self.interval());
                    })
                    .always(this.scrollToTop);
            },

            nextOrderDateChanged: function (nextOrderDate) {
                var subscriptionId = this.getSubscriptionId();

                var deferred = $.Deferred();
                changeNextOrderDate(subscriptionId, nextOrderDate, deferred);

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.nextOrderDate(nextOrderDate);
                    })
                    .fail(function () {
                        $('#subscription-'+subscriptionId+' .next-order-date').datepicker('setDate', self.nextOrderDate());
                    })
                    .always(this.scrollToTop);
            },

            skip: function () {
                var subscriptionId = this.getSubscriptionId();
                var deferred = $.Deferred();
                skip(subscriptionId, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (date) {
                        self.nextOrderDate(date);
                        $('#subscription-'+subscriptionId+' .next-order-date').datepicker('setDate', date);
                    })
                    .always(this.scrollToTop);
            },

            pause: function () {
                var deferred = $.Deferred();
                pause(this.getSubscriptionId(), deferred);

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.status('Paused');
                    })
                    .always(this.scrollToTop);
            },

            cancel: function () {
                var deferred = $.Deferred();
                cancel(
                    this.getSubscriptionId(),
                    this.subscriptionConfig.cancelContent,
                    this.subscriptionConfig.isCancelAllowed,
                    deferred
                );

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.status('Cancelled');
                    })
                    .always(this.scrollToTop);
            },

            restart: function () {
                var deferred = $.Deferred();
                restart(this.getSubscriptionId(), deferred);

                var self = this;
                $.when(deferred)
                    .done(function () {
                        self.status('Active');
                    })
                    .always(this.scrollToTop);
            },

            scrollToTop: function () {
                $("html, body").animate({ scrollTop: 0 }, 500);
            }
        });
    }
);
