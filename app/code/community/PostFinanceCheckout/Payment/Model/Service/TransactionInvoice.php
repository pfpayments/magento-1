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
 * This service provides functions to deal with PostFinance Checkout transaction invoices.
 */
class PostFinanceCheckout_Payment_Model_Service_TransactionInvoice extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The transaction invoice API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\TransactionInvoiceService
     */
    private $transactionInvoiceService;

    /**
     * Returns the transaction invoice for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \PostFinanceCheckout\Sdk\Model\TransactionInvoice
     */
    public function getTransactionInvoiceByTransaction($spaceId, $transactionId)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $query->setNumberOfEntities(1);
        $query->setFilter($this->createEntityFilter('completion.lineItemVersion.transaction.id', $transactionId));
        $result = $this->getTransactionInvoiceService()->search($spaceId, $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            Mage::throwException('The transaction invoice could not be found.');
        }
    }

    /**
     * Returns the transaction invoice for the given complication.
     *
     * @param int $spaceId
     * @param int $completionId
     * @return \PostFinanceCheckout\Sdk\Model\TransactionInvoice
     */
    public function getTransactionInvoiceByCompletion($spaceId, $completionId)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $query->setNumberOfEntities(1);
        $query->setFilter($this->createEntityFilter('completion.id', $completionId));
        $result = $this->getTransactionInvoiceService()->search($spaceId, $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            Mage::throwException('The transaction invoice could not be found.');
        }
    }

    /**
     * Returns whether it is possible to replace the transaction invoice.
     *
     * @param int $spaceId
     * @param int $invoiceId
     * @return boolean
     */
    public function isReplacementPossible($spaceId, $invoiceId)
    {
        return $this->getTransactionInvoiceService()->isReplacementPossible($spaceId, $invoiceId);
    }

    /**
     * Replaces the invoice with the new one.
     *
     * @param int $spaceId
     * @param int $invoiceId
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return \PostFinanceCheckout\Sdk\Model\TransactionInvoice
     */
    public function replace($spaceId, $invoiceId, Mage_Sales_Model_Order_Invoice $invoice)
    {
        $replacement = new \PostFinanceCheckout\Sdk\Model\TransactionInvoiceReplacement();
        $replacement->setMerchantReference($invoice->getIncrementId());
        $replacement->setExternalId($invoice->getIncrementId());
        /* @var PostFinanceCheckout_Payment_Model_Service_LineItem $lineItems */
        $lineItems = Mage::getSingleton('postfinancecheckout_payment/service_lineItem');
        $replacement->setLineItems($lineItems->collectInvoiceLineItems($invoice, $invoice->getGrandTotal()));
        return $this->getTransactionInvoiceService()->replace($spaceId, $invoiceId, $replacement);
    }

    /**
     * Returns the transaction invoice API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\TransactionInvoiceService
     */
    protected function getTransactionInvoiceService()
    {
        if ($this->transactionInvoiceService == null) {
            $this->transactionInvoiceService = new \PostFinanceCheckout\Sdk\Service\TransactionInvoiceService($this->getHelper()->getApiClient());
        }

        return $this->transactionInvoiceService;
    }
}