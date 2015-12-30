<?php


class Robinhq_Hooks_Model_Collector {

    /**
     * @var Robinhq_Hooks_Model_Queue
     */
    private $queue;
    /**
     * @var Robinhq_Hooks_Model_Robin_Converter
     */
    private $converter;

    /**
     * The number of items per page in a collection
     * @var int
     */
    private $pagination;

    /**
     * @param Robinhq_Hooks_Model_Queue $queue
     * @param Robinhq_Hooks_Model_Robin_Converter $converter
     * @param $pagination
     */
    public function __construct(Robinhq_Hooks_Model_Queue $queue, Robinhq_Hooks_Model_Robin_Converter $converter, $pagination) {

        $this->queue = $queue;
        $this->converter = $converter;
        $this->pagination = $pagination;
    }

    public function orders() {

        $this->queue->setType(Robinhq_Hooks_Model_Queue::ORDER);
        $collection = $this->getOrdersPaginated($this->pagination);
        $this->walk($collection, "orderCallback");
    }

    public function customers() {

        $this->queue->setType(Robinhq_Hooks_Model_Queue::CUSTOMER);
        $collection = $this->getCustomersPaginated($this->pagination);
        $this->walk($collection, 'customerCallback');
    }

    /**
     * @param $collection
     * @param array $callback
     */
    private function iterate($collection, array $callback) {

        $array = $collection->toArray();
        $array = (array_key_exists('items', $array)) ? $array['items'] : $array;
        array_map($callback, $array);
    }

    /**
     *
     * @param $customerData
     */
    private function customerCallback($customerData) {

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setData($customerData);
        $customer = $this->converter->toRobinCustomer($customer);
        $this->queue->push($customer);
    }

    /**
     * This method is preformed on each order
     *
     * @param $orderData
     */
    private function orderCallback($orderData) {

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')
                     ->load($orderData['entity_id'], 'entity_id')
        ;
        if (!empty($order->customer_email)) {
            $order = $this->converter->toRobinOrder($order);
            $this->queue->push($order);
        }
        $order = null;
    }

    /**
     * @return Mage_Sales_Model_Order[]|Varien_Data_Collection
     */
    private function getOrdersPaginated($size) {

        return Mage::getModel('sales/order')
                   ->getCollection()
                   ->addAttributeToSelect('entity_id')
                   ->addAttributeToSelect('customer_email')
                   ->setOrder('increment_id', 'DESC')
                   ->addFieldToFilter('customer_email', ['null' => false])
                   ->setPageSize($size)
            ;
    }

    /**
     * @return Mage_Customer_Model_Customer[]|Varien_Data_Collection
     */
    private function getCustomersPaginated($size) {

        return Mage::getModel('customer/customer')
                   ->getCollection()
                   ->addNameToSelect()
                   ->addAttributeToSelect('email')
                   ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                   ->addAttributeToSelect('created_at')
                   ->addFieldToFilter('email', ['null' => false])
                   ->setPageSize($size)
            ;
    }

    /**
     * @param $collection
     * @param $callback
     */
    private function walk(Varien_Data_Collection $collection, $callback) {

        $size = $collection->getLastPageNumber();
        for ($i = 1; $i <= $size; $i++) {
            $collection->setCurPage($i);
            $collection->load();
            $this->iterate($collection, [
                $this,
                $callback,
            ]);
            $collection->clear();
        }
        $this->queue->clear();
    }
}
