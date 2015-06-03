<?php


/**
 * Class Robinhq_Hooks_Model_Observer
 */
class Robinhq_Hooks_Model_Observer
{

    /**
     * @var Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $helper;

    /**
     * Gets and sets the dependency's
     */
    public function __construct()
    {
        $this->helper = Mage::helper("hooks");
        $this->api = $this->helper->getApi();
    }


    /**
     * Fires when the customer_save_after_handler events is dispatched.
     * This happens when a customer is created and when an customer places an order.
     * Also fires when a customer is created/edited through the backend.
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerHook(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $this->_customerHook($customer);
    }

    /**
     * Fires when the sales_order_place_after_handler event is dispatched
     * and sends the processed order to Robin
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderPlacedHook(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->helper->log("New order placed with id: " . $order->getId());
            try {
                $this->api->orders(array($order));
            } catch (Exception $e) {
                $this->helper->log("Exception: " . $e->getMessage());
                $this->helper->warnAdmin($e->getMessage());
            }
        }

    }

    /**
     * Fires when the sales_order_save_after_handler event is dispatched
     * and sends the changed order to robin. This event captures all changes made
     * to an order. It only sends the order to Robin when it has a status.
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderStatusChanceHook(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $status = $order->getStatus();
        $this->helper->log($status);
        if (is_string($status)) { //only fire when we actually have an status
            $string = "The status of order #" . $order->getIncrementId() . " chanced to: " . $order->getStatus();
            $this->helper->log($string);
            try {
                $this->api->orders(array($order));
                $this->helper->log("Order has changed, sending updated customer info to Robin");
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                $this->_customerHook($customer);
            } catch (Exception $e) {
                $this->helper->log("Exception: " . $e->getMessage());
                $this->helper->warnAdmin($e->getMessage());
            }
        }

    }


    /**
     * @param Mage_Customer_Model_Customer $customer
     */
    private function _customerHook(Mage_Customer_Model_Customer $customer)
    {
        if ($customer) {
            $this->helper->log("user with id: " . $customer->getId() . " chanced");
            try {
                $this->api->customers(array($customer));
            } catch (Exception $e) {
                $this->helper->log("Exception: " . $e->getMessage());
                $this->helper->warnAdmin($e->getMessage());
            }
        }
    }

}
