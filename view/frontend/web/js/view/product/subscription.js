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
                priceBoxSelector: '.price-box',
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
            },

            initProductPrice: function () {
                var finalPrice = parseFloat(parseFloat(this.product.final_price).toFixed(2));
                var basePrice = this.product.base_price ? parseFloat(parseFloat(this.product.base_price).toFixed(2)) : finalPrice;
                var hasSpecialPrice = finalPrice != basePrice;

                var priceBox = this.priceBoxElement.data('mage-priceBox');
                if (priceBox
                    && priceBox.options
                    && priceBox.options.priceConfig
                    && priceBox.options.priceConfig.prices.finalPrice
                    && priceBox.options.priceConfig.prices.oldPrice
                ) {
                    hasSpecialPrice = priceBox.options.priceConfig.prices.finalPrice.amount != priceBox.options.priceConfig.prices.oldPrice.amount;
                }

                this.productPrice = productPriceModel.create(this.product, this.priceConfig);

                var price = parseFloat(this.priceBoxElement.find('.price').parent().data('price-amount').toFixed(2));
                this.syncProductPrice({oldPrice: {amount: basePrice}, finalPrice: {amount: price}});

                this.productPrice.hasSpecialPrice(hasSpecialPrice);
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
                this.productPrice.setCalculatedPrices(oldPrice, finalPrice);
                this.productPrice.hasSpecialPrice(oldPrice != finalPrice);
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
