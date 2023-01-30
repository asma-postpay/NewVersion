<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;

/**
 * Add cancelation message and redirect to cart page.
 */
class Cancel extends Action
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->messageManager->addSuccessMessage(
            __('Postpay checkout has been canceled.')
        );
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
