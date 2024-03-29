<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html/).
 *
 * @package PostFinanceCheckout_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * This block renders an information bar in the store's backend to signalize if there are open manual tasks.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_Notification extends Mage_Adminhtml_Block_Template
{

    /**
     * Returns whether output is enabled for the admin notification module.
     *
     * @return boolean
     */
    public function isAdminNotificationEnabled()
    {
        if (! $this->isOutputEnabled('Mage_AdminNotification')) {
            return false;
        }

        return true;
    }

    /**
     * Returns the URL to check the open manual tasks.
     *
     * @return string
     */
    public function getManualTasksUrl($websiteId = null)
    {
        $manualTaskUrl = Mage::helper('postfinancecheckout_payment')->getBaseGatewayUrl();
        if ($websiteId != null) {
            $spaceId = Mage::app()->getWebsite($websiteId)->getConfig('postfinancecheckout_payment/general/space_id');
            $manualTaskUrl .= '/s/' . $spaceId . '/manual-task/list';
        }

        return $manualTaskUrl;
    }

    /**
     * Returns the number of open manual tasks.
     *
     * @return number
     */
    public function getNumberOfManualTasks()
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_ManualTask $manualTaskService */
        $manualTaskService = Mage::getSingleton('postfinancecheckout_payment/service_manualTask');
        return $manualTaskService->getNumberOfManualTasks();
    }
}