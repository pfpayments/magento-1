<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout/).
 *
 * @package PostFinanceCheckout_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * The observer handles cron jobs.
 */
class PostFinanceCheckout_Payment_Model_Observer_Cron
{

    /**
     * Tries to send all pending refunds to the gateway.
     */
    public function processRefundJobs()
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_Refund $refundService */
        $refundService = Mage::getSingleton('postfinancecheckout_payment/service_refund');

        /* @var PostFinanceCheckout_Payment_Model_Resource_RefundJob_Collection $refundJobCollection */
        $refundJobCollection = Mage::getModel('postfinancecheckout_payment/entity_refundJob')->getCollection();
        $refundJobCollection->setPageSize(100);
        foreach ($refundJobCollection->getItems() as $refundJob) {
            /* @var PostFinanceCheckout_Payment_Model_Entity_RefundJob $refundJob */
            try {
                $refundService->refund($refundJob->getSpaceId(), $refundJob->getRefund());
            } catch (\PostFinanceCheckout\Sdk\ApiException $e) {
                if ($e->getResponseObject() instanceof \PostFinanceCheckout\Sdk\Model\ClientError) {
                    $refundJob->delete();
                } else {
                    Mage::logException($e);
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}