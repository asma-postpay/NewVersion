<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Request;

use Magento\Quote\Model\Quote\Address;

/**
 * Add guest user information to checkout request.
 */
class Guest
{
    /**
     * Build request.
     *
     * @param Address $address
     *
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function build(Address $address)
    {
        return [
            'email' => $address->getEmail(),
            'first_name' => $address->getFirstname() ?: '',
            'last_name' => $address->getLastname() ?: '',
            'account' => 'guest'
        ];
    }
}
