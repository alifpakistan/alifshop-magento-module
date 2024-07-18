define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Catalog/js/price-utils'
    ],
    function (Component, priceUtils) {
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
                const minOrderTotal = window.checkoutConfig[this.item.method].min_order_total
                const priceFormat = window.checkoutConfig.priceFormat;

                return priceUtils.formatPrice(minOrderTotal, priceFormat);
            },
            getIsMinOrderValue: function () {
                const minOrderTotal = window.checkoutConfig[this.item.method].min_order_total
                const cartTotal = window.checkoutConfig.totalsData.base_subtotal

                if(!minOrderTotal) return true;

                return (cartTotal > minOrderTotal) ? true : false;
            },
            getIsDiscountApplied: function () {
                let isApplied = false;
                if(
                    window.checkoutConfig.totalsData.coupon_code !== null ||
                    window.checkoutConfig.totalsData.discount_amount > 0
                ) {
                    isApplied = true;
                }

                return isApplied
            },
            getIconHtml: function () {
                var iconPath = 'AlifShop_AlifShop/images/alif-shop.svg';
                return '<img src="' + require.toUrl(iconPath) + '" alt="' + this.getTitle() + '">';
            }
        });
    }
);