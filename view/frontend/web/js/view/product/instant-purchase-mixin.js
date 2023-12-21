define(['jquery', 'Magento_Customer/js/customer-data'], function ($, customerData) {
    'use strict';

    return function (target) {
        return target.extend({
            instantPurchase: function () {
                const instantPurchase = customerData.get('instant-purchase');
                if (instantPurchase().isNonSubscriptionTransactionActive === false) {
                    this.paymentToken(instantPurchase().paymentToken);
                }
                this._super();
            }
        });
    };
});
