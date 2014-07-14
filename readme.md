Magento Robin integration
==========================================================

This package is designed as a starting point and a way to show how you could use the Robin API in your Magento shop. It currently sends over data to Robin when a customer and/or order is created or updated through the frond- and back-end. The module is tested in Magento 1.7, 1.8 and 1.9.

## Setup

__Make sure you have your Robin API key and API secret. If you don't have the API secret, [You can request it here][request-api-key-secret]. You can also ask the Robin people what your API key is if you don't know where to find it.__

### Installation


- Clone or download this git repo
- If you downloaded the repo, unpack it
- Log in to your magento backend
- Go to System -> Tools -> Backups and click on `System Backup`, this might take a while. Sit back, relax en let Magento do the heavy lifting.
- Go to System -> Tools -> Compilation and disable compilation if it's enabled. 
- Go to System -> Cache Management and enable Configuration

From here you can make a choice, if you want to install the package though Magento Connect proceed, if not skip the next steps and follow the steps under __manual  installation__

- Go to System -> Magento Connect -> Magento Connect Manager and log in with your admin credentials
- Under the field `Direct package file upload` click `Choose file` and browse to the location where you downloaded/cloned this repo and go to `package/0.0.1 alpha` and select the file `Robinhq_Hooks-0.0.1.tgz`
- Click `Upload` to start the installation process
- After the installation is completed go back to the admin page

####Manual installation

- Connect to your hosting through FTP and browse to your Mageno root directory.
- On your local machine, go to the location where you have downloaded the module.
- Upload the folders `app` and `design` to the root directory of your Magento installation.
- When your FTP client asks you to override or combine existing folders, choose __combine__! Otherwise your entire Magento installation will be overwritten!

### What's installed?

The module gets installed inside the `app/code/community/Robinhq/Hooks` folder and the package settings file is located inside `app/etc/modules/Robin_Hooks.xml`. The admin design files are: `design/adminhtml/default/default/layout/Hooks.xml` and `design/adminhtml/default/default/template/hooks/hooksbackend.phtml`

### What's next

If you didn't get any errors, that's good! Now log out and back in again to be sure Magento takes note of the changes. You can now turn Compilation back on if you turned it off during installation. Also disable Configuration under Cache Management.
Again I want to make sure you have your [api key and secret][request-api-key-secret] otherwise the module __won't work!__

Now go to System -> Configuration -> ROBINHQ -> Settings -> API Settings and fill in your API key and API secret. The API base url is already provided for you. After you have filled in both key's click `save config` and you are done! All future orders and customers will be automatically send to Robin when they are created and/or changed.

### Initial data
You might have noticed the new `Robin` tab in your back-end menu bar. This is to send over existing customers and their orders to Robin. To start the process, simply go to Robin -> Mass Send and click on the `Run` button.

### Warnings and Logging
By default the package will log what's happening and give the admin page notifications when something went good or bad. To see the log file, log in to your web server through ssh and navigate to your magento root folder.

To see the contents of the log file do the following
```BASH
cd /your/magento/root
cd var/log/
cat Robinhq_Hooks.log
```
To live watch what's happening when you place an order you can do this
```BASH
cd /your/magento/root
cd var/log/
tail -f Robinhq_hooks.log
```

## Development

### The events

This module listens to the following events: 
- `customer_save_after`
- `sales_order_place_after`
- `sales_order_save_after`

####customer_save_after

This event gets called when a customer is created, it's information changed or when the customer places an order. On this event `Robinhq_Hooks_Model_Observer::customerHook` gets executed as defined in `etc/config.xml`

```xml
<config>
    <global>
        <events>
            <customer_save_after> <!-- identifier of the event we want to catch -->
                <observers>
                    <customer_save_after_handler> <!-- identifier of the event handler -->
                        <type>singleton</type> <!-- class method call type; valid are model, object and singleton -->
                        <class>hooks/observer</class> <!-- observers class alias -->
                        <method>customerHook</method>  <!-- observer's method to be called -->
                        <args></args> <!-- additional arguments passed to observer -->
                    </customer_save_after_handler>
                </observers>
            </customer_save_after>
        </events>
    </global>
</config>
```

####sales_order_place_after

This event gets called after an order is placed. On this event `Robinhq_Hooks_Model_Observer::orderPlacedHook` gets executed as defined in `etc/config.xml`

