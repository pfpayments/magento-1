<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/).
 *
 * @package PostFinanceCheckout_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_PaymentMethodConfiguration extends PostFinanceCheckout_Payment_Model_Webhook_Abstract
{

    /**
     * Synchronizes the payment method configurations on state transition.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Request $request
     */
    protected function process(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_PaymentMethodConfiguration $paymentMethodConfigurationService */
        $paymentMethodConfigurationService = Mage::getSingleton(
            'postfinancecheckout_payment/service_paymentMethodConfiguration');
        $paymentMethodConfigurationService->synchronize();
    }
}