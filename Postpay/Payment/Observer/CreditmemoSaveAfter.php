<?php

namespace Postpay\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Sales\Model\Order\Creditmemo;
use Postpay\Payment\Model\Adapter\AdapterInterface;

/**
 *
 */
class CreditmemoSaveAfter implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;
    private AdapterInterface $postpayAdapter;

    public const POSTAPY  =  ['postpay','postpay_pay_now'];


    /**
     * @param AdapterInterface $postpayAdapter
     * @param Logger $logger
     */
    public function __construct(
        AdapterInterface $postpayAdapter,
        Logger                         $logger,

    )
    {
        $this->logger = $logger;

        $this->postpayAdapter = $postpayAdapter;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $this->logger->debug('Postpay - Start creditmemo' );

        $creditmemo = $observer->getEvent()->getCreditmemo();

        /** @var Creditmemo $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();
        if (!$creditmemo->isObjectNew() && $creditmemo->getGrandTotal() < 0) {
            return;
        }
        $order = $creditMemo->getOrder();
        $payment = $order->getPayment();
        if ($payment === null) {
            return;
        }
        if (!$this->isPostpay($payment->getMethod())){
            return;
        }
        $id = $payment->getLastTransId();
        $refundId = $payment->getOrder()->getIncrementId() . '-' . uniqid();
        try {
                $this->postpayAdapter->refund($id, $refundId, $creditMemo->getGrandTotal());
                $payment->getCreditmemo()->setRefundTransactionId($refundId);
                $payment->setTransactionId($refundId);
                $payment->setParentTransactionId($id);
                $payment->setIsTransactionClosed(true);
                $payment->save();
                $order->addCommentToStatusHistory('Postpay Refund API was called successfully - the amount refunded is ' . $creditMemo->getGrandTotal(). $creditMemo->getOrderCurrencyCode());
                $order->save();
                $this->logger->debug('Postpay - End of creditmemo');
                return $this;
        } catch (\Exception $e) {
            $this->logger->error('An error occurred during the postpay refund process: ' . $e->getMessage());
            $order->addCommentToStatusHistory('Error - Postpay Refund API could not be called successfully for the amount ' . $creditMemo->getGrandTotal(). $creditMemo->getOrderCurrencyCode());
            $order->save();
            throw new \Exception('Postpay refund failed. See logs for details.', 0, $e);
        }
    }

    /**
     * @param $method
     * @return bool
     */
    public  function isPostpay($method)
    {
        return in_array($method, self::POSTAPY, true);
    }
}
