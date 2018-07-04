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
 * Webhook processor to handle token state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_Token extends PostFinanceCheckout_Payment_Model_Webhook_Abstract
{

    protected function process(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_Token $tokenService */
        $tokenService = Mage::getSingleton('postfinancecheckout_payment/service_token');
        $tokenService->updateToken($request->getSpaceId(), $request->getEntityId());
    }
}