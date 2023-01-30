<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */

namespace Postpay\Payment\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Postpay\Exceptions\ApiException;
use Postpay\Payment\Model\Adapter\AdapterInterface;
use Postpay\Payment\Model\Method\AbstractPostpayMethod;
use Postpay\Payment\Model\Request\Checkout as CheckoutRequest;

/**
 * Create a Postpay checkout.
 */
class Checkout extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var AdapterInterface
     */
    private $postpayAdapter;
    private \Magento\Sales\Model\OrderFactory $orderFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param AdapterInterface $postpayAdapter
     */
    public function __construct(
        Context                           $context,
        Session                           $checkoutSession,
        CartRepositoryInterface           $quoteRepository,
        AdapterInterface                  $postpayAdapter,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->postpayAdapter = $postpayAdapter;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $order = $this->getOrder();
        $quoteId = $order->getQuoteId();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);;
        $id = $order->getIncrementId() . '-' . uniqid();
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($quote->getBillingAddress()) {

            try {
                $response = $this->postpayAdapter->checkout(
                    CheckoutRequest::build($quote, $id, $payment->getMethodInstance())
                );
                return $resultRedirect->setPath($response['redirect_url']);
            } catch (ApiException $e) {
                $payment->setAdditionalInformation(AbstractPostpayMethod::TRANSACTION_ID_KEY, $id);
                $this->messageManager->addErrorMessage(
                    __('ْUnable to Redirect to Postpay')
                );
                return $resultRedirect->setPath('checkout/cart');
            }
        } else {
            $payment->setAdditionalInformation(AbstractPostpayMethod::TRANSACTION_ID_KEY, $id);
            $this->messageManager->addErrorMessage(
                __('No Billing Address Found!')
            );
            return $resultRedirect->setPath('checkout/cart');
        }
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );
    }
}
