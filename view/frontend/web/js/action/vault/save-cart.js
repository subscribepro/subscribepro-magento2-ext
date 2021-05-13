
define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function saveCart(payload) {
        return $.ajax({
            url: urlBuilder.build('/subscribepro/cards/save'),
            data: payload,
            type: 'post'
        });
    };
});
