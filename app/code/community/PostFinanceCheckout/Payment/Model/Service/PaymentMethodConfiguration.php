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
class PostFinanceCheckout_Payment_Model_Service_PaymentMethodConfiguration extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * Updates the data of the payment method configuration.
     *
     * @param \PostFinanceCheckout\Sdk\Model\PaymentMethodConfiguration $configuration
     */
    public function updateData(\PostFinanceCheckout\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $model */
        $model = Mage::getModel('postfinancecheckout_payment/entity_paymentMethodConfiguration');
        $model->loadByConfigurationId($configuration->getLinkedSpaceId(), $configuration->getId());
        if ($model->getId() && $this->hasChanged($configuration, $model)) {
            $model->setConfigurationName($configuration->getName());
            $model->setTitle($configuration->getResolvedTitle());
            $model->setDescription($configuration->getResolvedDescription());
            $model->setImage($this->getImagePath($configuration->getResolvedImageUrl()));
            $model->setSortOrder($configuration->getSortOrder());
            $model->save();
        }
    }

    protected function hasChanged(\PostFinanceCheckout\Sdk\Model\PaymentMethodConfiguration $configuration,
        PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $model)
    {
        if ($configuration->getName() != $model->getConfigurationName()) {
            return true;
        }

        if ($configuration->getResolvedTitle() != $model->getTitleArray()) {
            return true;
        }

        if ($configuration->getResolvedDescription() != $model->getDescriptionArray()) {
            return true;
        }

        if ($this->getImagePath($configuration->getResolvedImageUrl()) != $model->getImage()) {
            return true;
        }

        if ($configuration->getSortOrder() != $model->getSortOrder()) {
            return true;
        }

        return false;
    }

    /**
     * Synchronizes the payment method configurations from PostFinance Checkout.
     */
    public function synchronize()
    {
        /* @var PostFinanceCheckout_Payment_Model_Resource_PaymentMethodConfiguration_Collection $collection */
        $collection = Mage::getModel('postfinancecheckout_payment/entity_paymentMethodConfiguration')->getCollection();
        $spaceIds = array();
        $existingConfigurations = $collection->getItems();
        $existingFound = array();
        foreach ($existingConfigurations as $existingConfiguration) {
            $existingConfiguration->setState(
                PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration::STATE_HIDDEN);
        }

        foreach (Mage::app()->getWebsites() as $website) {
            $spaceId = $website->getConfig('postfinancecheckout_payment/general/space_id');
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $paymentMethodConfigurationService = new \PostFinanceCheckout\Sdk\Service\PaymentMethodConfigurationService(
                    $this->getHelper()->getApiClient());
                $configurations = $paymentMethodConfigurationService->search($spaceId,
                    new \PostFinanceCheckout\Sdk\Model\EntityQuery());
                foreach ($configurations as $configuration) {
                    /* @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $method */
                    $method = null;
                    foreach ($existingConfigurations as $existingConfiguration) {
                        /* @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration $existingConfiguration */
                        if ($existingConfiguration->getSpaceId() == $spaceId &&
                            $existingConfiguration->getConfigurationId() == $configuration->getId()) {
                            $method = $existingConfiguration;
                            $existingFound[] = $method->getId();
                            break;
                        }
                    }

                    if ($method == null) {
                        $method = $collection->getNewEmptyItem();
                    }

                    $method->setSpaceId($spaceId);
                    $method->setConfigurationId($configuration->getId());
                    $method->setConfigurationName($configuration->getName());
                    $method->setState($this->getConfigurationState($configuration));
                    $method->setTitle($configuration->getResolvedTitle());
                    $method->setDescription($configuration->getResolvedDescription());
                    $method->setImage($this->getImagePath($configuration->getResolvedImageUrl()));
                    $method->setSortOrder($configuration->getSortOrder());
                    $method->save();
                }

                $spaceIds[] = $spaceId;
            }
        }

        foreach ($existingConfigurations as $existingConfiguration) {
            if (! in_array($existingConfiguration->getId(), $existingFound)) {
                $existingConfiguration->setState(
                    PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration::STATE_HIDDEN);
                $existingConfiguration->save();
            }
        }

        $this->createPaymentMethodModelClasses();
        Mage::app()->removeCache(PostFinanceCheckout_Payment_Model_System_Config::SYSTEM_CACHE_ID);
        Mage::app()->removeCache(PostFinanceCheckout_Payment_Model_System_Config::VALUES_CACHE_ID);
        Mage::app()->getCacheInstance()->cleanType(Mage_Core_Model_Config::CACHE_TAG);
    }

    /**
     *
     * @param string $resolvedImageUrl
     * @return string
     */
    protected function getImagePath($resolvedImageUrl)
    {
        $index = strpos($resolvedImageUrl, 'resource/');
        return substr($resolvedImageUrl, $index + strlen('resource/'));
    }

    /**
     * Returns the payment method for the given id.
     *
     * @param int $id
     * @return \PostFinanceCheckout\Sdk\Model\PaymentMethod
     */
    protected function getPaymentMethod($id)
    {
        /* @var PostFinanceCheckout_Payment_Model_Provider_PaymentMethod $methodProvider */
        $methodProvider = Mage::getSingleton('postfinancecheckout_payment/provider_paymentMethod');
        return $methodProvider->find($id);
    }

    /**
     * Returns the state for the payment method configuration.
     *
     * @param \PostFinanceCheckout\Sdk\Model\PaymentMethodConfiguration $configuration
     * @return number
     */
    protected function getConfigurationState(\PostFinanceCheckout\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        switch ($configuration->getState()) {
            case \PostFinanceCheckout\Sdk\Model\CreationEntityState::ACTIVE:
                return PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration::STATE_ACTIVE;
            case \PostFinanceCheckout\Sdk\Model\CreationEntityState::INACTIVE:
                return PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration::STATE_INACTIVE;
            default:
                return PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration::STATE_HIDDEN;
        }
    }

    /**
     * Creates the model classes for the payment methods.
     */
    protected function createPaymentMethodModelClasses()
    {
        /* @var PostFinanceCheckout_Payment_Model_Resource_PaymentMethodConfiguration_Collection $collection */
        $collection = Mage::getModel('postfinancecheckout_payment/entity_paymentMethodConfiguration')->getCollection();
        $generationDir = $this->getHelper()->getGenerationDirectoryPath() . DS . 'PostFinanceCheckout' . DS . 'Payment' .
            DS . 'Model';
        if (! file_exists($generationDir)) {
            mkdir($generationDir, 0777, true);
        }

        $classTemplate = file_get_contents(
            Mage::getModuleDir('', 'PostFinanceCheckout_Payment') . DS . 'Model' . DS . 'Payment' . DS . 'Method' . DS .
            'Template.php.tpl');
        foreach ($collection->getItems() as $configuration) {
            $fileName = $generationDir . DS . 'PaymentMethod' . $configuration->getId() . '.php';
            if (! file_exists($fileName)) {
                file_put_contents($fileName,
                    str_replace(array(
                        '{id}'
                    ), array(
                        $configuration->getId()
                    ), $classTemplate));
            }
        }
    }
}