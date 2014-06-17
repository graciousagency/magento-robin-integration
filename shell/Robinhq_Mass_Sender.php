<?php
/**
 * Created by PhpStorm.
 * User: bwubs
 * Date: 16/06/14
 * Time: 14:43
 */
require_once 'abstract.php';

/**
 * Class Robinhq_Mass_Sender
 */
class Robinhq_Mass_Sender extends Mage_Shell_Abstract{

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $robin;

    /**
     * @var bool
     */
    protected  $_includeMage = true;

    /**
     * Gets and sets the dependency's
     */
    public function __construct() {
        parent::__construct();
        $this->robin = Mage::helper("hooks");
        // Time limit to infinity
        set_time_limit(0);
    }


    /**
     * Shell script point of entry
     */
    public function run(){
        try{
            $this->robin->sendCustomers();
            $this->robin->sendOrders();
        }
        catch(Exception $e){
            $this->robin->log($e->getMessage());
        }

    }

    /**
     * Usage instructions
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
        Sends all customers and their orders to the ROBIN API.

        Usage:  php -f Robinhq_Mass_Sender.php
        help                   This help
USAGE;
    }
}

$shell = new Robinhq_Mass_Sender();

$shell->run();