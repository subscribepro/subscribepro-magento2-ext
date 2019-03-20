define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/modal/modal',
        'Magento_Ui/js/form/form',
        'Swarming_SubscribePro/js/model/address/address-options',
        'Magento_Customer/js/model/customer/address',
        'Magento_Customer/js/customer-data',
        'Swarming_SubscribePro/js/action/address/save-in-address-book',
        'Swarming_SubscribePro/js/action/subscription/change-shipping-address',
        'mage/translate'
    ],
    function (
        $,
        ko,
        modal,
        Component,
        addressOptionsHelper,
        Address,
        customerData,
        saveInAddressBook,
        changeAddress,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                isLoading: false,
                addressOptions: [],
                shippingAddress: {},
                currentAddress: {},
                selectedAddress: null,
                saveInAddressBook: 1,
                isCustomerLoggedIn: true,
                isAddressFormVisible: false
            },

            isFormInline: false,

            addressOptions: null,

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading',
                        'shippingAddress',
                        'currentAddress',
                        'selectedAddress',
                        'saveInAddressBook',
                        'isCustomerLoggedIn',
                        'isAddressFormVisible'
                    ]);

                var self = this;

                this.cityRegionPostcodeText = ko.pureComputed(function() {
                    var cityRegionText = [self.shippingAddress().city, self.shippingAddress().region]
                        .filter(function (val) {return val;})
                        .join(', ');

                    return [cityRegionText, self.shippingAddress().postcode]
                        .filter(function (val) {return val;})
                        .join(' ');
                });

                this.customerHasAddresses = ko.pureComputed(function() {
                    return self.addressOptions().length > 1;
                });

                this.selectedAddress.subscribe(function(address) {
                    self.isAddressFormVisible(addressOptionsHelper.isNewAddressOption(address));
                });

                return this;
            },

            initialize: function () {
                this._super();
                this.addressOptions = addressOptionsHelper.getOptions();
                this.currentAddress(this.addressOptions()[0]);
                this.isAddressFormVisible(this.addressOptions().length == 1);
            },

            initModal: function (element) {
                var options = this.modalOptions;
                options.trigger = '[data-trigger=change-shipping-address-' + this.subscriptionId + ']';
                options.title = options.title ? $t(options.title) : $t('Change shipping address');
                options.buttons = [
                    {
                        text: $t('Continue'),
                        class: 'action primary action-update-shipping-address',
                        click: $.proxy(this.onChangeAddress, this)
                    }
                ];

                this.modal = modal(options, $(element));
            },

            addressOptionsText: function (address) {
                return address.getAddressInline();
            },

            onChangeAddress: function () {
                var isValid = true;
                var addressData = {};
                var selectedAddress = this.selectedAddress();
                if (!selectedAddress || addressOptionsHelper.isNewAddressOption(selectedAddress)) {
                    isValid = this.isValidNewAddress();
                    addressData = this.getNewAddressData();
                    // The Address object expects region_id to be a child of the region property
                    // But our form sends it as a top level parameter
                    if (addressData['region_id'] && addressData['region_id'] !== '0') {
                        addressData['region']['region_id'] = addressData['region_id'] + '';
                    }
                    selectedAddress = new Address(addressData);
                }

                if (isValid) {
                    if (selectedAddress.saveInAddressBook) {
                        this.processAddressSaving(this.subscriptionId, selectedAddress, addressData);
                    } else {
                        this.processAddressChanging(this.subscriptionId, selectedAddress);
                    }
                }
            },

            initAddressValidator: function () {
                this.source.set('params.invalid', false);
                this.source.trigger(this.dataScopePrefix + '.data.validate');
                if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                    this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                }
            },

            isValidNewAddress: function () {
                this.initAddressValidator();
                return !this.source.get('params.invalid');
            },

            getNewAddressData: function () {
                var addressData = $.extend(true, {}, this.source.get(this.dataScopePrefix));

                if (!this.customerHasAddresses()) {
                    this.saveInAddressBook(1);
                }
                addressData.save_in_address_book = this.saveInAddressBook() ? 1 : 0;
                if (!addressData.region) {
                    addressData.region = {};
                }
                return addressData;
            },

            processAddressSaving: function (subscriptionId, address, addressData) {
                this.isLoading(true);

                var deferred = $.Deferred();
                saveInAddressBook(address, this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response) {
                        var successMessage = $t('The address has been successfully saved in the address book.');
                        addressData.inline = response;
                        self.addSavedAddress(address);
                        self.closeForm();
                        self.processAddressChanging(subscriptionId, address, successMessage);
                    })
                    .fail(function () {
                        self.isLoading(false);
                    });
            },

            addSavedAddress: function (address) {
                address.saveInAddressBook = 0;
                this.addressOptions.push(address);
                this.selectedAddress(address);
            },

            closeForm: function () {
                this.reset();
                this.isAddressFormVisible(false);
            },

            processAddressChanging: function (subscriptionId, address, successMessage) {
                this.isLoading(true);

                var deferred = $.Deferred();
                changeAddress(subscriptionId, address, this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response, messageContainer) {
                        if (successMessage) {
                            messageContainer.addSuccessMessage({'message': successMessage});
                        }
                        messageContainer.addSuccessMessage({'message': $t('Subscription shipping address has been updated.')});
                        self.updateCurrentAddress(response);
                        self.closeModal();
                        self.scrollToTop();
                    })
                    .fail(function (response, messageContainer) {
                        if (successMessage) {
                            messageContainer.getSuccessMessages().push(successMessage);
                        }
                        self.selectedAddress(self.currentAddress());
                    })
                    .always(function () {
                        self.isLoading(false);
                    });
            },

            updateCurrentAddress: function (response) {
                this.shippingAddress(response);
                this.currentAddress(this.selectedAddress());
            },

            closeModal: function () {
                this.modal.closeModal();
            },

            scrollToTop: function () {
                $("html, body").animate({ scrollTop: 0 }, 500);
            }
        });
    }
);
