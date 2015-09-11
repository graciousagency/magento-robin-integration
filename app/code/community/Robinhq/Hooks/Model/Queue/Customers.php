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
     * @param Robinhq_Hooks_Model_Api $api
     * @param Robinhq_Hooks_Model_Robin_Customer[] $customers
     */
    public function __construct(Robinhq_Hooks_Model_Api $api, $customers)
    {
        parent::__construct();
        $this->customers = $customers;
        $this->api = $api;
    }

    public function perform()
    {
        $this->api->customers($this->customers);
    }
}