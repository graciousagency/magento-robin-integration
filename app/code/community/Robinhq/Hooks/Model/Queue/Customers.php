<?php


class Robinhq_Hooks_Model_Queue_Customers extends Jowens_JobQueue_Model_Job_Abstract
{
    /**
     * @var Mage_Customer_Model_Customer[]
     */
    private $customers;

    /**
     * @var false|Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @param Robinhq_Hooks_Model_Robin_Customer[] $orders
     */
    public function __construct($orders)
    {
        parent::__construct();
        $this->api = Mage::getModel("hooks/api");
        $this->customers = $orders;

    }

    public function perform()
    {
        $this->api->customers($this->customers);
    }
}