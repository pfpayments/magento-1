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
 * This controller accepts webhook requests from PostFinance Checkout and redirects them to the suitable processor.
 */
class PostFinanceCheckout_Payment_WebhookController extends Mage_Core_Controller_Front_Action
{

    /**
     * Accepts webhook requests from PostFinance Checkout and redirects them to the suitable processor.
     */
    public function indexAction()
    {
        http_response_code(500);
        $this->getResponse()->setHttpResponseCode(500);
        $request = new PostFinanceCheckout_Payment_Model_Webhook_Request(
            json_decode($this->getRequest()->getRawBody()));
        try {
            Mage::dispatchEvent(
                'postfinancecheckout_payment_webhook_' . strtolower($request->getListenerEntityTechnicalName()),
                array(
                    'request' => $request
                ));
        } catch (Exception $e) {
            Mage::log(
                'The webhook  ' . $request->getEntityId() . ' could not be processed because of an exception: ' . "\n" .
                $e->__toString(), Zend_Log::ERR, 'postfinancecheckout.log');
            throw $e;
        }
        $this->getResponse()->setHttpResponseCode(200);
    }
}