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
 * Provider of language information from the gateway.
 */
class PostFinanceCheckout_Payment_Model_Provider_Language extends PostFinanceCheckout_Payment_Model_Provider_Abstract
{

    public function __construct()
    {
        parent::__construct('postfinancecheckout_payment_languages');
    }

    /**
     * Returns the language by the given code.
     *
     * @param string $code
     * @return \PostFinanceCheckout\Sdk\Model\RestLanguage
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns the primary language in the given group.
     *
     * @param string $code
     * @return \PostFinanceCheckout\Sdk\Model\RestLanguage
     */
    public function findPrimary($code)
    {
        $code = substr($code, 0, 2);
        foreach ($this->getAll() as $language) {
            if ($language->getIso2Code() == $code && $language->getPrimaryOfGroup()) {
                return $language;
            }
        }

        return false;
    }

    /**
     * Returns a list of language.
     *
     * @return \PostFinanceCheckout\Sdk\Model\RestLanguage[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $languageService = new \PostFinanceCheckout\Sdk\Service\LanguageService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $languageService->all();
    }

    protected function getId($entry)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\RestLanguage $entry */
        return $entry->getIetfCode();
    }
}