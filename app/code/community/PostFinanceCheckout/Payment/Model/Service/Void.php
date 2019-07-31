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
 * This service provides functions to deal with PostFinance Checkout transaction voids.
 */
class PostFinanceCheckout_Payment_Model_Service_Void extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The transaction void API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\TransactionVoidService
     */
    protected $_transactionVoidService;

    /**
     * Void the transaction of the given payment.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \PostFinanceCheckout\Sdk\Model\TransactionVoid
     */
    public function void(Mage_Sales_Model_Order_Payment $payment)
    {
        return $this->getTransactionVoidService()->voidOnline(
            $payment->getOrder()
                ->getPostfinancecheckoutSpaceId(), $payment->getOrder()
                ->getPostfinancecheckoutTransactionId());
    }

    /**
     * Returns the transaction void API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\TransactionVoidService
     */
    protected function getTransactionVoidService()
    {
        if ($this->_transactionVoidService == null) {
            $this->_transactionVoidService = new \PostFinanceCheckout\Sdk\Service\TransactionVoidService(
                Mage::helper('postfinancecheckout_payment')->getApiClient());
        }

        return $this->_transactionVoidService;
    }
}