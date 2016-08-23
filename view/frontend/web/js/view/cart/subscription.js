define(
    [
        'jquery',
        'uiComponent'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/cart/subscription',
                oneTimePurchaseOption: '',
                subscriptionOption: '',
                subscriptionOnlyMode: '',
                subscriptionAndOneTimePurchaseMode: '',
                qtyFieldSelector: '',
                quoteItemId: 0,
                product: {},
                isProductLoaded: false,
                subscriptionOptionValue: '',
                intervalValue: ''
            },

            initialize: function () {
                this._super();
                this.initProduct();
                this.isProductLoaded(true);
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'subscriptionOptionValue',
                        'isProductLoaded',
                        'intervalValue'
                    ]);

                $(this.qtyFieldSelector).on('change', this.onQtyFieldChanged.bind(this));

                this.subscriptionOptionValue.subscribe(this.onQtyFieldChanged.bind(this));

                return this;
            },

            initProduct: function () {
                if (this.isSubscriptionMode(this.subscriptionOnlyMode)) {
                    this.subscriptionOptionValue(this.subscriptionOption);
                } else {
                    this.subscriptionOptionValue(this.product.default_subscription_option);
                }
                this.intervalValue(this.product.default_interval);

                if (this.isSubscriptionOption(this.subscriptionOption) || this.isSubscriptionMode(this.subscriptionOnlyMode)) {
                    this.validateQty();
                }
            },

            isSubscriptionMode: function(optionMode) {
                return this.product.subscription_option_mode == optionMode;
            },

            isSubscriptionOption: function(optionValue) {
                return this.product.default_subscription_option == optionValue;
            },

            onQtyFieldChanged: function () {
                if (this.subscriptionOptionValue() == this.oneTimePurchaseOption) {
                    return;
                }
                this.validateQty();
            },
            
            validateQty: function () {
                var $qtyField = $(this.qtyFieldSelector);
                var qty = $qtyField.val();

                var productMinQty = this.product.min_qty;
                if (productMinQty && qty < productMinQty) {
                    $qtyField.val(productMinQty).trigger('change');
                }

                var productMaxQty  = this.product.max_qty;
                if (productMaxQty && qty > productMaxQty) {
                    $qtyField.val(productMaxQty).trigger('change');
                }
            }
        });
    }
);
