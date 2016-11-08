define(
    [
        'ko',
        'Magento_Catalog/js/price-utils',
        'mage/translate'
    ],
    function (ko, priceUtils, $t) {
        'use strict';
        
        return {
            create: function (product, priceConfig) {
                var productPrice = {};

                var displayPriceIncludingTax = priceConfig.displayPriceIncludingTax || priceConfig.displayPriceBoth;

                productPrice.discount = product.discount;
                productPrice.is_discount_percentage = product.is_discount_percentage;

                productPrice.price = ko.observable(product.price);
                productPrice.hasSpecialPrice = ko.observable(product.is_catalog_rule_applied);

                productPrice.priceWithDiscountText = ko.pureComputed(function() {
                    var discount = productPrice.discountValue();
                    var price = parseFloat(productPrice.priceToDisplay()) - discount;
                    if (product.tax_rate && priceConfig.applyTaxAfterDiscount && displayPriceIncludingTax && !priceConfig.priceIncludesTax) {
                        price = (1 + product.tax_rate/100)*(productPrice.priceExclTax() - discount);
                    }
                    var priceText = getFormattedPrice(round(price));
                    if (!priceConfig.applyDiscountToCatalogPrice && productPrice.hasSpecialPrice()) {
                        return getFormattedPrice(productPrice.priceToDisplay());
                    }
                    if (price < discount) {
                        return getFormattedPrice(productPrice.priceToDisplay());
                    }
                    if (discount <= 0) {
                        return priceText;
                    }
                    if (!priceConfig.discountTax && displayPriceIncludingTax) {
                        priceText += ' ' + $t('(incl. tax)');
                    }

                    return priceConfig.discountMessage
                        .replace('%price', priceText)
                        .replace('%discount_amount', getFormattedPrice(discount))
                        .replace('%discount_text', productPrice.discountText());
                });

                productPrice.discountValue = ko.pureComputed(function() {
                    var discount = parseFloat(productPrice.discount);
                    if (productPrice.is_discount_percentage) {
                        var price = priceConfig.discountTax ? productPrice.priceInclTax() : productPrice.priceExclTax();
                        discount = parseFloat(price) * discount;
                    }
                    return round(discount);
                });

                productPrice.formattedPrice = ko.pureComputed(function() {
                    return getFormattedPrice(productPrice.priceToDisplay());
                });

                productPrice.priceToDisplay = ko.pureComputed(function() {
                    return displayPriceIncludingTax ? productPrice.priceInclTax() : productPrice.priceExclTax();
                });

                productPrice.priceInclTax = ko.pureComputed(function() {
                    var price = productPrice.price();
                    if (!product.tax_rate || priceConfig.priceIncludesTax) {
                        return price;
                    }

                    var tax = parseFloat(product.tax_rate)/100 * price;
                    return round(price + tax);
                });

                productPrice.priceExclTax = ko.pureComputed(function() {
                    var price = productPrice.price();
                    if (product.tax_rate && priceConfig.priceIncludesTax) {
                        price = parseFloat(price)/(1 + parseFloat(product.tax_rate)/100);
                    }

                    return round(price);
                });

                productPrice.discountText = ko.pureComputed(function() {
                    return productPrice.is_discount_percentage
                        ? round(100*parseFloat(productPrice.discount)) + '%'
                        : getFormattedPrice(productPrice.discount);
                });

                productPrice.setFrontendPrice = function(frontendPrice) {
                    productPrice.price(getPriceFromFrontendPrice(frontendPrice));
                };

                function getFormattedPrice(price) {
                    return priceUtils.formatPrice(price, priceConfig.priceFormat);
                }

                function round(num) {
                    return +(Math.round(num + 'e+2')  + 'e-2');
                }

                function getPriceFromFrontendPrice(price) {
                    if (!product.tax_rate) {
                        return price;
                    }

                    if (displayPriceIncludingTax && !priceConfig.priceIncludesTax) {
                        return price/(1 + product.tax_rate/100);
                    }
                    if (!displayPriceIncludingTax && priceConfig.priceIncludesTax) {
                        return price*(1 + product.tax_rate/100);
                    }

                    return price;
                }

                return productPrice;
            }    
        };
    }
);
