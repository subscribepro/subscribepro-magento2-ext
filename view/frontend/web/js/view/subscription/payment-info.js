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
                applyToOther: '0',
                showApplyWarning: false,
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
                        'applyToOther',
                        'showApplyWarning',
                        'payments'
                    ]);

                $('body').on('apply-payment-profile', $.proxy(this.applyPaymentProfile, this));
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
                this.applyToOther('0');

                if (this.paymentsLoaded()) {
                    this.selectedPaymentProfileId(this.paymentProfileId());
                    return;
                }

                this.isLoading(true);
                var deferred = $.Deferred();
                loadPayments(this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response) {
                        var customerPayments = [];
                        for (var i in response) {
                            if (response[i].customer_id == parseInt(window.customerData.id)) {
                                customerPayments.push(response[i]);
                            }
                        }
                        self.initPayments(customerPayments);
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

                if (this.applyToOther() == '0') {
                    this.showApplyWarning(true);
                    return;
                }
                this.showApplyWarning(false);
                var isApplyToOther = this.applyToOther() == '2'; // yes = 2, no = 1, 0 - not selected

                this.isLoading(true);
                var deferred = $.Deferred();
                changePayment(this.subscriptionId, this.selectedPaymentProfileId(), isApplyToOther, this.messageContainer, deferred);

                var self = this;
                $.when(deferred)
                    .done(function (response) {
                        if (isApplyToOther) {
                            $('body').trigger('apply-payment-profile', {currentPaymentId: self.paymentProfileId(), paymentProfile: response});
                        } else {
                            self.paymentProfile(response);
                            self.paymentProfileId(self.selectedPaymentProfileId());
                        }
                        self.modal.closeModal();
                        self.scrollToTop();
                    })
                    .always(function () {
                        self.isLoading(false);
                    });
            },

            applyPaymentProfile: function (event, eventData) {
                if (this.paymentProfileId() == eventData.currentPaymentId) {
                    this.paymentProfile(eventData.paymentProfile);
                    this.paymentProfileId(eventData.paymentProfile.id + '');
                }
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
