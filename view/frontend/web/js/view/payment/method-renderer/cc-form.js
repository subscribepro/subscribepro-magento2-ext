/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Swarming_SubscribePro/payment/cc-form'
            },

            getCode: function () {
                return 'subscribe_pro';
            },

            isActive: function () {
                return this.getCode() == this.isChecked();
            }
        });
    }
);
