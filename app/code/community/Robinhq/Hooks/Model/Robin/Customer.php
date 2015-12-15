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
     * @var Mage_Customer_Model_Customer
     */
    private $customer;

    private $data;

    /**
     * Factory method for creating an array with key/value pairs
     * the Robin API expects.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function factory(Mage_Customer_Model_Customer $customer)
    {
        $this->customer = $customer;

        return $this->make();
    }

    /**
     * Makes the array the Robin API expects. If you want more
     * info in the panel view, just add it in the $robinCustomer['panal_view'] array
     *
     * @return array
     */
    private function make()
    {
        $lifetime = $this->getLifeTimeSalesCustomer();
        $formattedTotalSpend = Mage::helper('core')->currency($lifetime->getLifetime(), true, false);

        $phoneNumber = $this->getCustomerPhoneNumber();
        $orderCount = $lifetime->getNumOrders();
        $rewardPoints = $this->getRewardPoints();

        $this->data = [
            'email_address'     => $this->customer->getEmail(),
            'customer_since'    => Mage::getModel('core/date')->date('Y-m-d', strtotime($this->customer->getCreatedAt())),
            'order_count'       => $orderCount,
            'total_spent'       => $formattedTotalSpend,
            "panel_view" => [
                'Reward_points' => $rewardPoints,
            ],
            'name'              => $this->customer->getName(),
            'currency'          => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'phone_number'      => $phoneNumber,
            'reward_points'     => $rewardPoints,
        ];
        return $this->data;
    }

    private function getRewardPoints() {
        $allStores = Mage::app()->getStores();
        $points = 0;
        if($this->customer->getId())    {
            foreach ($allStores as $_eachStoreId => $val) {
                $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
                if (Mage::getStoreConfig('rewardpoints/default/flatstats', $_storeId)) {
                    $reward_flat_model = Mage::getModel('rewardpoints/flatstats');
                    $points += $reward_flat_model->collectPointsCurrent($this->customer->getId(), $_storeId) + 0;
                } else {
                    $reward_model = Mage::getModel('rewardpoints/stats');
                    $points += $reward_model->getPointsCurrent($this->customer->getId(), $_storeId) + 0;
                }
            }
        }
        return $points;
    }


    /**
     * Gets customer statics like total order count and total spend.
     *
     * @return array
     */
    public function getLifeTimeSalesCustomer()
    {
        return Mage::getResourceModel('sales/sale_collection')
            ->setCustomerFilter($this->customer)->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
            ->load()
            ->getTotals();
    }

    /**
     * @return string
     *
     * Returns the phone number. When getBillingTelephone returns null
     * it loads the default billing address and retrieves the telephone
     * number from there. When both are null, it'll return an emtpy string.
     */
    private function getCustomerPhoneNumber()
    {
        $phoneNumber = $this->customer->getBillingTelephone();
        $phoneNumber = ($phoneNumber !== null) ? $phoneNumber : $this->getPhoneNumberFromBilling();

        return ($phoneNumber === null) ? "" : $phoneNumber;
    }

    private function getPhoneNumberFromBilling()
    {
        $address = Mage::getModel('customer/address');
        $billing = $address->load($this->customer->getDefaultBilling());
        $phone = $billing->getTelephone();

        return $phone;
    }

    /**
     * @return string
     *
     * Returns the twitter handler
     */
    private function getTwitterHandler()
    {
        return ($this->customer->getTwitterHandler() === null) ? "" : $this->customer->getTwitterHandler();
    }



}
