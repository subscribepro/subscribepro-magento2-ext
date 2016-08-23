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
                product.applyDiscountToCatalogPrice = ko.observable(productData.apply_discount_to_catalog_price);
                product.minQty = ko.observable(productData.min_qty);
                product.maxQty = ko.observable(productData.max_qty);
                product.defaultSubscriptionOption = ko.observable(productData.default_subscription_option);
                product.defaultInterval = ko.observable(productData.default_interval);
                product.subscriptionOptionMode = ko.observable(productData.subscription_option_mode);

                product.priceWithDiscountText = ko.pureComputed(function() {
                    if (!product.applyDiscountToCatalogPrice() && product.hasSpecialPrice()) {
                        return getFormattedPrice(product.finalPrice());
                    }
                    if (product.finalPrice() < product.discountValue()) {
                        return getFormattedPrice(product.finalPrice());
                    }

                    return product.priceWithDiscount() + ' with ' + product.discountText() + ' subscription discount';
                });

                product.priceWithDiscount = ko.pureComputed(function() {
                    return getFormattedPrice(parseFloat(product.finalPrice()) - product.discountValue());
                });

                product.discountValue = ko.pureComputed(function() {
                    var discount = parseFloat(product.discount());
                    if (product.isDiscountPercentage()) {
                        discount = parseFloat(product.finalPrice()) * discount;
                    }
                    
                    return discount;
                });

                product.formattedPrice = ko.pureComputed(function() {
                    return getFormattedPrice(product.finalPrice())
                });

                product.hasSpecialPrice = ko.pureComputed(function() {
                    return product.price() != product.finalPrice();
                });

                product.discountText = ko.pureComputed(function() {
                    return product.isDiscountPercentage()
                        ? 100*parseFloat(product.discount()) + '%'
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
                
                function getFormattedPrice(price) {
                    return priceUtils.formatPrice(price, priceFormat);
                }

                return product;
            }    
        };
    }
);
