define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'mage/translate',
        'Magento_Ui/js/modal/modal',
        'Swarming_SubscribePro/js/action/subscription/change-payment',
        'Swarming_SubscribePro/js/action/payment/load-list'
    ],
    function ($, Component, ko, $t, modal, changePayment, loadPayments) {
        'use strict';

        return Component.extend({

            modal: null,
            
            initialize: function () {
                this._super().observe('paymentProfileId');
                
                this.selectedPaymentProfileId = ko.observable(this.paymentProfileId());
                this.isLoading = ko.observable(false);
                this.payments = ko.observableArray([]);
                this.paymentProfile = ko.observable(this.paymentProfile);
                this.paymentsLoaded = ko.observable(false);
                this.paymentsLoadSuccess = ko.observable(false);
            },

            initModal: function (element) {
                var options = this.modalOptions;
                options.trigger = '[data-trigger=change-payment-'+this.subscriptionId+']';
                options.title = options.title ? $t(options.title) : $t('Change payment method');
                options.buttons = [
                    {
                        text: $t('Continue'),
                        class: 'action primary action-update-payment',
                        click: $.proxy(this.changePayment, this)
                    }
                ];
                options.opened = $.proxy(this.loadPayments, this);
                
                this.modal = modal(options, $(element));
            },

            loadPayments: function () {
                if (!this.paymentsLoaded()) {
                    loadPayments($.proxy(this.initPayments, this), this.paymentsLoaded, this.isLoading, this.messageContainer);
                }
            },

            changePayment: function () {
                if (this.paymentProfileId() == this.selectedPaymentProfileId()) {
                    this.modal.closeModal();
                    return;
                }

                changePayment(
                    this.subscriptionId, 
                    this.selectedPaymentProfileId(), 
                    this.isLoading, 
                    this.messageContainer,
                    $.proxy(this.updatePayment, this)
                );
            },
            
            updatePayment: function (response) {
                this.paymentProfileId(this.selectedPaymentProfileId());
                this.paymentProfile(response);
                this.modal.closeModal();
            },

            initPayments: function (response) {
                var selectedPaymentProfileId = null;
                var isFirst = true;
                var self = this;
                $.each(response, function() {
                    self.payments.push(this);
                    if (isFirst) {
                        selectedPaymentProfileId = this.gateway_token;
                        isFirst = false;
                    }
                    if (this.gateway_token == self.paymentProfileId()) {
                        selectedPaymentProfileId = this.gateway_token;
                    }
                });
                
                this.selectedPaymentProfileId(selectedPaymentProfileId);
                this.paymentsLoadSuccess(true);
            },

            getMaskedCC: function (paymentToken) {
                return JSON.parse(paymentToken.token_details).maskedCC;
            }
        });
    }
);
