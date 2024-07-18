define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'alifshop',
                component: 'AlifShop_AlifShop/js/view/payment/method-renderer/alifshop-method'
            }
        );
        return Component.extend({});
    }
);