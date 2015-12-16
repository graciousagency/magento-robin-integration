<?php


/**
 * Class Robinhq_Hooks_Model_Observer
 */
class Robinhq_Hooks_Model_Observer {

    /**
     * @var Robinhq_Hooks_Model_Queue
     */
    private $push;

    /**
     * @var Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $helper;

    private $enabled;

    /**
     * @var Robinhq_Hooks_Model_Robin_Converter
     */
    private $converter;

    /**
     * Gets and sets the dependency's
     */
    public function __construct() {

        $this->helper = Mage::helper("hooks");
        $this->push = $this->helper->getQueue();
        $this->api = $this->helper->getApi();
        $this->enabled = $this->isEnabled();
        $this->converter = $this->helper->getConverter();
    }


    /**
     * Fires when the customer_save_after_handler events is dispatched.
     * This happens when a customer is created and when an customer places an order.
     * Also fires when a customer is created/edited through the backend.
     *
     * @param Varien_Event_Observer|Mage_Customer_Model_Customer $customer
     */
    public function customerHook($customer) {

        if ($this->enabled) {
            if ($customer instanceof Varien_Event_Observer) {
                $customer = $customer->getEvent()->getCustomer();
            }
            if ($customer) {
                try {
                    $this->helper->log('User with id: ' . $customer->getId() . ' changed, sending it to ROBIN');
                    $customer = $this->converter->toRobinCustomer($customer);
                    $this->api->customer($customer);
                }
                catch (Exception $e) {
                    $this->helper->log('Exception: ' . $e->getMessage());
                    $this->helper->warnAdmin($e->getMessage());
                }
            }
        }
    }

    /**
     * Fires when the sales_order_place_after_handler event is dispatched
     * and sends the processed order to Robin
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderPlacedHook(Varien_Event_Observer $observer) {

        if ($this->enabled) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order) {
                $this->helper->log('New order placed with id: ' . $order->getId());
                try {
                    $order = $this->converter->toRobinOrder($order);
                    $this->api->order($order);
                }
                catch (Exception $e) {
                    $this->helper->log('Exception: ' . $e->getMessage());
                    $this->helper->warnAdmin($e->getMessage());
                }
            }
        }
    }

    /**
     * Fires when the sales_order_save_after_handler event is dispatched
     * and sends the changed order to ROBIN. This event captures all changes made
     * to an order. It only sends the order to Robin when it has a status.
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderStatusChangeHook(Varien_Event_Observer $observer) {

        if ($this->enabled) {
            $order = $observer->getEvent()->getOrder();
            $status = $order->getStatus();
            if (is_string($status)) { //only fire when we actually have an status
                $string = 'The status of order #' . $order->getIncrementId() . ' changed to: ' . $order->getStatus();
                $this->helper->log($string);
                try {
                    $this->helper->log('Order has changed, sending it to Robin');
                    $robinOrder = $this->converter->toRobinOrder($order);
                    $this->api->order($robinOrder);
                    if ($order->getCustomerId() !== null) {
                        /** @var Mage_Customer_Model_Customer $customer */
                        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                        $this->customerHook($customer);
                    }
                    // TODO: Implement Guests checkout
                }
                catch (Exception $e) {
                    $this->helper->log('Exception: ' . $e->getMessage());
                    $this->helper->warnAdmin($e->getMessage());
                }
            }
        }
    }

    private function isEnabled() {

        $config = Mage::getStoreConfig('settings/general');
        return $config['enabled'];
    }

    /**
     * Include our composer auto loader
     *
     * @param Varien_Event_Observer $event
     */
    public function controllerFrontInitBefore(Varien_Event_Observer $event) {

        //        Mage::log(__METHOD__, null, 'pepijn.log');
        //        Mage::log('----------------------------', null, 'pepijn.log');
        //        Mage::helper('hooks')->formatPhoneNumber('0624289059', 'NL');
        //        Mage::log('----------------------------', null, 'pepijn.log');
        //        Mage::helper('hooks')->formatPhoneNumber('31624289059', 'NL');
        //        Mage::log('----------------------------', null, 'pepijn.log');
        //        Mage::helper('hooks')->formatPhoneNumber('32624289059', 'BE');
        //        Mage::log('----------------------------', null, 'pepijn.log');
        //        $address = Mage::getModel('customer/address')->load(1000);
        //        Mage::log('$address = ' . $address->getCountryId(), null, 'pepijn.log');
    }


}
