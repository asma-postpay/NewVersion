/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'postpay',
            component: 'Postpay_Payment/js/view/payment/method-renderer/postpay-method'
        },
        {
            type: 'postpay_pay_now',
            component: 'Postpay_Payment/js/view/payment/method-renderer/postpay-method'
        }
    );
    return Component.extend({});
});
