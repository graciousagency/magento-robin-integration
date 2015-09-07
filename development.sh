#!/bin/sh
ln -s /vagrant/module/app/etc/modules/Robinhq_Hooks.xml /vagrant/www/magento/app/etc/modules/Robinhq_Hooks.xml
ln -s /vagrant/module/app/code/community/Robinhq/ /vagrant/www/magento/app/code/community/Robinhq
ln -s /vagrant/module/app/design/adminhtml/default/default/layout/hooks.xml /vagrant/www/magento/app/design/adminhtml/default/default/layout/hooks.xml
ln -s /vagrant/module/app/design/adminhtml/default/default/template/hooks/ /vagrant/www/magento/app/design/adminhtml/default/default/template/hooks
echo "Sucessfully linked all files for development"