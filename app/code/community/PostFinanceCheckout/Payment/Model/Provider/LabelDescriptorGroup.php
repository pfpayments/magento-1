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
 * Provider of label descriptor group information from the gateway.
 */
class PostFinanceCheckout_Payment_Model_Provider_LabelDescriptorGroup extends PostFinanceCheckout_Payment_Model_Provider_Abstract
{

    public function __construct()
    {
        parent::__construct('postfinancecheckout_payment_label_descriptor_group');
    }

    /**
     * Returns the label descriptor group by the given code.
     *
     * @param int $id
     * @return \PostFinanceCheckout\Sdk\Model\LabelDescriptorGroup
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of label descriptor groups.
     *
     * @return \PostFinanceCheckout\Sdk\Model\LabelDescriptorGroup[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorGroupService = new \PostFinanceCheckout\Sdk\Service\LabelDescriptionGroupService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $labelDescriptorGroupService->all();
    }

    protected function getId($entry)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\LabelDescriptorGroup $entry */
        return $entry->getId();
    }
}