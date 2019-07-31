<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout/).
 *
 * @package PostFinanceCheckout_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * Abstract webhook processor.
 */
abstract class PostFinanceCheckout_Payment_Model_Webhook_Abstract
{

    /**
     * Listens for an event call.
     *
     * @param Varien_Event_Observer $observer
     */
    public function listen(Varien_Event_Observer $observer)
    {
        $this->process($observer->getRequest());
    }

    /**
     * Processes the received webhook request.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Request $request
     */
    abstract protected function process(PostFinanceCheckout_Payment_Model_Webhook_Request $request);
}