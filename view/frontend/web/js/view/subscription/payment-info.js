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
            defaults: {
                isLoading: false,
                paymentsLoaded: false,
                paymentsLoadSuccess: false,
                paymentProfile: null,
                paymentProfileId: null,
                selectedPaymentProfileId: null,
                ccIcons: {},
                ccTypesMapper: {},
                payments: []
            },

            modal: null,

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoading',
                        'paymentsLoaded',
                        'paymentsLoadSuccess',
                        'paymentProfile',
                        'paymentProfileId',
                        'selectedPaymentProfileId',
                        'payments'
                    ]);
                return this;
            },

            initialize: function () {
                this._super();
                this.selectedPaymentProfileId(this.paymentProfileId());
            },

            initModal: function (element) {
                var options = this.modalOptions;
                options.trigger = '[data-trigger=change-payment-'+this.subscriptionId+']';
                options.title = options.title ? $t(options.title) : $t('Change payment method');
                options.buttons = [
                    {
                        text: $t('Continue'),
                        class: 'action primary action-update-payment',
                        click: $.proxy(this.onChangePayment, this)
                    }
                ];
                options.opened = $.proxy(this.onOpen, this);

                this.modal = modal(options, $(element));
            },

            onOpen: function () {
                if (this.paymentsLoaded()) {
                    return;
                }
                this.isLoading(true);
                var deferred = $.Deferred();
                loadPayments(this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response) {
                        self.initPayments(response);
                    })
                    .always(function () {
                        self.isLoading(false);
                        self.paymentsLoaded(true);
                    });
            },

            onChangePayment: function () {
                if (this.paymentProfileId() == this.selectedPaymentProfileId()) {
                    this.modal.closeModal();
                    return;
                }

                this.isLoading(true);
                var deferred = $.Deferred();
                changePayment(this.subscriptionId, this.selectedPaymentProfileId(), this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response) {
                        self.paymentProfile(response);
                        self.paymentProfileId(self.selectedPaymentProfileId());
                        self.modal.closeModal();
                        self.scrollToTop();
                    })
                    .always(function () {
                        self.isLoading(false);
                    });
            },

            initPayments: function (response) {
                var self = this;
                $.each(response, function() {
                    self.payments.push(this);
                });

                this.selectedPaymentProfileId(this.paymentProfileId());
                this.paymentsLoadSuccess(true);
            },

            getMaskedCC: function (paymentToken) {
                return JSON.parse(paymentToken.token_details).maskedCC;
            },

            getProfileIcon: function (paymentToken) {
                var type = JSON.parse(paymentToken.token_details).type;
                return this.getCcIcon(type);
            },

            getCcIcon: function (ccType) {
                return this.ccIcons.hasOwnProperty(ccType) ? this.ccIcons[ccType] : false;
            },

            getPlatformCcIcon: function (platformCcType) {
                return this.getCcIcon(
                    this.getMageCardType(platformCcType)
                );
            },

            getMageCardType: function (platformCcType) {
                if (platformCcType && typeof this.ccTypesMapper[platformCcType] !== 'undefined') {
                    return this.ccTypesMapper[platformCcType];
                }
                return null;
            },

            scrollToTop: function () {
                $("html, body").animate({ scrollTop: 0 }, 500);
            }
        });
    }
);
