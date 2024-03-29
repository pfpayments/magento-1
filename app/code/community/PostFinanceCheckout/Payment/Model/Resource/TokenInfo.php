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
 * Resource model of token info.
 */
class PostFinanceCheckout_Payment_Model_Resource_TokenInfo extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * DB read connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_read;

    protected function _construct()
    {
        $this->_init('postfinancecheckout_payment/token_info', 'entity_id');
        $this->_read = $this->_getReadAdapter();
    }

    /**
     * Load the token info by space and token.
     *
     * @param PostFinanceCheckout_Payment_Model_Entity_TokenInfo $model
     * @param int $spaceId
     * @param int $tokenId
     * @return array
     */
    public function loadByToken(PostFinanceCheckout_Payment_Model_Entity_TokenInfo $model, $spaceId, $tokenId)
    {
        $select = $this->_read->select()
            ->from($this->getMainTable())
            ->where('space_id=:space_id AND token_id=:token_id');

        $data = $this->_read->fetchRow($select, array(
            'space_id' => $spaceId,
            'token_id' => $tokenId
        ));

        $model->setData($data);
        $this->unserializeFields($model);
        $this->_afterLoad($model);
    }
}