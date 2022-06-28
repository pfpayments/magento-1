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
 * Abstract implementation of a provider.
 */
abstract class PostFinanceCheckout_Payment_Model_Provider_Abstract
{

    protected $_cacheKey;

    protected $_cacheTag;

    protected $_data;

    /**
     * Constructor.
     *
     * @param string $cacheKey
     * @param string $cacheTag
     */
    public function __construct($cacheKey, $cacheTag = 'COLLECTION_DATA')
    {
        $this->_cacheKey = $cacheKey;
        $this->_cacheTag = $cacheTag;
    }

    /**
     * Fetch the data from the remote server.
     *
     * @return array
     */
    abstract protected function fetchData();

    /**
     * Returns the id of the given entry.
     *
     * @param mixed $entry
     * @return string
     */
    abstract protected function getId($entry);

    /**
     * Returns a single entry by id.
     *
     * @param string $id
     * @return mixed
     */
    public function find($id)
    {
        if ($this->_data == null) {
            $this->loadData();
        }

        if (isset($this->_data[$id])) {
            return $this->_data[$id];
        } else {
            return false;
        }
    }

    /**
     * Returns all entries.
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->_data == null) {
            $this->loadData();
        }

        return $this->_data;
    }

    private function loadData()
    {
        $cachedData = Mage::app()->loadCache($this->_cacheKey);
        if ($cachedData) {
            $this->_data = unserialize($cachedData);
        } else {
            $this->_data = array();
            foreach ($this->fetchData() as $entry) {
                $this->_data[$this->getId($entry)] = $entry;
            }

            Mage::app()->saveCache(serialize($this->_data), $this->_cacheKey, array(
                $this->_cacheTag
            ));
        }
    }
}