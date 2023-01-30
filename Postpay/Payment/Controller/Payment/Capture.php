<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Controller\Payment;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartManagementInterface;
use Postpay\Exceptions\ApiException;
use Postpay\Payment\Model\Method\AbstractPostpayMethod;

/**
 * Order capture controller.
 */
class Capture extends Action
{
    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Data
     */
    private $checkoutHelper;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CartManagementInterface $quoteManagement
     * @param CustomerSession $customerSession
     * @param Session $checkoutSession
     * @param Data $checkoutHelper
     */
    public function __construct(
        Context $context,
        CartManagementInterface $quoteManagement,
        CustomerSession $customerSession,
        Session $checkoutSession,
        Data $checkoutHelper
    ) {
        parent::__construct($context);
        $this->quoteManagement = $quoteManagement;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();
        $id = $payment->getAdditionalInformation(AbstractPostpayMethod::TRANSACTION_ID_KEY);
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($id) {
            if (!$this->customerSession->isLoggedIn() &&
                $this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            }

            try {
                $this->quoteManagement->placeOrder($quote->getId());
                return $resultRedirect->setPath('checkout/onepage/success');
            } catch (ApiException $e) {
                $this->messageManager->addErrorMessage(
                    __('Capture error. Id %1. Code: %2.', $id, $e->getErrorCode())
                );
            }
        }
        return $resultRedirect->setPath('checkout/cart');
    }
}
