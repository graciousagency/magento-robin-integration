<?php


class Robinhq_Hooks_Model_Queue {

    const ORDER = 1;

    const CUSTOMER = 2;
    /**
     * @var false|Robinhq_Hooks_Model_Logger
     */
    private $logger;

    private $models = [];

    private $limit;

    private $batch = 0;

    /**
     * @var false|Robinhq_Hooks_Model_Api
     */
    private $api;

    private $type;

    /**
     * @param Robinhq_Hooks_Model_Logger $logger
     * @param Robinhq_Hooks_Model_Api $api
     * @param $limit
     */
    public function __construct(Robinhq_Hooks_Model_Logger $logger, Robinhq_Hooks_Model_Api $api, $limit) {

        $this->limit = $limit;
        $this->api = $api;
        $this->logger = $logger;
    }

    public function setType($type) {

        $this->type = $type;
    }

    public function push($model) {
        $this->models[] = $model;
        $this->enqueueWhenBatchLimitIsReached();
    }

    public function pushImmediately($model)   {
        $this->models[] = $model;
        $this->enqueueDeduplicate();
        $this->reset();
    }

    /**
     * Enqueue's the remaining items in $this->models,
     * afterwards it resets the $this->models to an empty array.
     * It also sets the batch number back to zero
     */
    public function clear() {

        if (!empty($this->models)) {
            $this->enqueue();
        }
        $this->batch = 0;
        $this->reset();
    }

    private function enqueue() {

        $queueAble = null;
        $message = 'Nothing';
        $first = $this->models[0];
        $last = end($this->models);
        reset($this->models);
        if ($this->type === static::ORDER) {
            $queueAble = new Robinhq_Hooks_Model_Queue_Orders($this->api, $this->models);
            $message = "Orders batch #" . $this->batch++ . " containing " . count($this->models) . " orders (" . $first['order_number'] . " - " . $last['order_number'] . ")";
        }
        if ($this->type === static::CUSTOMER) {
            $queueAble = new Robinhq_Hooks_Model_Queue_Customers($this->api, $this->models);
            $message = "Customers batch #" . $this->batch++ . " containing " . count($this->models) . " customers (" . $first['email_address'] . " - " . $last['email_address'] . ")";
        }
        if ($queueAble !== null) {
            $queueAble->setName($message);
            $queueAble->enqueue();
        }
        $this->logger->log($message . " added to the queue");
    }

    private function enqueueDeduplicate() {

        $queueAble = null;
        $message = 'Nothing';
        $model = $this->models[0];
        if ($this->type === static::ORDER) {
            $message = "Order " . $model['order_number'];
            $jobs = Mage::getModel('jobqueue/job')
                ->getCollection()
                ->addFieldToFilter('name', $message)
            ;
            $jobs->walk('delete');
            $queueAble = new Robinhq_Hooks_Model_Queue_Orders($this->api, $this->models);
        }
        if ($this->type === static::CUSTOMER) {
            $message = "Customer " . $model['email_address'];
            $jobs = Mage::getModel('jobqueue/job')
                ->getCollection()
                ->addFieldToFilter('name', $message)
            ;
            $jobs->walk('delete');
            $queueAble = new Robinhq_Hooks_Model_Queue_Customers($this->api, $this->models);
        }
        if ($queueAble !== null) {
            $queueAble->setName($message);
            $queueAble->enqueue();
        }
        $this->logger->log($message . " added to the queue");
    }

    /**
     * @return bool
     */
    private function enqueueWhenBatchLimitIsReached() {

        if (count($this->models) === $this->limit) {
            $this->enqueue();
            $this->reset();
        }
    }

    private function reset() {

        $this->models = [];
    }
}