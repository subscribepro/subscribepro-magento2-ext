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
                
                this.priceBoxElement = this.getPriceBoxElement();
                this.priceBoxElement.on('reloadPrice', this.onPriceChange.bind(this));
                this.initProductPrice();
            },

            initProductPrice: function () {
                var priceBox = this.priceBoxElement.data('mage-priceBox');
                var price = parseFloat(this.priceBoxElement.find('.price').parent().data('price-amount').toFixed(2));
                var finalPrice = parseFloat(parseFloat(this.finalPrice).toFixed(2));
                var basePrice = this.basePrice ? parseFloat(parseFloat(this.basePrice).toFixed(2)) : finalPrice;
                var hasSpecialPrice = finalPrice != basePrice;
                if (priceBox && priceBox.options && priceBox.options.priceConfig
                    && priceBox.options.priceConfig.prices.finalPrice && priceBox.options.priceConfig.prices.oldPrice
                ) {
                    hasSpecialPrice = priceBox.options.priceConfig.prices.finalPrice.amount != priceBox.options.priceConfig.prices.oldPrice.amount;
                }

                this.syncProductPrice({oldPrice: {amount: basePrice}, finalPrice: {amount: price}});
                this.product().hasSpecialPrice(hasSpecialPrice);
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
                this.product().hasSpecialPrice(oldPrice != finalPrice);
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
