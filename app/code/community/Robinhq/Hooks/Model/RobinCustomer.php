<?php
/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 16/06/14
 * Time: 17:10
 */

class Robinhq_Hooks_Model_RobinCustomer {

    /**
     * @var Mage_Customer_Model_Customer
     */
    private $customer;

    /**
     * Factory method for creating an array with key/value pairs
     * the Robin API expects.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public function factory(Mage_Customer_Model_Customer $customer){
        $this->customer = $customer;
        return $this->make();
    }

    /**
     * Makes the array the Robin API expects. If you want more
     * info in the panel view, just add it in the $robinCustomer['panal_view'] array
     *
     * @return array
     */
    private function make(){
        $lifetime = $this->getLifeTimeSalesCustomer();
        $formattedTotalSpend = Mage::helper('core')->currency($lifetime->getLifetime(), true, false);

        $robinCustomer = array(
            "email_address" => $this->customer->getEmail(),
            "customer_since" => Mage::getModel('core/date')->date('Y-m-d', strtotime($this->customer->getCreatedAt())),
            "order_count" => $lifetime->getNumOrders(),
            "total_spent" => $formattedTotalSpend,
            "panel_view" => array(
                "Orders" => (string) $lifetime->getNumOrders(),
                "Total_spent" => $formattedTotalSpend
            )
        );

        return  $robinCustomer;
    }

    /**
     * Gets customer statics like total order count and total spend.
     *
     * @return array
     */
    function getLifeTimeSalesCustomer(){
        return Mage::getResourceModel('sales/sale_collection')
            ->setCustomerFilter($this->customer)
            ->load()
            ->getTotals();
    }
} 