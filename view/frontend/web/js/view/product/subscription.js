define(
    [
        'jquery',
        'underscore',
        'Swarming_SubscribePro/js/view/cart/subscription',
        'Swarming_SubscribePro/js/model/product/price',
        'priceBox'
    ],
    function ($, _, Component, productPriceModel) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/product/subscription',
                oneTimePurchasePriceTemplate: 'Swarming_SubscribePro/product/price/default/one-time-purchase',
                subscriptionPriceTemplate: 'Swarming_SubscribePro/product/price/default/subscription',
                priceBoxSelector: '[data-role=priceBox]',
                qtyFieldSelector: '#qty',
                product: {},
                priceConfig: {},
                productPrice: {},
                priceBoxElement: null
            },

            initialize: function () {
                this._super();
                
                this.priceBoxElement = this.getPriceBoxElement();
                this.priceBoxElement.on('reloadPrice', this.onPriceChange.bind(this));
                this.initProductPrice();
                this.priceBoxElement.trigger('reloadPrice');
            },

            initProductPrice: function () {
                this.productPrice = productPriceModel.create(this.product, this.priceConfig);
            },

            onPriceChange: function () {
                var priceBox = this.priceBoxElement.data('mage-priceBox');
                if (!priceBox || !priceBox.cache || !priceBox.cache.displayPrices) {
                    return;
                }

                this.syncProductPrice(priceBox.cache.displayPrices);
            },

            syncProductPrice: function (prices) {
                var frontendFinalPrice, frontendPrice;
                var code = this.getFrontendPriceCode();
                if (prices[code]) {
                    frontendFinalPrice = frontendPrice = prices[code].amount;
                }
                if (prices.oldPrice) {
                    frontendPrice = prices.oldPrice.amount;
                }
                this.productPrice.setFrontendPrice(frontendFinalPrice);
                this.productPrice.hasSpecialPrice(frontendPrice != frontendFinalPrice);
            },

            getPriceBoxElement: function () {
                var priceBoxElement = _.find($(this.priceBoxSelector), function(el) {
                    return el && $(el).data('mage-priceBox');
                });
                return $(priceBoxElement);
            },

            getFrontendPriceCode: function () {
                var code = 'finalPrice';
                if (this.priceConfig.displayPriceExcludingTax && this.priceConfig.priceIncludesTax) {
                    code = 'basePrice';
                }

                return code;
            }
        });
    }
);
