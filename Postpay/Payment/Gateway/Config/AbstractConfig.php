<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Gateway\Config;

/**
 * Configuration abstract class.
 */
abstract class AbstractConfig extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_SUMMARY_WIDGET = 'summary_widget';

    /**
     * Get payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * Check if payment summary widget is enabled.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function summaryWidgetEnabled($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_SUMMARY_WIDGET, $storeId);
    }
}
