var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'Swarming_SubscribePro/js/action/checkout/set-payment-information-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Swarming_SubscribePro/js/action/checkout/place-order-mixin': true
            },
            'Magento_Checkout/js/model/place-order': {
                'Swarming_SubscribePro/js/model/checkout/place-order-mixin': true
            },
            'Magento_InstantPurchase/js/view/instant-purchase': {
                'Swarming_SubscribePro/js/view/product/instant-purchase-mixin': true
            }
        }
    }
};
