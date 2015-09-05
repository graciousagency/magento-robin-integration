<?php

/**
 * Class Robinhq_Hooks_Helper_Data
 */
class Robinhq_Hooks_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var Robinhq_Hooks_Model_Queue
     */
    private $queue;

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * @var Robinhq_Hooks_Model_Robin_Converter
     */
    private $converter;

    private $limit;

    /**
     * Gets and sets the dependency's
     */
    public function __construct()
    {
        $config = Mage::getStoreConfig('settings/general');
        $this->limit = (int)$config['bulk_limit'];

        $this->queue = Mage::getModel('hooks/queue', $this->limit);
        $this->logger = Mage::getModel('hooks/logger');
        $this->converter = Mage::getModel('hooks/robin_converter');
    }

    /**
     * Sends all orders to the Robin API.
     */
    public function sendOrders()
    {
        $collection = Mage::getModel('sales/order')->getCollection();
        $this->logCount($collection);
        $this->queue->setType(Robinhq_Hooks_Model_Queue::ORDER);
        $this->iterate($collection, array(array($this, "orderCallback")));
        $this->queue->done();
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

        $this->logCount($collection);
        $this->queue->setType(Robinhq_Hooks_Model_Queue::CUSTOMER);
        $this->iterate($collection, array(array($this, "customerCallback")));
        $this->queue->done();
    }

    public function customerCallback($args)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setData($args['row']);
        $customer = $this->converter->toRobinCustomer($customer);
        $this->queue->push($customer);
    }

    public function orderCallback($args)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setData($args['row']);
        $order = $this->converter->toRobinOrder($order);
        $this->queue->push($order);
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        $this->logger->log($message);
    }

    /**
     * @return Robinhq_Hooks_Model_Queue
     */
    public function getQueue()
    {
        return $this->queue;
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

    /**
     * @param $collection
     * @param array $callback
     */
    private function iterate($collection, array $callback)
    {
        Mage::getModel('core/resource_iterator')->walk($collection->getSelect(), $callback);
    }

    private function logCount($collection)
    {
        $this->log("Processing " . $collection->count() . " items");
    }

    /**
     * @return Robinhq_Hooks_Model_Robin_Converter
     */
    public function getConverter()
    {
        return $this->converter;
    }
}
	 