
define(
    [
        'mageUtils',
        'Magento_Payment/js/model/credit-card-validation/expiration-date-validator/parse-date',
        'Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-month-validator',
        'Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-year-validator'
    ],
    function(utils, parseDate, expirationMonth, expirationYear) {
        'use strict';

        function resultWrapper(isValid, isPotentiallyValid, isExpired, month, year, isValidMonth, isValidYear) {
            return {
                isValid: isValid,
                isPotentiallyValid: isPotentiallyValid,
                isExpired: isExpired,
                isValidMonth: isValidMonth,
                isValidYear: isValidYear,
                year: year,
                month: month
            };
        }

        var currentDate = new Date ();
        var currentMonth = currentDate.getMonth() + 1;
        var currentYear = currentDate.getFullYear();
        function checkDataExpiration(year, month) {
            year = parseInt(year);
            month = parseInt(month);
            return year > currentYear || (year == currentYear && month >= currentMonth)
        }

        return function(value) {
            var date,
                monthValid,
                yearValid;

            if (utils.isEmpty(value)) {
                return resultWrapper(false, false, null, null);
            }

            value = value.replace(/^(\d\d) (\d\d(\d\d)?)$/, '$1/$2');
            date = parseDate(value);
            monthValid = expirationMonth(date.month);
            yearValid = expirationYear(date.year);

            if (monthValid.isValid && yearValid.isValid && checkDataExpiration(date.year, date.month)) {
                return resultWrapper(true, true, false, date.month, date.year, true, true);
            }

            if (monthValid.isValid && yearValid.isValid) {
                return resultWrapper(false, false, true, date.month, date.year, true, true);
            }

            if (monthValid.isPotentiallyValid && yearValid.isPotentiallyValid) {
                return resultWrapper(false, true, false, date.month, date.year, monthValid.isValid, yearValid.isValid);
            }

            return resultWrapper(false, false, false, null, null, false, false);
        }
    }
);
