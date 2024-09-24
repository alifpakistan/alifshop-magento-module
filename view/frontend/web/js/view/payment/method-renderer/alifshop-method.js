define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, Component, priceUtils, quote, urlBuilder, storage, errorProcessor, messageContainer, $t, getPaymentInformationAction, fullScreenLoader) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'AlifShop_AlifShop/payment/alifshop'
        },

        getMailingAddress: function () {
            return window.checkoutConfig.payment.checkmo.mailingAddress;
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        getMinOrderValue: function () {
            const minOrderTotal = window.checkoutConfig[this.item.method].min_order_total;
            const priceFormat = window.checkoutConfig.priceFormat;

            return priceUtils.formatPrice(minOrderTotal, priceFormat);
        },

        getIsMinOrderValue: function () {
            const minOrderTotal = window.checkoutConfig[this.item.method].min_order_total;
            const cartTotal = quote.getTotals()().base_subtotal;

            if (!minOrderTotal) return true;

            return (cartTotal > minOrderTotal);
        },

        getIsDiscountApplied: function () {
            var totals = quote.getTotals()();
            return (totals && (totals.coupon_code || totals.discount_amount !== 0)) || this.getHasSpecialPrice()
        },

        getDiscountAppliedErrMsg: function () {
            var totals = quote.getTotals()();
            return (totals.coupon_code)
                ? "Unable to apply a discount code for Pay In Instalments. Please try again."
                : "Unable to offer Pay In Instalments for discounted items. Please try again."
        },

        getHasSpecialPrice: function() {
            let hasSpecialPrice = false;
            $.ajax({
                url: '/alifshop/check/specialprice',
                type: 'GET',
                dataType: 'json',
                async: false,
                success: function (response) {
                    if (response.has_special_price) {
                        hasSpecialPrice = true;
                    }
                },
                error: function () {
                    console.error('Error checking for special price.');
                    return false;
                }
            });

            return hasSpecialPrice;
        },

        getIconHtml: function () {
            var iconPath = 'AlifShop_AlifShop/images/alif-shop-with-text.svg';
            return '<img width="150" src="' + require.toUrl(iconPath) + '" alt="' + this.getTitle() + '">';
        },

        applyCoupon: function (couponCode) {
            var quoteId = quote.getQuoteId(),
                url = urlBuilder.createUrl('/carts/' + quoteId + '/coupons/' + encodeURIComponent(couponCode), {}),
                message = $t('Your coupon was successfully applied.');

            fullScreenLoader.startLoader();

            return storage.put(
                url,
                {},
                false
            ).done(function (response) {
                if (response) {
                    var deferred = $.Deferred();

                    getPaymentInformationAction(deferred);
                    $.when(deferred).done(function () {
                        messageContainer.addSuccessMessage({
                            'message': message
                        });
                    });
                }
            }).fail(function (response) {
                errorProcessor.process(response, messageContainer);
            }).always(function () {
                fullScreenLoader.stopLoader();
            });
        },

        cancelCoupon: function () {
            var quoteId = quote.getQuoteId(),
                url = urlBuilder.createUrl('/carts/' + quoteId + '/coupons', {});

            fullScreenLoader.startLoader();

            return storage.delete(
                url,
                false
            ).done(function (response) {
                if (response) {
                    var deferred = $.Deferred();

                    getPaymentInformationAction(deferred);
                    $.when(deferred).done(function () {
                        messageContainer.addSuccessMessage({
                            'message': $t('Your coupon was successfully removed.')
                        });
                    });
                }
            }).fail(function (response) {
                errorProcessor.process(response, messageContainer);
            }).always(function () {
                fullScreenLoader.stopLoader();
            });
        }
    });
});