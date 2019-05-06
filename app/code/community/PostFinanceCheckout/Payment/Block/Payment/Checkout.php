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
 * This block extends the checkout to be able to process PostFinance Checkout payments.
 */
class PostFinanceCheckout_Payment_Block_Payment_Checkout extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('postfinancecheckout/payment/checkout.phtml');
    }

    /**
     * Returns the URL to PostFinance Checkout's JavaScript library that is necessary to display the payment form.
     *
     * @return string
     */
    public function getJavaScriptUrl()
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        /* @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        try {
            return $transactionService->getJavaScriptUrl($checkoutSession->getQuote());
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the URL to PostFinance Checkout's payment page.
     *
     * @return string
     */
    public function getPaymentPageUrl()
    {
        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        /* @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        try {
            return $transactionService->getPaymentPageUrl($checkoutSession->getQuote());
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the URL to PostFinance Checkout's Javascript library to collect customer data.
     *
     * @return string
     */
    public function getDeviceJavascriptUrl()
    {
        /* @var PostFinanceCheckout_Payment_Helper_Data $helper */
        $helper = Mage::helper('postfinancecheckout_payment');
        return $helper->getDeviceJavascriptUrl();
    }
}