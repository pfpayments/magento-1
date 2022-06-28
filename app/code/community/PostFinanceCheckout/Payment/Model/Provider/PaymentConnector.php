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
 * Provider of payment connector information from the gateway.
 */
class PostFinanceCheckout_Payment_Model_Provider_PaymentConnector extends PostFinanceCheckout_Payment_Model_Provider_Abstract
{

    public function __construct()
    {
        parent::__construct('postfinancecheckout_payment_connectors');
    }

    /**
     * Returns the payment connector by the given id.
     *
     * @param int $id
     * @return \PostFinanceCheckout\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment connectors.
     *
     * @return \PostFinanceCheckout\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $connectorService = new \PostFinanceCheckout\Sdk\Service\PaymentConnectorService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $connectorService->all();
    }

    protected function getId($entry)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}