
define(
    [
        'jquery',
        'Swarming_SubscribePro/js/model/payment/config'
    ],
    function($, config) {
        'use strict';

        var classes = {
            focused: 'hosted-control-focused',
            valid: 'hosted-control-valid',
            invalid: 'hosted-control-invalid'
        };

        var controlIds = {
            number: '#' + config.getCode() + '_cc_number',
            cvv: '#' + config.getCode() + '_cc_cid'
        };

        var control = function (field) {
            return $(controlIds[field]);
        };

        return {
            addClass: function (field, status) {
                control(field).addClass(classes[status]);
                return this;
            },
            removeClass: function (field, status) {
                control(field).removeClass(classes[status]);
                return this;
            }
        };
    }
);
