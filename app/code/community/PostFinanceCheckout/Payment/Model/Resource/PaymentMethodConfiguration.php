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
 * Resource model of payment method configuration.
 */
class PostFinanceCheckout_Payment_Model_Resource_PaymentMethodConfiguration extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * DB read connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_read;

    protected $_serializableFields = array(
        'title' => array(
            null,
            array()
        ),
        'description' => array(
            null,
            array()
        )
    );

    protected function _construct()
    {
        $this->_init('postfinancecheckout_payment/payment_method_configuration', 'entity_id');
        $this->_read = $this->_getReadAdapter();
    }

    /**
     * Load the payment method by space and configuration.
     *
     * @param PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $model
     * @param int $spaceId
     * @param int $configurationId
     * @return array
     */
    public function loadByConfigurationId(PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $model,
        $spaceId, $configurationId)
    {
        $select = $this->_read->select()
            ->from($this->getMainTable())
            ->where('space_id=:space_id AND configuration_id=:configuration_id');

        $data = $this->_read->fetchRow($select, array(
            'space_id' => $spaceId,
            'configuration_id' => $configurationId
        ));

        $model->setData($data);
        $this->unserializeFields($model);
        $this->_afterLoad($model);
    }
}