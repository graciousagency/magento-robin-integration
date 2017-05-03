<?php


/**
 * Class Robinhq_Hooks_Model_Observer
 */
class Robinhq_Hooks_Model_Observer
{

    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;

    /**
     * Gets and sets the dependency's
     */
    public function __construct()
    {
        $this->helper = Mage::helper('robinhq_hooks');
    }


    /**
     * Fires when the customer_save_after_handler events is dispatched.
     * This happens when a customer is created and when an customer places an order.
     * Also fires when a customer is created/edited through the backend.
     *
     * @param Varien_Event_Observer|Mage_Customer_Model_Customer $customer
     */
    public function customerHook($customer)
    {
        $helper = $this->helper;
        if (!$helper->isEnabled()) {
            return;
        }

        if ($customer instanceof Varien_Event_Observer) {
            $customer = $customer->getEvent()
                    ->getCustomer();
        }

        if (!$customer) {
            return;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        if (!$customer->getId()) {
            return;
        }

        try {
            $helper->log('User with id: ' . $customer->getId() . ' changed, sending it to ROBIN');

            $queue = $helper->getQueue();
            $converter = $helper->getConverter();

            $queue->setType($queue::CUSTOMER);

            $aCustomer = $converter->toRobinCustomer($customer);
            $queue->pushImmediately($aCustomer);

        } catch (Exception $e) {
            $helper->log('Exception: ' . $e->getMessage());
            $helper->warnAdmin($e->getMessage());
        }

    }


    /**
     * Fires when the customer_save_after_handler events is dispatched.
     * This happens when a customer is created and when an customer places an order.
     * Also fires when a customer is created/edited through the backend.
     *
     * @param Varien_Event_Observer|Mage_Customer_Model_Customer $customer
     */
    public function customerHookOld($customer)
    {
        $helper = $this->helper;
        if (!$helper->isEnabled()) {
            return;
        }

        if ($customer instanceof Varien_Event_Observer) {
            $customer = $customer->getEvent()
                    ->getCustomer();
        }

        if (!$customer) {
            return;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        if (!$customer->getId()) {
            return;
        }


        try {
            $helper->log('User with id: ' . $customer->getId() . ' changed, sending it to ROBIN');

            $customer = $helper->getConverter()
                    ->toRobinCustomer($customer);

            $helper->getApi()
                    ->customer($customer);

        } catch (Exception $e) {

            $helper->log('Exception: ' . $e->getMessage());
            $helper->warnAdmin($e->getMessage());
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
        $helper = $this->helper;
        if (!$helper->isEnabled()) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()
                ->getOrder();

        if (!$order) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        if (!$order->getId()) {
            return;
        }

        $helper->log('New order placed with id: ' . $order->getId());
        try {
            $order = $helper->getConverter()
                    ->toRobinOrder($order);

            $helper->getApi()
                    ->order($order);

        } catch (Exception $e) {

            $helper->log('Exception: ' . $e->getMessage());
            $helper->warnAdmin($e->getMessage());
        }

    }

    /**
     * Fires when the sales_order_save_after_handler event is dispatched
     * and sends the changed order to ROBIN. This event captures all changes made
     * to an order. It only sends the order to Robin when it has a status.
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderStatusChangeHook(Varien_Event_Observer $observer)
    {
        $helper = $this->helper;
        if (!$helper->isEnabled()) {
            return;
        }

        $order = $observer->getEvent()
                ->getOrder();

        if (!$order) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        if (!$order->getId()) {
            return;
        }

        $status = $order->getStatus();
        if (!is_string($status)) {
            return;
        }
        // only fire when we actually have an status

        $helper->log('The status of order #' . $order->getIncrementId() . ' changed to: ' . $order->getStatus());

        try {

            $helper->log('Order has changed, sending it to Robin');

            if (null !== $order->getCustomerId()) {
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')
                        ->load($order->getCustomerId());
                $this->customerHook($customer);
            }

            $queue = $helper->getQueue();
            $queue->setType($queue::ORDER);

            $converter = $helper->getConverter();

            $aOrder = $converter->toRobinOrder($order);
            $queue->pushImmediately($aOrder);

        } catch (Exception $e) {

            $helper->log('Exception: ' . $e->getMessage());
            $helper->warnAdmin($e->getMessage());
        }
    }

    /**
     * Fires when the sales_order_save_after_handler event is dispatched
     * and sends the changed order to ROBIN. This event captures all changes made
     * to an order. It only sends the order to Robin when it has a status.
     *
     * @param Varien_Event_Observer $observer
     */
    public function orderStatusChangeHookOld(Varien_Event_Observer $observer)
    {
        $helper = $this->helper;
        if (!$helper->isEnabled()) {
            return;
        }

        $order = $observer->getEvent()
                ->getOrder();

        if (!$order) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        if (!$order->getId()) {
            return;
        }

        $status = $order->getStatus();
        if (!is_string($status)) {
            return;
        }

        $helper->log('The status of order #' . $order->getIncrementId() . ' changed to: ' . $order->getStatus());

        try {
            $helper->log('Order has changed, sending it to Robin');

            $robinOrder = $helper->getConverter()
                    ->toRobinOrder($order);

            if (null !== $order->getCustomerId()) {
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')
                        ->load($order->getCustomerId());
                $this->customerHook($customer);
            }

            $helper->getApi()
                    ->order($robinOrder);
            // TODO: Implement Guests checkout

        } catch (Exception $e) {

            $helper->log('Exception: ' . $e->getMessage());
            $helper->warnAdmin($e->getMessage());
        }
    }

}
