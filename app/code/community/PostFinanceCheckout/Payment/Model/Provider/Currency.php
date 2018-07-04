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
 * Provider of currency information from the gateway.
 */
class PostFinanceCheckout_Payment_Model_Provider_Currency extends PostFinanceCheckout_Payment_Model_Provider_Abstract
{

    public function __construct()
    {
        parent::__construct('postfinancecheckout_payment_currencies');
    }

    /**
     * Returns the currency by the given code.
     *
     * @param string $code
     * @return \PostFinanceCheckout\Sdk\Model\RestCurrency
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of currencies.
     *
     * @return \PostFinanceCheckout\Sdk\Model\RestCurrency[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $currencyService = new \PostFinanceCheckout\Sdk\Service\CurrencyService(Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $currencyService->all();
    }

    protected function getId($entry)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\RestCurrency $entry */
        return $entry->getCurrencyCode();
    }
}