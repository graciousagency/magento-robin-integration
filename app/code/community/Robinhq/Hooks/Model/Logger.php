<?php


/**
 * Class Robinhq_Hooks_Model_Logger
 */
class Robinhq_Hooks_Model_Logger {

    /**
     * Logs the given string to var/log/Robinhq_Hooks.log
     * @param $string
     */
    public static function log($string) {

        Mage::log($string, null, 'Robinhq_Hooks.log');
//        if (defined('STDIN')) {
//            echo 'Magento [Robinhq Mass Sender]: ' . $string . "\n";
//        }
    }

    /**
     * Logs the given string/array to var/log/Robinhq_Hooks-debug.log
     *
     * Only use for debugging purposes! Like logging objects/arrays and
     * other information you need to check during development.
     * @param $value
     */
    public static function debug($value) {

        Mage::log($value, null, "Robinhq_Hooks-debug.log");
    }
}