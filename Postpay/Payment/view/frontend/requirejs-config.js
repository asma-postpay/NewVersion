/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
var config = {
    map: {
        '*': {
            postpayUi: 'Postpay_Payment/js/view/ui'
        }
    },
    shim: {
        'postpay-js' : {
            'exports': 'postpay'
        }
    },
    paths: {
        'postpay-js': 'https://cdn.postpay.io/v1/js/postpay'
    }
};
