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
 * Resource collection of token info.
 */
class PostFinanceCheckout_Payment_Model_Resource_TokenInfo_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('postfinancecheckout_payment/entity_tokenInfo');
    }

    /**
     * Filters the collection by space.
     *
     * @param int $spaceId
     * @return PostFinanceCheckout_Payment_Model_Resource_TokenInfo_Collection
     */
    public function addSpaceFilter($spaceId)
    {
        $this->addFieldToFilter('main_table.space_id', $spaceId);
        return $this;
    }

    /**
     * Filters the collection by customer.
     *
     * @param int $customerId
     * @return PostFinanceCheckout_Payment_Model_Resource_TokenInfo_Collection
     */
    public function addCustomerFilter($customerId)
    {
        $this->addFieldToFilter('main_table.customer_id', $customerId);
        return $this;
    }

    /**
     * Filters the collection by payment method configuration.
     *
     * @param int $paymentMethodConfigurationId
     * @return PostFinanceCheckout_Payment_Model_Resource_TokenInfo_Collection
     */
    public function addPaymentMethodConfigurationFilter($paymentMethodConfigurationId)
    {
        $this->addFieldToFilter('main_table.payment_method_id', $paymentMethodConfigurationId);
        return $this;
    }
}