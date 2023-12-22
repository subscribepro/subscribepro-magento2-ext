define(
    [
        'jquery',
        'mage/translate',
        'uiComponent',
        'uiLayout',
        'Magento_Ui/js/model/messages',
        'Magento_Customer/js/customer-data',
        'Magento_InstantPurchase/js/view/instant-purchase'
    ],
    function ($, $t, Component, layout, Messages, customerData) {
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
                this.initMessageComponent();
                if (typeof this.product.id !== 'undefined') {
                    this.isProductLoaded(true);
                }
                var self = this;

                function callback() {
                    self.initInstantPurchaseConfig();
                    observer.disconnect();
                }
                const observer = new MutationObserver(callback);
                observer.observe(
                    document.getElementById('instant-purchase'),
                    { attributes: true, childList: true, subtree: true, characterData: true}
                );
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'subscriptionOptionValue',
                        'isProductLoaded',
                        'intervalValue'
                    ])
                    .initProduct();

                $(this.qtyFieldSelector).on('change', this.onQtyFieldChanged.bind(this));
                this.subscriptionOptionValue.subscribe(this.onQtyFieldChanged.bind(this));

                return this;
            },

            initInstantPurchaseConfig: function () {
                const instantPurchase = customerData.get('instant-purchase');
                if (instantPurchase().isNonSubscriptionTransactionActive === false) {
                    this.setPurchaseData(this.subscriptionOptionValue(), instantPurchase());
                    this.subscriptionOptionValue.subscribe(function(optionType){
                        this.setPurchaseData(optionType, instantPurchase())
                    }.bind(this));
                }
            },

            setPurchaseData: function (optionType, data) {
                const button = $('button.instant-purchase');
                if (optionType === 'subscription' && typeof data.spPaymentToken !== "undefined") {
                    data.paymentToken = data.spPaymentToken
                    button.show();
                    return;
                }
                if (optionType === 'onetime_purchase' && typeof data.nonSpPaymentToken !== "undefined") {
                    data.paymentToken = data.nonSpPaymentToken
                    button.show();
                    return;
                }
                button.hide();
            },

            initMessageComponent: function () {
                this.messageContainer = new Messages();
                this.createMessagesComponent();

                return this;
            },

            createMessagesComponent: function () {
                var messagesComponent = {
                    parent: this.name,
                    name: this.name + '.messages',
                    config: {messageContainer: this.messageContainer}
                };

                layout([$.extend(true, this.messages, messagesComponent)]);

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
                this.validateQty(true);
            },

            validateQty: function (showMessages) {
                var qtyField = $(this.qtyFieldSelector);
                var qty = qtyField.val();
                var errorMessage;

                var productMinQty = this.product.min_qty;
                if (productMinQty && qty < productMinQty) {
                    qtyField.val(productMinQty).trigger('change');
                    errorMessage = $t('Product requires minimum quantity of %qty for subscription.').replace('%qty', productMinQty);
                }

                var productMaxQty  = this.product.max_qty;
                if (productMaxQty && qty > productMaxQty) {
                    qtyField.val(productMaxQty).trigger('change');
                    errorMessage = $t('Product requires maximum quantity of %qty for subscription.').replace('%qty', productMaxQty);
                }

                if (showMessages && errorMessage) {
                    this.messageContainer.addErrorMessage({message: errorMessage});
                }
            }
        });
    }
);
