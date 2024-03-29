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
 * The block renders the payment form in the checkout.
 */
class PostFinanceCheckout_Payment_Block_Payment_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('postfinancecheckout/payment/form.phtml');
    }

    /**
     * Returns the URL to the payment method image.
     *
     * @return string
     */
    public function getImageUrl()
    {
        /* @var PostFinanceCheckout_Payment_Model_Payment_Method_Abstract $methodInstance */
        $methodInstance = $this->getMethod();
        $spaceId = $methodInstance->getPaymentMethodConfiguration()->getSpaceId();
        $spaceViewId = Mage::getStoreConfig('postfinancecheckout_payment/general/space_view_id');
        $language = Mage::getStoreConfig('general/locale/code');
        /* @var PostFinanceCheckout_Payment_Helper_Data $helper */
        $helper = $this->helper('postfinancecheckout_payment');
        return $helper->getResourceUrl($methodInstance->getPaymentMethodConfiguration()
            ->getImage(), $language, $spaceId, $spaceViewId);
    }

    /**
     * Returns the list of tokens that can be applied.
     *
     * @return PostFinanceCheckout_Payment_Model_Entity_TokenInfo[]
     */
    public function getTokens()
    {
        /* @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();

        /* @var PostFinanceCheckout_Payment_Model_Payment_Method_Abstract $methodInstance */
        $methodInstance = $this->getMethod();
        $spaceId = $methodInstance->getPaymentMethodConfiguration()->getSpaceId();

        /* @var PostFinanceCheckout_Payment_Model_Resource_TokenInfo_Collection $collection */
        $collection = Mage::getModel('postfinancecheckout_payment/entity_tokenInfo')->getCollection();
        $collection->addCustomerFilter($quote->getCustomerId());
        $collection->addSpaceFilter($spaceId);
        $collection->addPaymentMethodConfigurationFilter($methodInstance->getPaymentMethodConfigurationId());
        return $collection->getItems();
    }
}