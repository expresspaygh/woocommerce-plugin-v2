<p align="center">
  <img src="https://expresspaygh.com/images/logo.png" />
</p>
<br/>

# Expresspay Wordpress Widget

A simple plugin based on woocommerce for Wordpress integrators, this plugin works provided you are using woocommerce for your store.

------------------

# Requirements
* PHP: 7.0
* Wordpress 5.2 or later, tested up to 5.5
* WooCommerce 4.4.1 or later

------------------

# Install

* Install wordpress for your enviroment - [Wordpress](https://wordpress.org/download/)
* Install woocommerce for your enviroment - [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* Activate both `WooCommerce` and `WooCommerce Services`

------------------

# Upload and Activate

After installation, on your admin portal kindly do the following:

* Head to `Plugins` -> `Add New` -> `Upload Plugin`.
* From the root of this project you will find a compressed file `woocommerce-expresspay-plugin-v2.zip`, download if you already haven't and upload the file.
* After upload is complete, goto `Plugins` -> `Installed Plugins`
* Look through the list for `WooCommerce Expresspay Gateway`, click on `Activate`.
* After activation is complete, click on `Configure`, this takes you to the WooCommerce settings page.

------------------

# Setup for use

* On the `WooCommerce Expresspay Gateway` setup page, go through and fill out your merchant details and make amends where you see fit.
* After the above is complete, head to the `General` tab on the same page.
* Scroll to the `Currency options` section and set `Ghana Cedi (GHS)` as your main currency.
* Now visit your site, add some items to your cart and checkout using [expressPay](https://www.expresspaygh.com)
* All done!

-------------------

# Demo

* Install docker for your environment - [Docker](https://www.docker.com/get-started)
* Clone this repository to your environment
* Head to your preferred terminal and change directory into the root of this project
* Run `make run-dev` to boot and run in log mode
* Run `make daemon` to boot and run in the background
* Run `make help` to view more command option


----------------------

Copyright 2020, All rights reserved. [Expresspay Ghana Limited](https://expresspaygh.com)
