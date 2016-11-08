define(
    [
        'jquery',
        'underscore',
        'Swarming_SubscribePro/js/view/cart/subscription',
        'Swarming_SubscribePro/js/model/product/price',
        'mage/translate'
    ],
    function ($, _, Component, productPriceModel, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/product/subscription',
                oneTimePurchasePriceTemplate: 'Swarming_SubscribePro/product/price/msrp/one-time-purchase',
                subscriptionPriceTemplate: 'Swarming_SubscribePro/product/price/msrp/subscription',
                qtyFieldSelector: '#qty',
                product: {},
                priceConfig: {},
                productPrice: {}
            },

            initialize: function () {
                this._super();
                
                this.initProductPrice();
                this.initSubscriptionDiscountText();
            },

            initProductPrice: function () {
                this.productPrice = productPriceModel.create(this.product, this.priceConfig);
                this.productPrice.hasSpecialPrice(false);
                this.productPrice.price(this.msrpPrice);
            },

            initSubscriptionDiscountText: function () {
                this.subscriptionDiscountText = $t('%discount subscription discount')
                    .replace('%discount', this.productPrice.discountText());
            }
        });
    }
);
