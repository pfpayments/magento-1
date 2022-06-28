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
 * Webhook processor to handle manual task state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_ManualTask extends PostFinanceCheckout_Payment_Model_Webhook_Abstract
{

    /**
     * Updates the number of open manual tasks.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Request $request
     */
    protected function process(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_ManualTask $manualTaskService */
        $manualTaskService = Mage::getSingleton('postfinancecheckout_payment/service_manualTask');
        $manualTaskService->update();
    }
}