<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Block\Widget;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Postpay\Payment\Gateway\Config\Config;
use Postpay\Payment\Model\Adapter\ApiAdapter;

/**
 * Cart widget block.
 */
class Cart extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    private Resolver $localResolver;

    /**
     * @param Context $context
     * @param Config $config
     * @param CustomerCart $cart
     * @param PriceCurrencyInterface $priceCurrency ,
     * @param Resolver $localResolver
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        CustomerCart $cart,
        PriceCurrencyInterface $priceCurrency,
        Resolver $localResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->config = $config;
        $this->cart = $cart;
        $this->priceCurrency = $priceCurrency;
        $this->localResolver = $localResolver;
    }

    /**
     * Get currency code.
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }

    /**
     * Get cart subtotal.
     *
     * @return int
     */
    public function getSubtotal()
    {
        $totals = $this->cart->getQuote()->getTotals();
        return ApiAdapter::decimal($totals['subtotal']['value']);
    }

    /**
     * @inheritdoc
     */
    public function _toHtml()
    {
        if ($this->config->isActive() && $this->config->isAvailable()) {
            return parent::_toHtml();
        }
        return '';
    }
    public function getCurrentLocale(){
        $currentLocaleCode= $this->localResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_',true);
        if(!$languageCode){
            return 'en';
        }
        return $languageCode;
    }
}
