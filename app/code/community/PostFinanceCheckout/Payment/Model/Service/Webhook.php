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
 * This service handles webhooks.
 */
class PostFinanceCheckout_Payment_Model_Service_Webhook extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The webhook listener API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\WebhookListenerService
     */
    protected $_webhookListenerService;

    /**
     * The webhook url API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\WebhookUrlService
     */
    protected $_webhookUrlService;

    protected $_webhookEntities = array();

    /**
     * Constructor to register the webhook entites.
     */
    public function __construct()
    {
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1487165678181, 'Manual Task',
            array(
                \PostFinanceCheckout\Sdk\Model\ManualTaskState::DONE,
                \PostFinanceCheckout\Sdk\Model\ManualTaskState::EXPIRED,
                \PostFinanceCheckout\Sdk\Model\ManualTaskState::OPEN
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041857405,
            'Payment Method Configuration',
            array(
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::DELETED,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::DELETING,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::INACTIVE
            ), true);
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041829003, 'Transaction',
            array(
                \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED,
                \PostFinanceCheckout\Sdk\Model\TransactionState::DECLINE,
                \PostFinanceCheckout\Sdk\Model\TransactionState::FAILED,
                \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL,
                \PostFinanceCheckout\Sdk\Model\TransactionState::VOIDED,
                \PostFinanceCheckout\Sdk\Model\TransactionState::COMPLETED,
                \PostFinanceCheckout\Sdk\Model\TransactionState::PROCESSING,
                \PostFinanceCheckout\Sdk\Model\TransactionState::CONFIRMED
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041819799,
            'Delivery Indication',
            array(
                \PostFinanceCheckout\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041816898,
            'Transaction Invoice',
            array(
                \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
                \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::PAID
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041831364,
            'Transaction Completion', array(
                \PostFinanceCheckout\Sdk\Model\TransactionCompletionState::FAILED
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041839405, 'Refund',
            array(
                \PostFinanceCheckout\Sdk\Model\RefundState::FAILED,
                \PostFinanceCheckout\Sdk\Model\RefundState::SUCCESSFUL
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041806455, 'Token',
            array(
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::DELETED,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::DELETING,
                \PostFinanceCheckout\Sdk\Model\CreationEntityState::INACTIVE
            ));
        $this->_webhookEntities[] = new PostFinanceCheckout_Payment_Model_Webhook_Entity(1472041811051, 'Token Version',
            array(
                \PostFinanceCheckout\Sdk\Model\TokenVersionState::ACTIVE,
                \PostFinanceCheckout\Sdk\Model\TokenVersionState::OBSOLETE
            ));
    }

    /**
     * Installs the necessary webhooks in PostFinance Checkout.
     */
    public function install()
    {
        $spaceIds = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $spaceId = $website->getConfig('postfinancecheckout_payment/general/space_id');
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if ($webhookUrl == null) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }

                $existingListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->_webhookEntities as $webhookEntity) {
                    /* @var PostFinanceCheckout_Payment_Model_Webhook_Entity $webhookEntity */
                    $exists = false;
                    foreach ($existingListeners as $existingListener) {
                        if ($existingListener->getEntity() == $webhookEntity->getId()) {
                            $exists = true;
                        }
                    }

                    if (! $exists) {
                        $this->createWebhookListener($webhookEntity, $spaceId, $webhookUrl);
                    }
                }

                $spaceIds[] = $spaceId;
            }
        }
    }

    /**
     * Create a webhook listener.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Entity $entity
     * @param int $spaceId
     * @param \PostFinanceCheckout\Sdk\Model\WebhookUrl $webhookUrl
     * @return \PostFinanceCheckout\Sdk\Model\WebhookListenerCreate
     */
    protected function createWebhookListener(PostFinanceCheckout_Payment_Model_Webhook_Entity $entity, $spaceId,
        \PostFinanceCheckout\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $webhookListener = new \PostFinanceCheckout\Sdk\Model\WebhookListenerCreate();
        $webhookListener->setEntity($entity->getId());
        $webhookListener->setEntityStates($entity->getStates());
        $webhookListener->setName('Magento ' . $entity->getName());
        $webhookListener->setState(\PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookListener->setUrl($webhookUrl->getId());
        $webhookListener->setNotifyEveryChange($entity->isNotifyEveryChange());
        return $this->getWebhookListenerService()->create($spaceId, $webhookListener);
    }

    /**
     * Returns the existing webhook listeners.
     *
     * @param int $spaceId
     * @param \PostFinanceCheckout\Sdk\Model\WebhookUrl $webhookUrl
     * @return \PostFinanceCheckout\Sdk\Model\WebhookListener[]
     */
    protected function getWebhookListeners($spaceId, \PostFinanceCheckout\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $filter = new \PostFinanceCheckout\Sdk\Model\EntityQueryFilter();
        $filter->setType(\PostFinanceCheckout\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url.id', $webhookUrl->getId())
            ));
        $query->setFilter($filter);
        return $this->getWebhookListenerService()->search($spaceId, $query);
    }

    /**
     * Creates a webhook url.
     *
     * @param int $spaceId
     * @return \PostFinanceCheckout\Sdk\Model\WebhookUrlCreate
     */
    protected function createWebhookUrl($spaceId)
    {
        $webhookUrl = new \PostFinanceCheckout\Sdk\Model\WebhookUrlCreate();
        $webhookUrl->setUrl($this->getUrl());
        $webhookUrl->setState(\PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookUrl->setName('Magento');
        return $this->getWebhookUrlService()->create($spaceId, $webhookUrl);
    }

    /**
     * Returns the existing webhook url if there is one.
     *
     * @param int $spaceId
     * @return \PostFinanceCheckout\Sdk\Model\WebhookUrl
     */
    protected function getWebhookUrl($spaceId)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $query->setNumberOfEntities(1);
        $filter = new \PostFinanceCheckout\Sdk\Model\EntityQueryFilter();
        $filter->setType(\PostFinanceCheckout\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url', $this->getUrl())
            ));
        $query->setFilter($filter);
        $result = $this->getWebhookUrlService()->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Returns the webhook endpoint URL.
     *
     * @return string
     */
    protected function getUrl()
    {
        return Mage::getUrl('postfinancecheckout/webhook',
            array(
                '_secure' => true,
                '_store' => Mage::app()->getDefaultStoreView()->getId()
            ));
    }

    /**
     * Returns the webhook listener API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\WebhookListenerService
     */
    protected function getWebhookListenerService()
    {
        if ($this->_webhookListenerService == null) {
            $this->_webhookListenerService = new \PostFinanceCheckout\Sdk\Service\WebhookListenerService(
                Mage::helper('postfinancecheckout_payment')->getApiClient());
        }

        return $this->_webhookListenerService;
    }

    /**
     * Returns the webhook url API service.
     *
     * @return \PostFinanceCheckout\Sdk\Service\WebhookUrlService
     */
    protected function getWebhookUrlService()
    {
        if ($this->_webhookUrlService == null) {
            $this->_webhookUrlService = new \PostFinanceCheckout\Sdk\Service\WebhookUrlService(
                Mage::helper('postfinancecheckout_payment')->getApiClient());
        }

        return $this->_webhookUrlService;
    }
}