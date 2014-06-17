<?php


/**
 * Class Robinhq_Hooks_Model_RobinOrder
 */
class Robinhq_Hooks_Model_RobinOrder {

    /**
     * @var Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @var Mage_Sales_Model_Resource_Order_Shipment_Collection
     */
    private $shipments;

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;


    /**
     * Factory method for creating an array with data that is
     * required by the Robin API. Also adds a example 'details_view' key
     * listing the products and shipments. To add extra fields, read up on
     * the documentation on http://docs.robinhq.com/faq/robin-api/ how to format.
     *
     * Extra details_view's can be added inside the getDetailsView method.
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function factory(Mage_Sales_Model_Order $order){
        $this->order = $order;
        $this->shipments = $this->order->getShipmentsCollection();
        $this->logger = Mage::getModel('hooks/logger');
        return $this->make();
    }

    /**
     * Starts the construction of the robinOrder array by generating
     * the base and getting the details view.
     *
     * @return array
     */
    private function make(){
        $base = $this->getBaseInfo();
        $base['details_view'] = $this->getDetailsView();
        $this->logger->debug($base);
        return $base;
    }

    /**
     * Wrapper method for generating all the child elements for the 'details_view'
     * key. If you want to add your own 'details_view' field, write a method for it
     * and add a call to it here like: $infoName = $this->getInfos();. Then add
     * $infoName to the array that's returned.
     *
     * @return array
     */
    private function getDetailsView(){
        $details = $this->getDetails();
        $productsOverview = $this->getProductsOverview();
        $shipments = $this->getShipments();
//        $invoices = $this->getInvoices();
        return array($details, $productsOverview, $shipments);
    }

    /**
     * Gets the base info for a order, contains the required parts
     * for the Robin API.
     * @return array
     */
    private function getBaseInfo(){
        return array(
            "order_number" => $this->order->getIncrementId(),
            "email_address" => $this->order->getCustomerEmail(),
            "url" => $this->getOrderUrl(),
            "list_view" => array(
                "order_number" => $this->order->getIncrementId(),
                "date" => $this->order->getCreatedAt(),
                "status" => $this->order->getStatus(),
            ),
        );
    }

    /**
     * Gets the Details view as first item of the 'details_view' key
     * @return array
     */
    private function getDetails(){
        $payment = $this->order->getPayment()->getMethodInstance();
        return array(
            "display_as" => "Details",
            "data" => array(
                "date" => $this->order->getCreatedAt(),
                "status" => $this->order->getStatus(),
                "payment_method" => $payment->getTitle(),
                "payment_status" => $payment->getStatus(),
            )
        );
    }

    /**
     * Gets all products of the order. Displays the name, quantity, price and status.
     * Also gets shipment and payment info to generate a total view of the order.
     *
     * @return array
     */
    private function getProductsOverview(){
        $base = array(
            "display_as" => 'columns',
            "caption" => 'products',
            "data" => array()
        );

        $base['data'] = $this->getProducts($base);

        $base['data'][] = $this->getShipmentInfo();

        $base['data'][] =  $this->getOrderTotalInfo();

        return $base;
    }

    /**
     * When there are shipments, it lists them and their status.Other
     * Otherwise the 'data' key stays empty.
     * @return array
     */
    private function getShipments(){
        $base = array(
            "display_as" => 'rows',
            "caption" => 'shipments',
            "data" => array()
        );
        if($this->shipments !== false){
            foreach($this->shipments as $shipment){
                $this->logger->debug($shipment->getData());
                $url = $this->getShipmentUrl($shipment);
                $base['data'][] = array(
                    "shipment" => "<a target='_blank' href='" . $url . "'>". $shipment->getIncrementId() ."</a>",
                    "status" => $shipment->getShipmentStatus()
                );
            }
        }

        return $base;
    }

    /**
     * Should get the invoices, did not get it working in my test shop.
     *
     * @return array
     */
    private function getInvoices(){
        $base = array(
            "display_as" => 'rows',
            "caption" => 'invoices',
            "data" => array(),
        );

        if($this->order->hasInvoices()){
            foreach($this->order->getInvoiceCollection() as $invoice){
                $this->logger->debug($invoice->getData());
            }
        }

        return $base;
    }

    /**
     * @return array
     */
    private function getProducts()
    {
        $products = array();
        $items = $this->order->getAllItems();
        //All products from this order
        foreach ($items as $item) {
            $products[] = array(
                "product" => $item->getName(),
                "quantity" => $item->getQtyOrdered(),
                "price" => Mage::helper('core')->currency($item->getPrice(), true, false),
                "status" => $item->getStatusLabel()
            );
        }
        return $products;
    }

    /**
     * @return array
     */
    private function getShipmentInfo()
    {
        return array(
            "Product" => "Shipment",
            "quantity" => '',
            "price" => Mage::helper('core')->currency($this->order->getBaseShippingAmount(), true, false),
        );
    }

    /**
     * @return array
     */
    private function getOrderTotalInfo()
    {
        return array(
            "Product" => "Total",
            "quantity" => '',
            "price" => Mage::helper('core')->currency($this->order->getBaseGrandTotal(), true, false),
        );
    }

    /**
     * @return mixed
     */
    private function getOrderUrl()
    {
        return Mage::helper('adminhtml')->getUrl(
            'adminhtml/sales_order/view',
            array('order_id' => $this->order->getIncrementId(), '_type' => Mage_Core_Model_Store::URL_TYPE_WEB)
        );
    }

    /**
     * @param $shipment
     * @return mixed
     */
    private function getShipmentUrl($shipment)
    {
        return Mage::helper('adminhtml')
            ->getUrl('adminhtml/sales_order_shipment/view', array('shipment_id' => $shipment->getId(), '_type'=> Mage_Core_Model_Store::URL_TYPE_WEB));
    }
} 