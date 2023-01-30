/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'jquery',
    'postpay-js'
], function ($, postpay) {
    'use strict';

    var config = window.checkoutConfig;

    $.widget('mage.postpayUi', {
        options: config && config.payment.postpay.uiParams,

        /** @inheritdoc */
        _create: function () {
            postpay.init(this.options);
        }
    });

    return $.mage.postpayUi;
});
