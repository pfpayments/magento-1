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
 * This service provides functions to deal with PostFinance Checkout tokens.
 */
class PostFinanceCheckout_Payment_Model_Service_Token extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The token API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\TokenService
     */
    protected $_tokenService;

    /**
     * The token version API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\TokenVersionService
     */
    protected $_tokenVersionService;

    public function updateTokenVersion($spaceId, $tokenVersionId)
    {
        $tokenVersion = $this->getTokenVersionService()->read($spaceId, $tokenVersionId);
        $this->updateInfo($spaceId, $tokenVersion);
    }

    public function updateToken($spaceId, $tokenId)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $filter = new \PostFinanceCheckout\Sdk\Model\EntityQueryFilter();
        $filter->setType(\PostFinanceCheckout\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('token.id', $tokenId),
                $this->createEntityFilter('state', \PostFinanceCheckout\Sdk\Model\TokenVersionState::ACTIVE)
            ));
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $tokenVersion = $this->getTokenVersionService()->search($spaceId, $query);
        if (! empty($tokenVersion)) {
            $this->updateInfo($spaceId, current($tokenVersion));
        } else {
            /* @var PostFinanceCheckout_Payment_Model_Entity_TokenInfo $info */
            $info = Mage::getModel('postfinancecheckout_payment/entity_tokenInfo')->loadByToken($spaceId, $tokenId);
            if ($info->getId()) {
                $info->delete();
            }
        }
    }

    protected function updateInfo($spaceId, \PostFinanceCheckout\Sdk\Model\TokenVersion $tokenVersion)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_TokenInfo $info */
        $info = Mage::getModel('postfinancecheckout_payment/entity_tokenInfo')->loadByToken($spaceId,
            $tokenVersion->getToken()
                ->getId());

        if (! in_array($tokenVersion->getToken()->getState(),
            array(
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::INACTIVE
            ))) {
            if ($info->getId()) {
                $info->delete();
            }

            return;
        }

        $info->setCustomerId($tokenVersion->getToken()
            ->getCustomerId());
        $info->setName($tokenVersion->getName());

        /* @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $paymentMethod */
        $paymentMethod = Mage::getModel('postfinancecheckout_payment/entity_paymentMethodConfiguration')->loadByConfigurationId(
            $spaceId, $tokenVersion->getPaymentConnectorConfiguration()
                ->getPaymentMethodConfiguration()
                ->getId());
        $info->setPaymentMethodId($paymentMethod->getId());
        $info->setConnectorId($tokenVersion->getPaymentConnectorConfiguration()
            ->getConnector());

        $info->setSpaceId($spaceId);
        $info->setState($tokenVersion->getToken()
            ->getState());
        $info->setTokenId($tokenVersion->getToken()
            ->getId());
        $info->save();
    }

    public function deleteToken($spaceId, $tokenId)
    {
        $this->getTokenService()->delete($spaceId, $tokenId);
    }

    /**
     * Returns the token API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\TokenService
     */
    protected function getTokenService()
    {
        if ($this->_tokenService == null) {
            $this->_tokenService = new \PostFinanceCheckout\Sdk\Service\TokenService(
                $this->getHelper()->getApiClient());
        }

        return $this->_tokenService;
    }

    /**
     * Returns the token version API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\TokenVersionService
     */
    protected function getTokenVersionService()
    {
        if ($this->_tokenVersionService == null) {
            $this->_tokenVersionService = new \PostFinanceCheckout\Sdk\Service\TokenVersionService(
                $this->getHelper()->getApiClient());
        }

        return $this->_tokenVersionService;
    }
}