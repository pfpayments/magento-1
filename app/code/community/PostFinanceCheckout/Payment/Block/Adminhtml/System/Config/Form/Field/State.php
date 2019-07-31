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
 * This block renders the form field that displays a simple state, yes or no.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_System_Config_Form_Field_State extends PostFinanceCheckout_Payment_Block_Adminhtml_System_Config_Form_Field_Label
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $value = $element->getData('value');
        return $value == 1 ? $this->helper('postfinancecheckout_payment')->__('Yes') : $this->helper(
            'postfinancecheckout_payment')->__('No');
    }
}