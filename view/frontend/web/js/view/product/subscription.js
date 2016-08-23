define(
    [
        'jquery',
        'underscore',
        'Swarming_SubscribePro/js/view/cart/subscription',
        'priceBox'
    ],
    function ($, _, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/product/subscription',
                priceBoxSelector: '.price-box',
                qtyFieldSelector: '#qty'
            },

            priceBoxElement: null,

            initialize: function () {
                this._super();
                
                if (this.priceBoxSelector) {
                    this.priceBoxElement = this.getPriceBoxElement();
                    this.priceBoxElement.on('reloadPrice', this.onPriceChange.bind(this));
                    this.initProductPrice();
                }
            },

            initProductPrice: function () {
                var priceBox = this.priceBoxElement.data('mage-priceBox');
                if (!priceBox || !priceBox.options || !priceBox.options.prices) {
                    return;
                }

                this.syncProductPrice(priceBox.options.prices);
            },

            onPriceChange: function () {
                var priceBox = this.priceBoxElement.data('mage-priceBox');
                if (!priceBox || !priceBox.cache || !priceBox.cache.displayPrices) {
                    return;
                }

                this.syncProductPrice(priceBox.cache.displayPrices);
            },

            syncProductPrice: function (prices) {
                var finalPrice, oldPrice;
                if (prices.finalPrice) {
                    finalPrice = oldPrice = prices.finalPrice.amount;
                }
                if (prices.oldPrice) {
                    oldPrice = prices.oldPrice.amount;
                }
                this.product().setCalculatedPrices(oldPrice, finalPrice);
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
