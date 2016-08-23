
define(
    [
        'underscore',
        'Swarming_SubscribePro/js/model/payment/config',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/hosted-fields'
    ],
    function(_, config, hostedFields) {
        'use strict';

        var isValidCardNumber = true;
        var isValidCvv = true;

        var focusProcessor = function (name) {
            hostedFields.removeClass(name, 'invalid');
            hostedFields.addClass(name, 'focused');
        };

        var blurProcessor = function (name) {
            hostedFields.removeClass(name, 'focused');
            if (name == 'number' && !isValidCardNumber) {
                hostedFields.addClass(name, 'invalid');
            }
            if (name == 'cvv' && !isValidCvv) {
                hostedFields.addClass('cvv', 'invalid');
            }
        };

        var inputNumberProcessor = function (inputData) {
            if (inputData.validNumber || !inputData.numberLength) {
                hostedFields.removeClass('number', 'invalid');
            }
            if (inputData.validNumber && inputData.numberLength) {
                hostedFields.addClass('number', 'valid');
            }
            if (!inputData.validNumber || !inputData.numberLength) {
                hostedFields.removeClass('number', 'valid');
            }
        };

        var inputCvvProcessor = function (inputData) {
            if (inputData.validCvv || !inputData.cvvLength) {
                hostedFields.removeClass('cvv', 'invalid');
            }
            if (inputData.validCvv && inputData.cvvLength) {
                hostedFields.addClass('cvv', 'valid');
            }
            if (!inputData.validCvv || !inputData.cvvLength) {
                hostedFields.removeClass('cvv', 'valid');
            }
        };

        var inputProcessor = function (name, inputData) {
            isValidCardNumber = (inputData.validNumber || !inputData.numberLength);
            isValidCvv = (inputData.validCvv || !inputData.cvvLength);

            inputNumberProcessor(inputData);
            inputCvvProcessor(inputData);
        };

        /**
         * Find mage card type by SubscribePro card type
         */
        var getMageCardType = function (cardType) {
            var storedCardType = null,
                mapper = config.getCcTypesMapper();

            if (cardType && typeof mapper[cardType] !== 'undefined') {
                storedCardType = mapper[cardType];

                if (config.getAvailableCardTypes()[storedCardType]) {
                    return storedCardType;
                }
            }

            return null;
        };

        var evenProcessor = {
            focus: focusProcessor,
            blur: blurProcessor,
            input: inputProcessor
        };

        return function(name, event, inputData) {

            if (_.isFunction(evenProcessor[event])) {
                evenProcessor[event](name, inputData)
            }

            var cardType = undefined;
            if (event == 'input' && name == 'number' && inputData.cardType !== undefined) {
                cardType = getMageCardType(inputData.cardType);
            }

            return {
                isValid: inputData.validNumber && inputData.validCvv,
                cardType: cardType
            }
        }
    }
);
