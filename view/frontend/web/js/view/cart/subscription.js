define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'ko',
        'Swarming_SubscribePro/js/model/product/item'
    ],
    function ($, _, Component, ko, productModel) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/cart/subscription',
                isProductLoaded: false,
                product: {}
            },

            priceBoxElement: null,

            initialize: function () {
                this._super();
                this.initProduct(this.productData);

                $(this.qtyFieldSelector).on('change', this.onQtyFieldChanged.bind(this));
            },

            initObservable: function () {
                this._super().observe(['product', 'isProductLoaded']);
                return this;
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

            onQtyFieldChanged: function () {
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
            }
        });
    }
);
