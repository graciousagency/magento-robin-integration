<?php


abstract class Robinhq_Hooks_Model_Queue_Abstract extends Jowens_JobQueue_Model_Job_Abstract
{

    /** @var array */
    protected $messages = [];

    /**
     * @return string
     */
    abstract public function getAction();

    /**
     * Get API
     *
     * @return Robinhq_Hooks_Model_Api
     */
    public function getApi()
    {
        return Mage::helper('robinhq_hooks')
                ->getApi();
    }

    /**
     * Set messages to process
     *
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Execute
     */
    public function perform()
    {
        usleep(500000);

        $action = $this->getAction();
        $this->getApi()
                ->$action($this->messages);
    }

}