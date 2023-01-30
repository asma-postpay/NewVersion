<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Method;

use Postpay\Payment\Gateway\Config\PayNowConfig;

/**
 * Postpay pay now method.
 */
class PayNow extends AbstractPostpayMethod
{
    /**
     * Number of instalments
     */
    const NUM_INSTALMENTS = 1;

    /**
     * @var string
     */
    protected $_code = PayNowConfig::CODE;
}
