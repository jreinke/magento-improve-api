## Overview

This extension improves default features of Magento API.

It allows you to:

* Associate simple products to configurable or grouped product;
* Retrieve simple products from configurable products;
* Retrieve configurable attributes used in configurable products;
* Specify category names rather than the ids;
* Specify the name of the attribute set rather than the id;
* Specify options labels rather than the ids;
* Specify the website code rather than the id.

## Installation

### Magento CE 1.6.x, 1.7.x (retrieval of simple products and configurable attributes worked on 1.5.x)

Install with [modgit](https://github.com/jreinke/modgit):

    $ cd /path/to/magento
    $ modgit init
    $ modgit clone bubble-api https://github.com/jreinke/magento-improve-api.git

or download package manually:

* Download latest version [here](https://github.com/jreinke/magento-improve-api/archive/master.zip)
* Unzip in Magento root folder
* Clear cache

## How to associate simple products to configurable/grouped product

Please refer to [this article](http://www.bubblecode.net/en/2012/04/20/magento-api-associate-simple-products-to-configurable-or-grouped-product/).

## How to retrieve simple products and used configurable attributes from configurable products

Consume the "catalogProductInfo" method from the SOAP API on neither  V1 and V2 versions.

The simple products list will be on the "associated_skus" attribute and the configurable attributes list will be on the "configurable_attributes" attribute.
