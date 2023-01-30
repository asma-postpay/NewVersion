<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Request;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Postpay\Payment\Model\Adapter\ApiAdapter;

/**
 * Add checkout information to checkout request.
 */
class Checkout
{
    /**
     * Build request.
     *
     * @param Quote $quote
     * @param string $id
     *
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function build(Quote $quote, $id, MethodInterface $method)
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();

        if ($quote->getCustomerId()) {
            $customer = Customer::build($quote->getCustomer());
        } else {
            $customer = Guest::build($billing);
        }

        return [
            'order_id' => $id,
            'total_amount' => ApiAdapter::decimal($quote->getBaseGrandTotal()),
            'tax_amount' => ApiAdapter::decimal($shipping->getBaseTaxAmount()),
            'currency' => $quote->getBaseCurrencyCode(),
            'shipping' => $quote->isVirtual() ? null : Shipping::build($shipping),
            'billing_address' => Address::build($billing),
            'customer' => $customer,
            'items' => array_map(
                'Postpay\Payment\Model\Request\Item::build',
                $quote->getAllVisibleItems()
            ),
            'merchant' => [
                'confirmation_url' => self::getUrl('postpay/payment/confirmation'),
                'cancel_url' => self::getUrl('postpay/payment/confirmation')
            ],
            'metadata' => Metadata::build($method),
            'num_instalments' => $method::NUM_INSTALMENTS
        ];
    }

    /**
     * Get absolute url.
     *
     * @param string $path
     *
     * @return string
     */
    public static function getUrl($path)
    {
        $objectManager = ObjectManager::getInstance();
        return $objectManager->get(UrlInterface::class)->getUrl($path);
    }
}
