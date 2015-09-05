<?php


class Robinhq_Hooks_Model_Queue
{
    const ORDER = 1;

    const CUSTOMER = 2;
    /**
     * @var false|Robinhq_Hooks_Model_Logger
     */
    private $logger;

    private $models = array();

    private $limit;

    private $batch = 0;

    /**
     * @var false|Robinhq_Hooks_Model_Api
     */
    private $api;

    private $type;

    /**
     * @param $limit
     */
    public function __construct($limit)
    {
        $this->logger = Mage::getModel('hooks/logger');
        $this->api = Mage::getModel("hooks/api");
        $this->limit = $limit;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function push($model)
    {
        $this->models[] = $model;
        $this->queueWhenBatchLimitIsReached();
    }

    public function done()
    {
        if (!empty($this->models)) {
            $this->enqueue();
        }
        $this->batch = 0;
        $this->reset();
    }

    private function enqueue()
    {
        $queueAble = null;
        $message = "Nothing";

        if ($this->type === static::ORDER) {
            $queueAble = new Robinhq_Hooks_Model_Queue_Orders($this->models, $this->api);
            $message = "Orders batch #" . $this->batch++ . " containing " . count($this->models) . " orders";
        }

        if ($this->type === static::CUSTOMER) {
            $queueAble = new Robinhq_Hooks_Model_Queue_Customers($this->models);
            $message = "Customers batch #" . $this->batch++ . " containing " . count($this->models) . " customers";
        }

        if ($queueAble !== null) {
            $queueAble->setName($message);
            $queueAble->enqueue();
        }

        $this->logger->log($message . " Added to the queue");
    }

    /**
     * @return bool
     */
    private function queueWhenBatchLimitIsReached()
    {
        if (count($this->models) === $this->limit) {
            $this->enqueue();
            $this->reset();
        }
    }

    private function reset()
    {
        $this->models = array();
    }
}