
define(
    [
        'jquery',
        'Swarming_SubscribePro/js/model/payment/config'
    ],
    function ($, config) {
        'use strict';

        var classes = {
            focused: 'control-focused',
            valid: 'control-valid',
            invalid: 'control-invalid'
        };

        var controlIds = {
            month: '#' + config.getCode() + '_expiration',
            year: '#' + config.getCode() + '_expiration_yr'
        };

        var control = function (field) {
            return $(controlIds[field]).parent('.child-control');
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
