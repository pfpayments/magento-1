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
 * This block extends the cart to be able to collect device data.
 */
class PostFinanceCheckout_Payment_Block_Payment_Cart extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('postfinancecheckout/payment/cart.phtml');
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