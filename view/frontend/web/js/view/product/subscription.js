define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'ko',
        'Swarming_SubscribePro/js/model/product/item',
        'priceBox'
    ],
    function ($, _, Component, ko, productModel) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/product/subscription',
                priceBoxSelector: '.price-box',
                qtyFieldSelector: '#qty',
                product: {}
            },

            priceBoxElement: null,

            initialize: function () {
                this._super().observe('product');
                
                this.isProductLoaded = ko.observable(false);
                this.initProduct(this.productData);
                
                if (this.priceBoxSelector) {
                    this.priceBoxElement = this.getPriceBoxElement();
                    this.priceBoxElement.on('reloadPrice', this.onPriceChange.bind(this));
                }

                $(this.qtyFieldSelector).on('change', this.onQtyFieldChanged.bind(this));
            },

            initProduct: function (product) {
                this.product(productModel.create(product, this.priceFormat));
                this.subscriptionOptionValue = ko.observable(this.product().defaultSubscriptionOption());
                this.intervalValue = ko.observable(this.product().defaultInterval());
                this.isProductLoaded(true);
                this.subscriptionOptionValue.subscribe(this.onQtyFieldChanged.bind(this));
                
                if (this.product().isSubscriptionMode(this.subscriptionOnlyMode)) {
                    this.subscriptionOptionValue(this.subscriptionOption);
                }
                if ((this.product().isSubscriptionOption(this.subscriptionOption) 
                    || this.product().isSubscriptionMode(this.subscriptionOnlyMode))
                    && this.product().minQty() > $(this.qtyFieldSelector).val() 
                ) {
                    $(this.qtyFieldSelector).val(this.product().minQty()).trigger('change');
                }
            },

            onQtyFieldChanged: function (event) {
                if (this.subscriptionOptionValue() == this.oneTimePurchaseOption) {
                    return;
                }

                var field = $(this.qtyFieldSelector);
                if (field.val() < this.product().minQty()) {
                    field.val(this.product().minQty()).trigger('change');
                }
                if (this.product().maxQty() && field.val() > this.product().maxQty()) {
                    field.val(this.product().maxQty()).trigger('change');
                }
            },

            onPriceChange: function () {
                var priceBox = this.priceBoxElement.data('mage-priceBox');
                if (!priceBox || !priceBox.cache || !priceBox.cache.displayPrices) {
                    return;
                }

                var prices = priceBox.cache.displayPrices;
                if (prices.finalPrice) {
                    this.product().finalPrice(prices.finalPrice.amount);
                    this.product().price(prices.finalPrice.amount);
                }
                if (prices.oldPrice) {
                    this.product().price(prices.oldPrice.amount);
                }
            },
            
            getPriceBoxElement: function () {
                var priceBoxElement = _.find($(this.priceBoxSelector), function(el) {
                    return el && $(el).data('mage-priceBox');
                });
                
                return $(priceBoxElement);
            }
        });
    }
);
