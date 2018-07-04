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
 * Abstract webhook processor.
 */
abstract class PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated extends PostFinanceCheckout_Payment_Model_Webhook_Abstract
{

    /**
     * Processes the received order related webhook request.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Request $request
     */
    protected function process(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        $entity = $this->loadEntity($request);

        /* @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->getResource()->beginTransaction();
        try {
            $order->load($this->getOrderId($entity));
            if ($order->getId() > 0) {
                if ($order->getPostfinancecheckoutTransactionId() != $this->getTransactionId($entity)) {
                    return;
                }

                $this->lock($order);
                $order->load($order->getId());
                $this->processOrderRelatedInner($order, $entity);
            }

            $order->getResource()->commit();
        } catch (Exception $e) {
            $order->getResource()->rollBack();
            throw $e;
        }
    }

    /**
     * Loads and returns the entity for the webhook request.
     *
     * @param PostFinanceCheckout_Payment_Model_Webhook_Request $request
     * @return object
     */
    abstract protected function loadEntity(PostFinanceCheckout_Payment_Model_Webhook_Request $request);

    /**
     * Returns the transaction's id linked to the entity.
     *
     * @param object $entity
     * @return int
     */
    abstract protected function getTransactionId($entity);

    /**
     * Actually processes the order related webhook request.
     *
     * This must be implemented
     *
     * @param Mage_Sales_Model_Order $order
     * @param mixed $entity
     */
    abstract protected function processOrderRelatedInner(Mage_Sales_Model_Order $order, $entity);

    protected function getOrderId($entity)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $transactionInfo */
        $transactionInfo = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByTransaction($entity->getLinkedSpaceId(), $this->getTransactionId($entity));
        return $transactionInfo->getOrderId();
    }
    
    /**
     * Create a lock to prevent concurrency.
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function lock(Mage_Sales_Model_Order $order)
    {
        /* @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $resource->getConnection('core_write')->update(
            $resource->getTableName('sales/order'), array(
                'postfinancecheckout_lock' => date("Y-m-d H:i:s")
            ), array(
                'entity_id = ?' => $order->getId()
            )
        );
    }
}