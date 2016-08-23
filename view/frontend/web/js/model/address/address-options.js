define(
    [
        'jquery',
        'ko',
        'Magento_Customer/js/model/address-list',
        'mage/translate'
    ],
    function($, ko, addressList, $t) {
        "use strict";

        var newAddressOption = {
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        };
        var addressOptionsItems = addressList().filter(function (address) {
            return address.getType() == 'customer-address';
        });

        addressOptionsItems.push(newAddressOption);
        var addressOptions = ko.observableArray(addressOptionsItems);

        addressOptions.subscribe(function(changes) {
            $.each(changes, function() {
                if (this.status == 'added') {
                    addressOptions().splice(this.index - 1, 2, this.value, newAddressOption);
                }
            });
        }, null, "arrayChange");

        return {
            getOptions: function () {
                return addressOptions;
            },    
            isNewAddressOption: function (address) {
                return address == newAddressOption;
            }    
        };
    }
);