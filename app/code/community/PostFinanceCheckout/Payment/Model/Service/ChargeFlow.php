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
 * This service provides functions to deal with PostFinance Checkout charge flows.
 */
class PostFinanceCheckout_Payment_Model_Service_ChargeFlow extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The charge flow API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\ChargeFlowService
     */
    protected $_chargeFlowService;

    /**
     * Apply a charge flow to the given transaction.
     *
     * @param \PostFinanceCheckout\Sdk\Model\Transaction $transaction
     */
    public function applyFlow(\PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        $this->getChargeFlowService()->applyFlow($transaction->getLinkedSpaceId(), $transaction->getId());
    }

    /**
     * Returns the charge flow API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\ChargeFlowService
     */
    protected function getChargeFlowService()
    {
        if ($this->_chargeFlowService == null) {
            $this->_chargeFlowService = new \PostFinanceCheckout\Sdk\Service\ChargeFlowService(
                $this->getHelper()->getApiClient());
        }

        return $this->_chargeFlowService;
    }
}