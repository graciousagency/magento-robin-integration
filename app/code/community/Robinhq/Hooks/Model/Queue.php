<?php

class Robinhq_Hooks_Model_Queue
{

    const ORDER = 1;

    const CUSTOMER = 2;

    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;
    /** @var array  */
    protected $models = [];
    /** @var int */
    protected $limit;
    /** @var int */
    protected $batch = 0;

    /** @var int */
    protected $type;

    /**
     * @param Robinhq_Hooks_Helper_Data $helper
     */
    public function __construct(Robinhq_Hooks_Helper_Data $helper)
    {
        $this->helper = $helper;
        $this->limit = +$helper->getConfig('bulk_limit');
    }

    /**
     * Set type
     *
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * This pushes a model to the queue when the batch limit has not been reached yet
     * Otherwise it starts creating another batch
     *
     * @param $model
     */
    public function push($model)
    {
        $this->models[] = $model;
        $this->enqueueWhenBatchLimitIsReached();
    }

    /**
     * Ignore batch settings and add to queue right away
     * Creates single lines in the queue instead of a batch
     *
     * @param $model
     */
    public function pushImmediately($model)
    {
        $this->models[] = $model;
        $this->enqueueDeduplicate();
        $this->reset();
    }

    /**
     * Enqueue's the remaining items in $this->models,
     * afterwards it resets the $this->models to an empty array.
     * It also sets the batch number back to zero
     */
    public function clear()
    {
        $this->enqueue();
        $this->batch = 0;
        $this->reset();
    }

    /**
     * This creates a queue as used by the mass uploader
     */
    protected function enqueue()
    {
        $helper = $this->helper;

        if (empty($this->models)) {
            // Nothing to do
            return;
        }

        $first = $this->models[0];
        $last = end($this->models);
        reset($this->models);

        switch ($this->type) {
            case self::ORDER:
                $message = "Orders batch #" . $this->batch++ . " containing "
                        . count($this->models) . " orders (" . $first['order_number'] . " - "
                        . $last['order_number'] . ")";
                break;

            case self::CUSTOMER:
                $message = "Customers batch #" . $this->batch++ . " containing "
                        . count($this->models) . " customers (" . $first['email_address'] . " - "
                        . $last['email_address'] . ")";
                break;

            default:
                $helper->log("Nothing added to the queue");
                return;

        }

        $this->processQueueWithMessage($message);
    }

    /**
     * Process queue with message
     *
     * @param $message
     */
    protected function processQueueWithMessage($message)
    {
        $helper = $this->helper;

        switch ($this->type) {
            case self::ORDER:
                $queueAble = Mage::getModel('robinhq_hooks/queue_orders');
                break;

            case self::CUSTOMER:
                $queueAble = Mage::getModel('robinhq_hooks/queue_customer');
                break;

        }

        $helper->log($message . " added to the queue");

        /** @var Robinhq_Hooks_Model_Queue_Abstract $queueAble */
        $queueAble->setMessages($this->models);
        $queueAble->setName($message);
        $queueAble->enqueue();
    }

    /**
     * This enqueues the model and removes any old ones with the same message name
     * This is generally used by the observer since Magento has a knack of firing off multiple events during a single submit
     */
    protected function enqueueDeduplicate()
    {
        $helper = $this->helper;

        if (empty($this->models)) {
            // Nothing to do
            return;
        }

        // Get first model
        $model = $this->models[0];

        /** @var Jowens_JobQueue_Model_Resource_Job_Collection $jobs */
        $jobs = Mage::getResourceModel('jobqueue/job_collection');

        switch ($this->type) {
            case self::ORDER:
                $message = "Order " . $model['order_number'];
                break;

            case self::CUSTOMER:
                $message = "Customer " . $model['order_number'];
                break;

            default:
                $helper->log("Nothing added to the queue");
                return;

        }

        $jobs->addFieldToFilter('name', $message);
        $jobs->walk('delete');

        $this->processQueueWithMessage($message);
    }

    /**
     * Enqueue when the batch limit has been reached
     *
     * @return void
     */
    protected function enqueueWhenBatchLimitIsReached()
    {
        if (count($this->models) === $this->limit) {
            $this->enqueue();
            $this->reset();
        }
    }

    /**
     * Empty $this->models
     */
    protected function reset()
    {
        $this->models = [];
    }

}