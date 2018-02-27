
define(
    [
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-fields',
        'Swarming_SubscribePro/js/model/payment/credit-card-validation/expiration-date-validator'
    ],
    function (expirationFields, expirationDateValidator) {
        'use strict';

        function validate(field, month, year)
        {
            expirationFields.removeClass(field, 'focused');

            var expirationDate = expirationDateValidator(month + '/' + year);

            if (expirationDate.isPotentiallyValid) {
                expirationFields.removeClass('month', 'valid');
                expirationFields.removeClass('month', 'invalid');
                if (expirationDate.isValidMonth) {
                    expirationFields.addClass('month', 'valid');
                } else if (expirationDate.month) {
                    expirationFields.addClass('month', 'invalid');
                }

                expirationFields.removeClass('year', 'invalid');
                expirationFields.removeClass('year', 'valid');
                if (expirationDate.isValidYear) {
                    expirationFields.addClass('year', 'valid');
                } else if (expirationDate.year) {
                    expirationFields.addClass('year', 'invalid');
                }
            } else if (expirationDate.isExpired) {
                expirationFields.removeClass('month', 'valid');
                expirationFields.addClass('month', 'invalid');
                expirationFields.removeClass('year', 'invalid');
                expirationFields.addClass('year', 'valid');
            } else {
                expirationFields.removeClass(field, 'valid');
                expirationFields.addClass(field, 'invalid');
            }
            return expirationDate.isValid;
        }

        return function (isFocused, field, month, year) {
            var isValid = false;
            if (isFocused) {
                expirationFields.removeClass(field, 'invalid');
                expirationFields.addClass(field, 'focused');
            } else {
                isValid = validate(field, month, year)
            }
            return isValid;
        }
    }
);
