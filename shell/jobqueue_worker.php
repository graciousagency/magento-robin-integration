<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

class jobqueueWorker{

    public function __construct()   {
        require '../app/Mage.php';
        umask(0);
        Mage::app('admin')->setUseSessionInUrl(false);
        Mage::getConfig()->init();
        $worker = new Jowens_JobQueue_Model_Worker();
        $worker->executeJobs();
    }

}

$jobqueueWorker = new jobqueueWorker();

