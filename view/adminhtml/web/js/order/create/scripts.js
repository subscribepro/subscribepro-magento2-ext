define(['Magento_Sales/order/create/scripts'], function() {
    'use strict';

    window.AdminOrder.prototype.itemsUpdate = function() {
        console.log('in SP scripts.js');
        var area = ['sidebar', 'items', 'shipping_method', 'billing_method','totals', 'giftmessage'];
        // prepare additional fields
        var fieldsPrepare = {update_items: 1};
        var info = $('order-items_grid').select('input', 'select', 'textarea');
        for(var i=0; i<info.length; i++){
            var skipTypes = ['checkbox', 'radio'];
            if(!info[i].disabled && (!skipTypes.includes(info[i].type) || info[i].checked)) {
                fieldsPrepare[info[i].name] = info[i].getValue();
            }
        }
        fieldsPrepare = Object.extend(fieldsPrepare, this.productConfigureAddFields);
        this.productConfigureSubmit('quote_items', area, fieldsPrepare);
        this.orderItemChanged = false;
    };
});
