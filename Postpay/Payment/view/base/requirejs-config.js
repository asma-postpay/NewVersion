/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
var config = {
config:
    {
        mixins:
        {
            'Magento_Catalog/js/price-box': {
                               'Postpay_Payment/js/view/payment/price/price-box-mixin': true
                           }
        }
    },
    shim: {
           'postpay-js' : {
                        'exports': 'postpay'
                    }
                },
          map: {
                '*': {
                    postpayUi: 'Postpay_Payment/js/view/ui',
                }
            },
            paths: {
                'postpay-js': 'https://cdn.postpay.io/v1/js/postpay'
         }
};
