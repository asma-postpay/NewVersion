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
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Postpay\Exceptions\RESTfulException;
use Postpay\Payment\Gateway\Config\Config;
use Postpay\Payment\Model\Adapter\AdapterInterface;
use Postpay\Serializers\Decimal;

/**
 * Order capture controller.
 */
class Confirmation extends Action
{
    private Http $request;
    private AdapterInterface $postpayAdapter;
    private Order $order;
    private Config $config;

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
        Context          $context,
        Http             $request,
        AdapterInterface $postpayAdapter,
        Order            $order,
        Config           $config
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->postpayAdapter = $postpayAdapter;
        $this->order = $order;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $status = $this->request->getParam('status');
        $postpayOrderId = $this->request->getParam('order_id');
        $orderId = explode("-", $postpayOrderId)[0];
        $order = $this->order->loadByIncrementId($orderId);
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $order->getPayment();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderStatus = 'success';
        $redirect = 'checkout/onepage/success';

        if ($status === 'APPROVED') {
            try {
                $response = $this->postpayAdapter->capture($postpayOrderId);
                $payment->setTransactionId($orderId);
                $payment->setIsTransactionClosed(true);
                $payment->setTransactionAdditionalInfo(
                    Transaction::RAW_DETAILS,
                    [
                        'Status' => $response['status'],
                        'Amount' => (new Decimal($response['total_amount']))->toFloat()
                    ]
                );
                    $this->messageManager->addSuccessMessage(
                        __('Your order Id %1. was created', $orderId)
                    );
                    $orderStatus = 'success';
                    $redirect = 'checkout/onepage/success';

            } catch (RESTfulException $exception) {
                $this->messageManager->addErrorMessage(
                    __('Capture error. Id %1. Code: %2.', $orderId, $exception->getErrorCode())
                );
                $orderStatus = 'failure';
                $redirect = 'checkout/cart';
            }
        } else {

            $this->messageManager->addErrorMessage(
                __('Unable to proceed with Postpay payment gateway. Id %1. Code: %2.', $orderId, $status)
            );

            $orderStatus = 'failure';
            $redirect = 'checkout/cart';
        }

        $this->changeOrderStatus($order, $orderStatus);
        return $resultRedirect->setPath($redirect);
    }


    /**
     * @param Order $order
     * @param $status
     * @return Order|void
     */
    public function changeOrderStatus(Order $order, $status)
    {
        try {
            if ($status === 'success') {
                $successStatus = $this->config->getCheckoutSuccessStatus();
                $order->setState($successStatus);
                $order->setStatus($successStatus);
                $order->save();
                return $order;
            }
            $failureStatus = $this->config->getCheckoutFailureStatus();
            if ($failureStatus === Order::STATE_CANCELED) {
                $order->cancel()->save();
                return $order;
            }
            $order->setState($failureStatus);
            $order->setStatus($failureStatus);
            $order->save();
            return $order;
        } catch (\Exception $exception){
            $this->messageManager->addErrorMessage(
                __('Unable to save order. Id %1. Code: %2.', $order->getIncrementId(), $exception->getErrorCode())
            );
        }
    }
}