```xml
<config>
    <global>
        <events>
           <sales_order_place_after> <!-- identifier of the event we want to catch -->
               <observers>
                    <sales_order_place_after_handler> <!-- identifier of the event handler -->
                        <type>singleton</type> <!-- class method call type; valid are model, object and singleton -->
                        <class>hooks/observer</class> <!-- observers class alias -->
                        <method>orderPlacedHook</method>  <!-- observer's method to be called -->
                        <args></args> <!-- additional arguments passed to observer -->
                     </sales_order_place_after_handler>
               </observers>
            </sales_order_place_after>
        </events>
    </global>
</config>
```

####sales_order_save_after

This event gets called after an orders status changes or when any other type of order related  changes gets saved. On this event `Robinhq_Hooks_Model_Observer::orderStatusChanceHook` gets executed as defined in `etc/config.xml`

```xml
<config>
    <global>
        <events>
           <sales_order_save_after> <!-- identifier of the event we want to catch -->
                <observers>
                    <sales_order_save_after_handler> <!-- identifier of the event handler -->
                        <type>singleton</type> <!-- class method call type; valid are model, object and singleton -->
                        <class>hooks/observer</class> <!-- observers class alias -->
                        <method>orderStatusChanceHook</method>  <!-- observer's method to be called -->
                        <args></args> <!-- additional arguments passed to observer -->
                    </sales_order_save_after_handler>
                </observers>
                </sales_order_save_after>
        </events>
    </global>
</config>
```
###The Code

The class `Robinhq_Hooks_Model_Observer` is the entry class for all events that we are hooking into. From there the api gets called by using `Robinhq_Hooks_Model_Api::orders()` or Robinhq_Hooks_Model_Api::customers()`.

####Robinhq_Hooks_Model_Api::orders()

This method goes through all orders given to it, maps it to a RobinOrder as required by the [Robin API][robin-api] and sends it to the `orders` API endpoint.
To generate a RobinOrder the method `Robinhq_Hooks_Model_RobinOrder::factory()` is used. This method collects order details like the products, shipments and invoices and formats them according to the `details_view` object required by the [Robin API][robin-api].

```JSON
{
  "orders": 
  [
    {
      "email_address":  "email@address.com",
      "order_number":  "RHQO1234",
      "url":  "http://shop.com/order/RHQO1234",
 
      "list_view": 
      {
         "order_number":  "RHQO1234",
         "date":  "29-01-2014 12:34",
         "status":  "In progress"
      },
      "details_view": 
      [    
        {
          "display_as": "details",      
          "data": 
          {
             "date":  "29-01-2014 12:34",
             "status":  "In progress",
             "payment_status":  "Partially paid",
             "shipment_status":  "Partially shipped"
          }
        }, 
        {
          "display_as": "columns",
          "caption": "products",
          "data" :
          [
            {
              "product":  "iPhone 5S",
              "quantity":  "1",
              "price":  "$695.50"
            },
            {
              "product":  "iPad 3",
              "quantity":  "2",
              "price":  "$898.00"
            },
            {
              "product":  "Shipment",
              "quantity":  "",
              "price":  "$10.00"
            },
            {
              "product":  "Total",
              "quantity":  "",
              "price":  "$1603.50"
            }
          ]
        },
        {
          "display_as": "rows",
          "caption": "shipments",
          "data":           
          [
            {
              "shipment":  
                 "<a href=' http://shop.com/shipment/RHQS01'>RHQS01</a>",
              "status":  "Shipped"
            },
            {
              "shipment":  
                 "<a href=' http://shop.com/shipment/RHQS02'>RHQS02</a>",
              "status":  "Not shipped"
            }
          ]
        },
        {
          "display_as": "rows",
          "caption": "invoices",
          "data":           
          [
            {
              "shipment":  
                 "<a href=' http://shop.com/invoice/RHQI01'>RHQI01</a>",
              "status":  "Paid",
              "amount":  "$1200.00"
            },
            {
              "shipment":  
                 "<a href=' http://shop.com/invoice/RHQI02'>RHQI02</a>",
              "status":  "Not paid",
              "amount":  "$403.50"
            }
          ]
        }
      ]
    }
  ]
}
```

####Robinhq_Hooks_Model_Api::customers()

This method goes through all customers given to it and maps them to a RobinCustomer as required by the [Robin API][robin-api] and sends them to the `customers` API endpoint. To generate a RobinCustomer the method `Robinhq_Hooks_Model_RobinCustomer::factory()` is used. This method makes a customer object required by the [Robin API][robin-api].

```JSON
{
      "email_address":  "email@address.com",
      "customer_since":  "2014-01-28",
      "order_count":  12,
      "total_spent":  "$154.95",
      "panel_view":  
      {
         "Orders":  "12",
         "Total_spent":  "$154.95",
      }
}
```
The `panel_view` contains a new object of key/value pairs that can be anything. As an example I've added the total orders and the total spent. How to use the panel_view array is explained in the [Robin API][robin-api] documentation.

####Adding more order data to send to Robin

You can extend the data you want to be visible inside Robin by adding key/value pairs to the `list_view` or the `data` arrays each method inside `Robinhq_Hooks_Model_RobinOrder::getDetailsView()` returns. 
If you want to add a complete new `details_view` like the comments from a order, you can add the logic inside the `Robinhq_Hooks_Model_RobinOrder::getDetailsView()` method like this

```PHP
private function getDetailsView(){
        $details = $this->getDetails();
        $productsOverview = $this->getProductsOverview();
        $shipments = $this->getShipments();
        $invoices = $this->getInvoices();
        $comments = $this->getComments(); //new line of code!
        return array($details, $productsOverview, $shipments, $invoices, $comments); //the order of this array determines the order of the different panel views you have inside the Robin conversation
    }
