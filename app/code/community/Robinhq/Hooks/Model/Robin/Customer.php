<?php


/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 16/06/14
 * Time: 17:10
 */
class Robinhq_Hooks_Model_Robin_Customer {

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
    public function factory(Mage_Customer_Model_Customer $customer) {

        $this->customer = $customer;
        return $this->make();
    }

    /**
     * Makes the array the Robin API expects. If you want more
     * info in the panel view, just add it in the $robinCustomer['panel_view'] array
     *
     * @return array
     */
    private function make() {

        $lifetime = $this->getLifeTimeSalesCustomer();
        $formattedTotalSpent = Mage::helper('core')->currency($lifetime->getLifetime(), true, false);
        $phoneNumber = $this->getCustomerPhoneNumber();
        $orderCount = $lifetime->getNumOrders();
        $rewardPoints = Mage::helper('hooks')->getRewardPoints($this->customer);

        $this->data = [
           'email_address'  => $this->customer->getEmail(),
           'customer_since' => Mage::getModel('core/date')->date('Y-m-d', strtotime($this->customer->getCreatedAt())),
           'order_count'    => $orderCount,
           'total_spent'    => $formattedTotalSpent,
           'panel_view'     => [
               'Reward_points' => $rewardPoints,
           ],
           'name'           => $this->customer->getName(),
           'currency'       => Mage::app()->getStore()->getCurrentCurrencyCode(),
           'phone_number'   => $phoneNumber,
           'reward_points'  => $rewardPoints,
        ];
        return $this->data;
    }

    /**
     * Gets customer statics like total order count and total spend.
     *
     * @return array
     */
    public function getLifeTimeSalesCustomer() {

        $totals = Mage::getResourceModel('sales/sale_collection')
            ->setCustomerFilter($this->customer)
            ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
            ->load()
            ->getTotals()
        ;
        return $totals;
    }

    /**
     * Returns the phone number. When getBillingTelephone returns null
     * it loads the default billing address and retrieves the telephone
     * number from there. When both are null, it'll return an emtpy string.
     *
     * @return string
     *
     */
    private function getCustomerPhoneNumber() {
        $phone = '';
        $address = Mage::getModel('customer/address');
        $billing = $address->load($this->customer->getDefaultBilling());
        $countryId = $billing->getCountryId();
        $phone = $billing->getTelephone();
        $phone = Mage::helper('hooks')->formatPhoneNumber($phone, $countryId);
        return $phone;
    }

}
