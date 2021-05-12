var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'Swarming_SubscribePro/js/action/checkout/set-payment-information-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Swarming_SubscribePro/js/action/checkout/place-order-mixin': true
            }
        }
    }
};
