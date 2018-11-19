<?php

class Robinhq_Hooks_Model_Robin_Converter
{

    /** @var Robinhq_Hooks_Model_Robin_Customer */
    protected $robinCustomer;
    /** @var Robinhq_Hooks_Model_Robin_Order */
    protected $robinOrder;

    public function __construct()
    {
        $this->robinCustomer = Mage::getModel('robinhq_hooks/robin_customer');
        $this->robinOrder = Mage::getModel('robinhq_hooks/robin_order');
    }

    /**
     * Converts a Mage_Customer_Model_Customer into a simple array
     * with key/value pairs that are required by the Robin API.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function toRobinCustomer($customer)
    {
        return $this->robinCustomer
                ->factory($customer);
    }

    /**
     * Converts a Mage_Sales_Model_Order into a array with required key/value pairs and a
     * example details_view.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function toRobinOrder($order)
    {
        return $this->robinOrder
                ->factory($order);
    }

}