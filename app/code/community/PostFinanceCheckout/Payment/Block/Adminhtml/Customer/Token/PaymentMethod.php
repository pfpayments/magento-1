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
 * This block renders the payment method column in the token grid.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_Customer_Token_PaymentMethod extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{

    public function _getValue(Varien_Object $row)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $paymentMethod */
        $paymentMethod = Mage::getModel('postfinancecheckout_payment/entity_paymentMethodConfiguration')->load(
            $row->payment_method_id);
        return $paymentMethod->getConfigurationName();
    }
}