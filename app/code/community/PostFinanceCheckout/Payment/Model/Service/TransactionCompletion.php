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
 * This service provides functions to deal with PostFinance Checkout transaction completions.
 */
class PostFinanceCheckout_Payment_Model_Service_TransactionCompletion extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The transaction completion API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\TransactionCompletionService
     */
    protected $_transactionCompletionService;

    /**
     * Completes a transaction completion.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \PostFinanceCheckout\Sdk\Model\TransactionCompletion
     */
    public function complete(Mage_Sales_Model_Order_Payment $payment)
    {
        return $this->getTransactionCompletionService()->completeOnline(
            $payment->getOrder()
                ->getPostfinancecheckoutSpaceId(), $payment->getOrder()
                ->getPostfinancecheckoutTransactionId());
    }

    /**
     * Returns the transaction completion API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\TransactionCompletionService
     */
    protected function getTransactionCompletionService()
    {
        if ($this->_transactionCompletionService == null) {
            $this->_transactionCompletionService = new \PostFinanceCheckout\Sdk\Service\TransactionCompletionService(
                $this->getHelper()->getApiClient());
        }

        return $this->_transactionCompletionService;
    }
}