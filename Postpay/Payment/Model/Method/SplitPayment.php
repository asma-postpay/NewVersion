<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Method;

use Postpay\Payment\Gateway\Config\Config;

/**
 * Postpay split payment method.
 */
class SplitPayment extends AbstractPostpayMethod
{
    /**
     * @var string
     */
    protected $_code = Config::CODE;
}
