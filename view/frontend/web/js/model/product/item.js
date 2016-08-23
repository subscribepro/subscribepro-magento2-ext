define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, ko, priceUtils) {
        'use strict';
        
        return {
            create: function (productData, priceFormat) {
                var product = {};

                product.intervals = ko.observableArray(productData.intervals);
                product.name = ko.observable(productData.name);
                product.sku = ko.observable(productData.sku);
                product.url = ko.observable(productData.url);
                product.imageUrl = ko.observable(productData.image_url);
                product.discount = ko.observable(productData.discount);
                product.isDiscountPercentage = ko.observable(productData.is_discount_percentage);
                product.price = ko.observable(productData.price);
                product.finalPrice = ko.observable(productData.final_price);
                product.taxRate = ko.observable(productData.tax_rate);
                product.discountTax = ko.observable(productData.discount_tax);
                product.priceIncludesTax = ko.observable(productData.price_includes_tax);
                product.displayPriceIncludingTax = ko.observable(productData.display_price_including_tax);
                product.needPriceConversion = ko.observable(productData.need_price_conversion);
                product.applyTaxAfterDiscount = ko.observable(productData.apply_tax_after_discount);
                product.applyDiscountToCatalogPrice = ko.observable(productData.apply_discount_to_catalog_price);
                product.minQty = ko.observable(productData.min_qty);
                product.maxQty = ko.observable(productData.max_qty);
                product.defaultSubscriptionOption = ko.observable(productData.default_subscription_option);
                product.defaultInterval = ko.observable(productData.default_interval);
                product.subscriptionOptionMode = ko.observable(productData.subscription_option_mode);

                product.priceWithDiscountText = ko.pureComputed(function() {
                    var discount = product.discountValue();
                    var price = parseFloat(product.priceToDisplay()) - discount;
                    if (product.taxRate() && product.applyTaxAfterDiscount() && product.displayPriceIncludingTax() && !product.priceIncludesTax()) {
                        price = (1 + product.taxRate()/100)*(product.priceExclTax() - discount);
                    }
                    var priceText = getFormattedPrice(price);
                    if (!product.applyDiscountToCatalogPrice() && product.hasSpecialPrice()) {
                        return getFormattedPrice(product.priceToDisplay());
                    }
                    if (price < discount) {
                        return getFormattedPrice(product.priceToDisplay());
                    }
                    if (discount <= 0) {
                        return priceText;
                    }
                    if (product.needPriceConversion() && !product.discountTax() && product.displayPriceIncludingTax()) {
                        return priceText + ' (incl. tax).';
                    }

                    return priceText + ' with ' + product.discountText() + ' subscription discount';
                });

                product.priceWithDiscount = ko.pureComputed(function() {
                    return getFormattedPrice(parseFloat(product.priceToDisplay()) - product.discountValue());
                });

                product.discountValue = ko.pureComputed(function() {
                    var discount = parseFloat(product.discount());
                    if (product.isDiscountPercentage()) {
                        var price = product.discountTax() ? product.priceInclTax() : product.priceExclTax();
                        discount = parseFloat(price) * discount;
                    }
                    
                    return discount;
                });

                product.formattedPrice = ko.pureComputed(function() {
                    return getFormattedPrice(product.priceToDisplay());
                });

                product.priceToDisplay = ko.pureComputed(function() {
                    return product.displayPriceIncludingTax() ? product.priceInclTax() : product.priceExclTax();
                });

                product.priceInclTax = ko.pureComputed(function() {
                    var price = product.finalPrice();
                    if (!product.taxRate() || product.priceIncludesTax()) {
                        return price;
                    }

                    var tax = parseFloat(product.taxRate())/100 * price;
                    return price + tax;
                });

                product.priceExclTax = ko.pureComputed(function() {
                    var price = product.finalPrice();
                    if (product.taxRate() && product.priceIncludesTax()) {
                        price = parseFloat(price)/(1 + parseFloat(product.taxRate())/100);
                    }

                    return price.toFixed(2);
                });

                product.hasSpecialPrice = ko.pureComputed(function() {
                    return product.price() != product.finalPrice();
                });

                product.discountText = ko.pureComputed(function() {
                    return product.isDiscountPercentage()
                        ? parseFloat((100*parseFloat(product.discount())).toFixed(2)) + '%'
                        : getFormattedPrice(product.discount());
                });

                product.isSubscriptionMode = function(optionMode) {
                    return product.subscriptionOptionMode() == optionMode;
                };

                product.isSubscriptionOption = function(optionValue) {
                    return product.defaultSubscriptionOption() == optionValue;
                };

                product.getQtyValues = function() {
                    var values = [];
                    for (var i = product.minQty(); i <= product.maxQty(); i++) {
                        values.push(i);
                    }

                    return values;
                };

                product.setCalculatedPrices = function(basePrice, finalPrice) {
                    product.price(getBasePriceFromCalculatedPrice(basePrice));
                    product.finalPrice(getBasePriceFromCalculatedPrice(finalPrice));
                };

                function getFormattedPrice(price) {
                    return priceUtils.formatPrice(price, priceFormat);
                }

                function getBasePriceFromCalculatedPrice(price) {
                    if (!product.taxRate()) {
                        return price;
                    }

                    if (product.displayPriceIncludingTax() && !product.priceIncludesTax()) {
                        return price/(1 + product.taxRate()/100);
                    }
                    if (!product.displayPriceIncludingTax() && product.priceIncludesTax()) {
                        return price*(1 + product.taxRate()/100);
                    }

                    return price;
                }

                return product;
            }    
        };
    }
);
