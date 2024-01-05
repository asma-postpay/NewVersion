<?php

namespace Postpay\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Sales\Model\Order\Creditmemo;
use Postpay\Payment\Model\Adapter\AdapterInterface;

class CreditmemoSaveAfter implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;
    private AdapterInterface $postpayAdapter;

    public const POSTAPY  =  ['postpay','postpay_pay_now'];


    public function __construct(
        AdapterInterface $postpayAdapter,
        Logger                         $logger,

    )
    {
        $this->logger = $logger;

        $this->postpayAdapter = $postpayAdapter;
    }

    public function execute(Observer $observer)
    {
        $this->logger->debug('Start Credit Memo saved' );

        $creditmemo = $observer->getEvent()->getCreditmemo();
        $this->logger->debug('Credit Memo saved: ' . $creditmemo->getIncrementId());

        /** @var Creditmemo $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $payment = $order->getPayment();

        if ($payment === null) {
            return;
        }
        if (!$this->isPostpay($payment->getMethod())){
            return;
        }

        $id = $payment->getLastTransId();
        $this->logger->debug('Postpay - Credit Memo Parent Tranaction ID is :' .$id);
        $refundId = $payment->getOrder()->getIncrementId() . '-' . uniqid();
        $this->postpayAdapter->refund($id, $refundId, $creditMemo->getGrandTotal());
        $payment->setTransactionId($refundId);
        $payment->setParentTransactionId($id);
        $payment->setIsTransactionClosed(true);
        $this->logger->debug('Postpay - End to creditmemo');
        return $this;
    }

    public  function isPostpay($method)
    {
        return in_array($method, self::POSTAPY, true);
    }
}
