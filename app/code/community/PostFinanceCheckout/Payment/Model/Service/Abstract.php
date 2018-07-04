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
 * Abstract service providing shared methods.
 */
class PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * Returns the data helper.
     *
     * @return PostFinanceCheckout_Payment_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('postfinancecheckout_payment');
    }

    /**
     * Returns the fraction digits for the given currency.
     *
     * @param string $currencyCode
     * @return number
     */
    protected function getCurrencyFractionDigits($currencyCode)
    {
        return $this->getHelper()->getCurrencyFractionDigits($currencyCode);
    }

    /**
     * Rounds the given amount to the currency's format.
     *
     * @param float $amount
     * @param string $currencyCode
     * @return number
     */
    protected function roundAmount($amount, $currencyCode)
    {
        return round($amount, $this->getCurrencyFractionDigits($currencyCode));
    }

    /**
     * Creates and returns a new entity filter.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $operator
     * @return \PostFinanceCheckout\Sdk\Model\EntityQueryFilter
     */
    protected function createEntityFilter($fieldName, $value, $operator = \PostFinanceCheckout\Sdk\Model\CriteriaOperator::EQUALS)
    {
        $filter = new \PostFinanceCheckout\Sdk\Model\EntityQueryFilter();
        $filter->setType(\PostFinanceCheckout\Sdk\Model\EntityQueryFilterType::LEAF);
        $filter->setOperator($operator);
        $filter->setFieldName($fieldName);
        $filter->setValue($value);
        return $filter;
    }

    /**
     * Creates and returns a new entity order by.
     *
     * @param string $fieldName
     * @param string $sortOrder
     * @return \PostFinanceCheckout\Sdk\Model\EntityQueryOrderBy
     */
    protected function createEntityOrderBy($fieldName, $sortOrder = \PostFinanceCheckout\Sdk\Model\EntityQueryOrderByType::DESC)
    {
        $orderBy = new \PostFinanceCheckout\Sdk\Model\EntityQueryOrderBy();
        $orderBy->setFieldName($fieldName);
        $orderBy->setSorting($sortOrder);
        return $orderBy;
    }

    /**
     * Changes the given string to have no more characters as specified.
     *
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    protected function fixLength($string, $maxLength)
    {
        return mb_substr($string, 0, $maxLength, 'UTF-8');
    }
    
    /**
     * Removes all line breaks in the given string and replaces them with a whitespace character.
     * 
     * @param string $string
     * @return string
     */
    protected function removeLinebreaks($string)
    {
        return preg_replace("/\r|\n/", ' ', $string);
    }
    
    /**
     * Returns the first line of the given string only.
     * 
     * @param string $string
     * @return string
     */
    protected function getFirstLine($string)
    {
        return rtrim(strtok($string, "\n"));
    }
    
}