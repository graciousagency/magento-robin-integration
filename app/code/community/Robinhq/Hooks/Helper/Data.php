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
    public function __construct(){
        $this->api = Mage::getModel('hooks/api');
        $this->logger = Mage::getModel('hooks/logger');
    }

    /**
     * Sends all orders to the Robin API.
     */
    public function sendOrders(){
        $collection = Mage::getModel('sales/order')->getCollection();
        $this->api->orders($collection);
    }

    /**
     * Sends all customers to the Robin API.
     */
    public function sendCustomers(){
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at');

        $this->api->customers($collection);
    }

    public function log($message){
        $this->logger->log($message);
    }
}
	 