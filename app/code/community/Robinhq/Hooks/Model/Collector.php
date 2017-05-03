<?php

class Robinhq_Hooks_Model_Collector
{
    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;

    /**
     * The number of items per page in a collection
     * @var int
     */
    protected $pagination;

    /**
     * @param Robinhq_Hooks_Helper_Data $helper
     */
    public function __construct(
            Robinhq_Hooks_Helper_Data $helper
    ) {
        $this->helper = $helper;
        $this->pagination = +$helper->getConfig('select_limit');
    }

    public function orders()
    {
        $queue = $this->helper->getQueue();
        $queue->setType($queue::ORDER);

        $collection = $this->getOrdersPaginated($this->pagination);
        $this->walk($collection, 'orderCallback');
    }

    public function customers()
    {
        $queue = $this->helper->getQueue();
        $queue->setType($queue::CUSTOMER);

        $collection = $this->getCustomersPaginated($this->pagination);
        $this->walk($collection, 'customerCallback');
    }

    /**
     * @param Varien_Data_Collection $collection
     * @param array $callback
     */
    protected function iterate($collection, array $callback)
    {
        $array = $collection->toArray();
        $array = (array_key_exists('items', $array)) ? $array['items'] : $array;
        array_map($callback, $array);
    }

    /**
     *
     * @param $customerData
     */
    public function customerCallback($customerData)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setData($customerData);

        $helper = $this->helper;
        $customer = $helper->getConverter()
                ->toRobinCustomer($customer);
        $helper->getQueue()
                ->push($customer);
    }

    /**
     * This method is preformed on each order
     *
     * @param $orderData
     */
    protected function orderCallback($orderData)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')
                ->load($orderData['entity_id'], 'entity_id');

        $email = $order->getData('customer_email');
        if (empty($email)) {
            return;
        }

        $helper = $this->helper;
        $order = $helper->getConverter()
                ->toRobinOrder($order);
        $helper->getQueue()
                ->push($order);
    }

    /**
     * @return Mage_Sales_Model_Order[]|Varien_Data_Collection
     */
    protected function getOrdersPaginated($size)
    {
        return Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('customer_email')
                ->setOrder('increment_id', 'DESC')
                ->addFieldToFilter('customer_email', ['notnull' => true])
                ->setPageSize($size);
    }

    /**
     * @return Mage_Customer_Model_Customer[]|Varien_Data_Collection
     */
    protected function getCustomersPaginated($size)
    {
        return Mage::getModel('customer/customer')
                ->getCollection()
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->addAttributeToSelect('created_at')
                ->addFieldToFilter('email', ['notnull' => true])
                ->setPageSize($size);
    }

    /**
     * @param Varien_Data_Collection $collection
     * @param string $callback
     */
    protected function walk(Varien_Data_Collection $collection, $callback)
    {
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

        $this->helper
                ->getQueue()
                ->clear();
    }

}
