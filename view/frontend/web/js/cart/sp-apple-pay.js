define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'domReady'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            buttonId: '',
            merchantDomainName: '',
            apiAccessToken: '',
            paymentRequest: {},
            applePayBtn: '',
            createSessionUrl: '',
            onShippingContactSelectedUrl: '',
            onShippingMethodSelectedUrl: '',
            onPaymentAuthorizedUrl: '',
            displayName: 'MERCHANT'
        },
        initialize: function (config, node) {
            this._super();

            // Save config
            this.defaults = config;
            if (!node.id.length) {
                console.error($t('ApplePay button element ID not found'));
                return false;
            }
            this.defaults.buttonId = node.id;

            // Show button(s)
            this.showApplePayButtons();
        },
        showApplePayButtons:  function () {
            // Check if user has Apple Pay and canMakePayments
            if (window.ApplePaySession) {
                if (ApplePaySession.canMakePayments) {
                    $('#'+this.defaults.buttonId)
                        .click(this.onApplePayButtonClicked.bind(this))
                        .show();
                }
            }
        },
        onApplePayButtonClicked: function () {
            var self = this,
                displayName = self.displayName;

            // Pre-configured paymentRequest
            const paymentRequest = self.defaults.paymentRequest;

            // Set merchant display name
            paymentRequest.total = self.replaceTotalLabel(paymentRequest.total, displayName);

            // Create session object
            const session = new ApplePaySession(1, paymentRequest);

            // Call Merchant Validation
            session.onvalidatemerchant = function (event) {
                // Requests an Apple Pay merchant session from Subscribe Pro platform and returns a promise.
                $.ajax({
                    url: self.defaults.createSessionUrl,
                    type: "POST",
                    dataType: "json",
                    contentType: "application/json; charset=utf-8",
                    crossDomain: true,
                    headers: {
                        'Authorization': 'Bearer ' + self.defaults.apiAccessToken
                    },
                    data: JSON.stringify({
                        url: event.validationURL,
                        merchantDomainName: self.defaults.merchantDomainName,
                    }),
                success: function (data, textStatus, jqXHR) {
                    // Save display name
                    self.displayName = data.displayName;
                    // Complete validation
                    session.completeMerchantValidation(data);
                },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(errorThrown);
                        session.abort();
                    }
                });
            };
            // New shipping contact was selected for payment sheet is init'd the first time
            session.onshippingcontactselected = function (event) {
                // Fetch shipping methods when sheet shown and when new contact chosen
                if (!self.defaults.onShippingContactSelectedUrl) {
                    console.error('Invalid shipping url');
                    session.abort();
                    return false;
                }

                jQuery.ajax({
                    url: self.defaults.onShippingContactSelectedUrl,
                    type: "POST",
                    dataType: "json",
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify({
                        shippingContact: event.shippingContact
                    }),
                success: function (data, textStatus, jqXHR) {
                    let applePayStatus = ApplePaySession.STATUS_SUCCESS;

                    if (!data.success) {
                        if (data.is_exception) {
                            console.error('--> onShippingContactSelected: Exception');
                            console.error(data.exception_message);
                            session.abort();
                            alert(data.message);
                        } else if (data.errorCode && data.errorCode.length) {
                            applePayStatus = ApplePaySession.STATUS_FAILURE;
                            let ApplePayShippingContactUpdate = {
                                status: applePayStatus,
                                newShippingMethods: data.newShippingMethods,
                                newLineItems: data.newLineItems,
                                newTotal: self.replaceTotalLabel(data.newTotal, self.displayName),
                                errors: []
                            };

                            ApplePayShippingContactUpdate.errors.push(
                                new ApplePayError(
                                    data.errorCode,
                                    data.contactField,
                                    data.message
                                )
                            );
                            console.log('--> onShippingContactSelected: STATUS_FAILED');
                            session.completeShippingContactSelection(ApplePayShippingContactUpdate);
                        } else {
                            console.error(data);
                            session.abort();
                        }

                        return false;
                    }

                    if (!data.newShippingMethods.length) {
                        applePayStatus = ApplePaySession.STATUS_FAILURE;
                    }
                    session.completeShippingContactSelection(
                        applePayStatus,
                        data.newShippingMethods,
                        self.replaceTotalLabel(data.newTotal, self.displayName),
                        data.newLineItems
                    );
                },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log('--> Ajax: error');
                        console.error(errorThrown.message);
                        session.abort();
                    }
                });
            };
            /**
             * Shipping Method Selection
             * If the user changes their chosen shipping method we need to recalculate
             * the total price. We can use the shipping method identifier to determine
             * which method was selected.
             */
            session.onshippingmethodselected = function (event) {
                // Fetch shipping methods when sheet shown and when new contact chosen
                $.ajax({
                    url: self.defaults.onShippingMethodSelectedUrl,
                    type: "POST",
                    dataType: "json",
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify({
                        shippingMethod: event.shippingMethod
                    }),
                success: function (data, textStatus, jqXHR) {

                    if (!data.success) {
                        console.error('--> onShippingMethodSelected: STATUS_FAILED');
                        if (data.is_exception) {
                            console.error(data.exception_message);
                        }
                        session.abort();
                        alert(data.message);

                        return false;
                    }

                    session.completeShippingMethodSelection(
                        ApplePaySession.STATUS_SUCCESS,
                        self.replaceTotalLabel(data.newTotal, self.displayName),
                        data.newLineItems
                    );
                },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error('--> Ajax: Error');
                        console.error(errorThrown.message);
                        session.abort();
                    }
                });
            };
            /**
             * Payment Authorization
             * Here you receive the encrypted payment data. You would then send it
             * on to your payment provider for processing, and return an appropriate
             * status in session.completePayment()
             */
            session.onpaymentauthorized = function (event) {
                $.ajax({
                    url: self.defaults.onPaymentAuthorizedUrl,
                    type: "POST",
                    dataType: "json",
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify({
                        payment: event.payment
                    }),
                success: function (data, textStatus, jqXHR) {

                    if (!data.success) {
                        console.error('--> onPaymentAuthorized: STATUS_FAILED');
                        if (data.is_exception) {
                            console.error(data.exception_message);
                        } else {
                            console.log(data);
                        }
                        session.abort();
                        alert(data.message);

                        return false;
                    }

                    // Complete payment
                    session.completePayment(ApplePaySession.STATUS_SUCCESS);

                    // Redirect to success page
                    if (data.redirectUrl) {
                        window.location.href = data.redirectUrl;
                    } else {
                        console.error('--> completePayment: true');
                        console.error('--> No RedirectUrl');
                        console.log(data);
                    }
                },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error('--> Ajax: ERROR');
                        console.error(errorThrown.message);
                        session.abort();
                        alert(errorThrown.message);
                    }
                });
            };

            // Start the Apple Pay session
            // All our handlers are setup
            session.begin();
        },
        /**
         * Helper method to replace label in total line item with Merchant Display Name
         *
         * @param total
         * @param label
         * @returns {{label: *, amount: *}}
         */
        replaceTotalLabel: function (total, label) {
            var newTotal = {
                label: label,
                amount: total.amount
            };
            if (total.type) {
                newTotal.type = total.type;
            }

            return newTotal;
        }
    });
});
