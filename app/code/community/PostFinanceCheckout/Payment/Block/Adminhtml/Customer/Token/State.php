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
 * This block renders the state column in the token grid.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_Customer_Token_State extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{

    public function _getValue(Varien_Object $row)
    {
        $helper = Mage::helper('postfinancecheckout_payment');
        switch ($row->state) {
            case \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE:
                return $helper->__('Active');
            case \PostFinanceCheckout\Sdk\Model\CreationEntityState::INACTIVE:
                return $helper->__('Inactive');
        }
    }
}