```

Now create a new method inside Robinhq_Hooks_Model_RobinOrder called `getComments` and let it return a array with the comments data
with the following layout

```JSON
{
    "display_as":"rows",
    "caption": "Order Comments",
    "data": []
}
```
Inside the `data` key you can add an array with objects containing key/value pairs like this
```JSON
{
    "Date:":"Jun 24, 2014 2:35:41 AM",
    "Content:": "This is a comment from the admin",
    "Order Status:":"On Hold",
    "Customer Notified:":"Yes"
}
```

 Note: the name of the key will also be displayed, so be sure to format it nicely.




### Environment

This example code is all developed with the use of a Vagrant box. If you want to develop you own implementation based on this code, I strongly recommend to use a Vagrant box as a development environment as the setup is quick and easy. If you are new to Vagrant, please watch [this][magento-vagrant] little presentation to get you up to speed.

I recommend to use [this one][magento-vagrant-github]. Simply follow the instructions provided there and you should have a virtual Magento installation in no-time. 

####Git and Magento module development

The development of a Magento module that is a git repo can be hard. To get it working I've figured out the following.
Make a folder called `module` inside your Vagrant root and do a git clone inside that folder (`git clone [url] .`). Make sure it's inside the Vagrant root, because this will ensure the files are automatically accessible inside the Vagrant machine.

Next simply do the following commands in the folder where you ran `vagrant up` (if you haven't done that yet, please do it now and wait till it completes)
```bash
vagrant ssh
ln -s /vagrant/module/app/etc/modules/Robinhq_Hooks.xml /vagrant/www/magento/app/etc/modules/Robinhq_Hooks.xml
ln -s /vagrant/module/app/code/community/Robinhq/ /vagrant/www/magento/app/code/community/Robinhq
ln -s /vagrant/module/design/adminhtml/default/default/layout/hooks.xml /vagrant/www/magento/app/design/adminhtml/default/default/layout/hooks.xml
ln -s /vagrant/module/design/adminhtml/default/default/template/hooks/ /vagrant/www/magento/app/design/adminhtml/default/default/template/hooks
```
And enable symlinks by going to System -> Configuration -> ADVANCED -> Developer -> Template Settings and set `Allow Symlinks` to `Yes`.
The idea here is to create a separated folder that only contains the module files and folders and link that to the magento installation. This way your magento installation won't have to be a git repository and you won't have to do some weird .gitignore magic.

Now you can develop inside the `module` folder. All new files created inside `/vagrant/module/app/code/community/Robinhq/` and `/vagrant/module/design/adminhtml/default/default/template/hooks/` will also be inside the Magento installation.

##License
The code is licensed under the [Robin End-User Licence Agreement][robin-licence]

[magento-vagrant]: http://broderboy.github.io/vagrant-magento-presentation/#/
[magento-vagrant-github]: https://github.com/matthewsplant/magento-vagrant-puppet
[request-api-key-secret]: http://docs.robinhq.com/faq/api-secret-request/
[robin-api]: http://docs.robinhq.com/faq/robin-api/
[robin-licence]: http://robinhq.com/eula/