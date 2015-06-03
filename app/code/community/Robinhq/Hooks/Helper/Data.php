<?php

/**
 * Class Robinhq_Hooks_Helper_Data
 */
class Robinhq_Hooks_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @var Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * Gets and sets the dependency's
     */
    public function __construct()
    {
        $this->api = Mage::getModel('hooks/api');
        $this->logger = Mage::getModel('hooks/logger');
    }

    /**
     * Sends all orders to the Robin API.
     */
    public function sendOrders()
    {
        $collection = Mage::getModel('sales/order')->getCollection();
//        $this->helper->log($collection->count());
        $this->api->orders($collection);
    }

    /**
     * Sends all customers to the Robin API.
     */
    public function sendCustomers()
    {
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('twitter_handler');

        $this->api->customers($collection);
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        $this->logger->log($message);
    }

    /**
     * @return Robinhq_Hooks_Model_Api
     */
    public function getApi()
    {
        return $this->api;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public static function warnAdmin($warning)
    {
        Mage::getSingleton('adminhtml/session')->addWarning("Robin: " . $warning);
    }

    public static function noticeAdmin($notice)
    {
        Mage::getSingleton('adminhtml/session')->addSuccess("Robin: " . $notice);
    }

    public static function formatPrice($price)
    {
        return Mage::helper('core')->currency($price, true, false);
    }
}
	 