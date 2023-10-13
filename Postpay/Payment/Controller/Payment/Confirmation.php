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
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory as DBTransaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;

/**
 * Order capture controller.
 */
class Confirmation extends Action
{
    private Http $request;
    private AdapterInterface $postpayAdapter;
    private Order $order;
    private Config $config;
    private OrderSender $orderSender;
    private InvoiceService $invoiceService;
    private DBTransaction $transactionFactory;
    private InvoiceSender $invoiceSender;
    private Builder $transactionBuilder;


    /**
     * Constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param AdapterInterface $postpayAdapter
     * @param Order $order
     * @param Config $config
     * @param OrderSender $orderSender
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param DBTransaction $transactionFactory
     * @param Builder $transactionBuilder
     */
    public function __construct(
        Context          $context,
        Http             $request,
        AdapterInterface $postpayAdapter,
        Order            $order,
        Config           $config,
        OrderSender      $orderSender,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        DBTransaction $transactionFactory,
        Builder $transactionBuilder
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->postpayAdapter = $postpayAdapter;
        $this->order = $order;
        $this->config = $config;
        $this->orderSender = $orderSender;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $postpayOrderId = $this->request->getParam('order_id');
        $orderId = explode("-", $postpayOrderId)[0];
        $order = $this->order->loadByIncrementId($orderId);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderStatus = 'failure';
        $redirect = 'checkout/cart';

        $status = $this->postpayAdapter->getSingleOrder($postpayOrderId)['status']  ;

        if ($status === 'approved' || $status === 'captured') {
            try {
                $response = $this->postpayAdapter->capture($postpayOrderId);
                $amount = (new Decimal($response['total_amount']))->toFloat();
                $comment = 'Captured amount of ' . $amount. ' '. $response['currency'] . ' Transaction ID : . '.$postpayOrderId;
                $this->changeOrderStatus($order, 'success', $comment);
                $paymentData = [
                'id' => $postpayOrderId,
                'Status' => $response['status'],
                'Amount' => $amount];
                $this->createTransaction($order,$paymentData);
                $this->messageManager->addSuccessMessage(
                    __('Your order Id %1. was created', $orderId)
                );
                return $resultRedirect->setPath('checkout/onepage/success');
            } catch (RESTfulException $exception) {
                $this->messageManager->addErrorMessage(
                    __('Capture error. Id %1. Code: %2.', $orderId, $exception->getErrorCode())
                );
                $this->changeOrderStatus($order, $orderStatus, $exception->getErrorCode());
                return $resultRedirect->setPath($redirect);
            }
        } else if ($status === 'pending') {
            $this->messageManager->addErrorMessage(
                __('Order is created but payment is pending. Id %1. Code: %2.', $order->getIncrementId(), 'Pending Payment')
            );
            return $resultRedirect->setPath('checkout/onepage/success');
        } else {
            $comment = 'Denied Transaction' ;
            $this->changeOrderStatus($order, $orderStatus, $comment);
            return $resultRedirect->setPath($redirect);
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function sendEmail(Order $order)
    {
        try {
            $sent = $this->orderSender->send($order);
            if ($sent) {
                $order->addCommentToStatusHistory('Email sent to the customer');
            } else {
                $this->messageManager->addErrorMessage(
                    __('Unable to Send Order Confirmation Email for order . Id %1.', $order->getIncrementId())
                );
                $order->addCommentToStatusHistory('Unable to Send Order Confirmation Email for order');
            }
            $order->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('Error sending confirmation email, exception message: '.$e->getMessage(), false);
            $order->save();
        }
    }

       /**
         * @param Order $order
         * @return void
         */
    public function createInvoice(Order $order)
        {
            try {

                if (!$order || !$order->getId() || !$order->canInvoice() || !$order->getPayment()) {
                    return;
                }

                $invoice = $this->invoiceService->prepareInvoice($order);
                if (!$invoice || !$invoice->getTotalQty()) {
                    return;
                }
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $order->addStatusHistoryComment('Automatically INVOICED', false);
                $transactionSave = $this->transactionFactory
                    ->create()
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            } catch (\Exception $e) {
                $order->addStatusHistoryComment('Error creating the invoice, exception message: '.$e->getMessage(), false);
                $order->save();
            }
        }

        /**
         * @param Order $order
         * @return void
         */
        public function createTransaction(Order $order, $paymentData = array())
            {
                try {
                    /** @var \Magento\Quote\Model\Quote\Payment $payment */
                    $payment = $order->getPayment();
                    $payment->setLastTransId($paymentData['id']);
                    $payment->setTransactionId($paymentData['id']);
                    $payment->setAdditionalInformation(
                        [Transaction::RAW_DETAILS => (array) $paymentData]
                    );
                    $formatedPrice = $order->getBaseCurrency()->formatTxt(
                        $order->getGrandTotal()
                    );

                    $message = __('The authorized amount is %1.', $formatedPrice);
                    $trans = $this->transactionBuilder;
                    $transaction = $trans->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($paymentData['id'])
                    ->setAdditionalInformation(
                        [Transaction::RAW_DETAILS => (array) $paymentData]
                    )
                    ->setFailSafe(true)
                    ->build(Transaction::TYPE_CAPTURE);

                    $payment->addTransactionCommentsToOrder(
                        $transaction,
                        $message
                    );
                    $payment->setParentTransactionId($paymentData['id']);
                    $payment->save();
                    $order->save();

                    return  $transaction->save()->getTransactionId();
                } catch (Exception $e) {
                     $order->addStatusHistoryComment('Error Saving the transaction, exception message: '.$e->getMessage(), false);
                     $order->save();
                }
            }


    /**
     * @param Order $order
     * @param $status
     * @return void
     */
    public function changeOrderStatus(Order $order, $status, $comment)
    {
        try {
            if ($status === 'success') {
                $successStatus = $this->config->getCheckoutSuccessStatus();
                $order->setState($successStatus);
                $order->setStatus($successStatus);
                $order->addCommentToStatusHistory($comment);
                $order->save();
                $this->sendEmail($order);
                $this->createInvoice($order);
                return;
            }
            $failureStatus = $this->config->getCheckoutFailureStatus();
            if ($failureStatus === Order::STATE_CANCELED) {
                $order->addCommentToStatusHistory('Canceled Order, due to: '.$comment);
                $order->cancel()->save();
                return;
            }
            $order->setState($failureStatus);
            $order->setStatus($failureStatus);
            $order->addCommentToStatusHistory($comment);
            $order->save();
            return;
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                __('Unable to change the status of the order. Id %1. Code: %2.', $order->getIncrementId(), $exception->getErrorCode())
            );
        }
    }
}
