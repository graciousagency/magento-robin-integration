<?php

/**
 * Created by PhpStorm.
 * User: WouterSteenmeijer
 * Date: 25/04/2017
 * Time: 16:50
 * I'm completely operational, and all my circuits are functioning perfectly. - HAL 9000
 */
class Robinhq_Hooks_Block_Hooks extends Mage_Catalog_Block_Product_Abstract
{

    /**
     * Return a image url
     *
     * @return string
     */
    public function getImageUrl () {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "media/catalog/product" . $this->getProduct()->getImage();
    }


    /**
     * Return based on product type a stock string to display in robinhq backend
     *
     * @return string
     */
    public function getStockStatus() {

        $product = $this->getProduct();

        switch ($product->getTypeId()) {

            case 'configurable':

                $childStock = array();
                foreach ($product->getTypeInstance(true)->getUsedProducts(null, $product) as $_simple) {
                    $stockQty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_simple)->getQty();
                    $childStock[] = $_simple->getAttributeText('size') . ": " . ($stockQty > 0 ? 'In' : 'Not in') . " Stock (" . $stockQty . ") ";
                }

                $stockText = implode($childStock);

                return $stockText;

            case 'simple':
                // Product is a simple,
                if ($product->getIsInStock()) {
                    return "Op voorraad";
                }
                return "Niet op voorraad";

            default:
                return "Niet op voorraad";
        }

    }

}