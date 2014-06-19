<?php


class Robinhq_Hooks_Block_Adminhtml_Test extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_controller = 'hooksbackend_index';
        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save Account'));
        $this->_removeButton('delete');
        $this->_removeButton('back');
    }

    public function getHeaderText()
    {
        return Mage::helper('adminhtml')->__('My Account');
    }
}
