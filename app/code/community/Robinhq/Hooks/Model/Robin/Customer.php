<?php

/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 16/06/14
 * Time: 17:10
 */
class Robinhq_Hooks_Model_Robin_Customer
{

    /**
     * Factory method for creating an array with key/value pairs
     * the Robin API expects.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function factory(Mage_Customer_Model_Customer $customer)
    {
        /** @var Robinhq_Hooks_Helper_Data $helper */
        $helper = Mage::helper('robinhq_hooks');

        $lifetime = $this->getLifeTimeSalesCustomer($customer);
        $latestOrder = $this->getLatestOrder($customer);

        $formattedTotalSpent = Mage::helper('core')
                ->currency($lifetime->getLifetime(), true, false);

        $phoneNumber = $this->getCustomerPhoneNumber($customer, $helper);
        $orderCount = $lifetime->getNumOrders();

        $rewardPoints = $helper->getRewardPoints($customer);

        return [
                'email_address' => trim($customer->getEmail()),
                'customer_since' => Mage::getModel('core/date')
                        ->date('Y-m-d', strtotime($customer->getCreatedAt())),
                'order_count' => $orderCount,
                'total_spent' => $formattedTotalSpent,
                'total_revenue' => $lifetime->getLifetime(),
                'panel_view' => [
                        'Reward_points' => $rewardPoints,
                ],
                'name' => $customer->getName(),
                'currency' => Mage::app()
                        ->getStore()
                        ->getCurrentCurrencyCode(),
                'phone_number' => $phoneNumber,
                'reward_points' => $rewardPoints,
                'latest_order_date' => Mage::getModel('core/date')
                        ->date('Y-m-d', strtotime($latestOrder->getCreatedAt()))
        ];
    }

    /**
     * Gets customer statics like total order count and total spend.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Varien_Object
     */
    public function getLifeTimeSalesCustomer(Mage_Customer_Model_Customer $customer)
    {
        /** @var Mage_Sales_Model_Resource_Sale_Collection $collection */
        $collection = Mage::getResourceModel('sales/sale_collection');

        $collection->setCustomerFilter($customer)
                ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
                ->load();

        return $collection->getTotals();
    }

    /**
     * Returns the phone number. When getBillingTelephone returns null
     * it loads the default billing address and retrieves the telephone
     * number from there. When both are null, it'll return an emtpy string.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param Robinhq_Hooks_Helper_Data $helper
     * @return string
     */
    protected function getCustomerPhoneNumber(Mage_Customer_Model_Customer $customer, Robinhq_Hooks_Helper_Data $helper)
    {
        $billing = $customer->getDefaultBillingAddress();
        if (!$billing) {
            return '';
        }

        return $helper->formatPhoneNumber($billing->getTelephone(), $billing->getCountryId());
    }

    /**
     * Get the latest order placed with customer's email
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Sales_Model_Order|Varien_Object
     */
    protected function getLatestOrder(Mage_Customer_Model_Customer $customer)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_collection');

        $collection->addFieldToFilter('customer_email', $customer->getEmail())
            ->addAttributeToSort('created_at', 'DESC')
            ->setPageSize(1)
            ->load();

        return $collection->getFirstItem();
    }
}
