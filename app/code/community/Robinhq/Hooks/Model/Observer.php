<?php


/**
 * Class Robinhq_Hooks_Model_Observer
 */
class Robinhq_Hooks_Model_Observer
{


    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * @var Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * Gets and sets the dependency's
     */
    public function __construct(){
        $this->logger = Mage::getModel('hooks/logger');
        $this->api = Mage::getModel('hooks/api');
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
        if($customer){
           $this->logger->log("user with id: ". $customer->getId() . " chanced");
            try{
                $this->api->customers(array($customer));
            }
            catch(Exception $e){
                $this->logger->log("Exception: ". $e->getMessage());
                $this->logger->warnAdmin('Unable to send changes to ROBIN, see the log file for more information.');
            }

        }
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
        if($order){
            $this->logger->log("New order placed with id: " . $order->getId());
            try{
                $this->api->orders(array($order));
            }
            catch(Exception $e){
                $this->logger->log("Exception: ". $e->getMessage());
                $this->logger->warnAdmin('Unable to send changes to ROBIN, see the log file for more information.');
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
        if(is_string($order->getStatus())){ //only fire when we actually have an status
            $string = "The status of order #" . $order->getId() . " chanced to: " . $order->getStatus();
            $this->logger->log($string);
            try{
                $this->api->orders(array($order));
            }
            catch(Exception $e){
                $this->logger->log("Exception: ". $e->getMessage());
                $this->logger->warnAdmin('Unable to send changes to ROBIN, see the log file for more information.');
            }
        }
    }

}
