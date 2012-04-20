## Overview

This extension improves default features of Magento API.

It allows you to:

* Associate simple products to configurable or grouped product;
* Specify category names rather than the ids;
* Specify the name of the attribute set rather than the id;
* Specify options labels rather than the ids;
* Specify the website code rather than the id.

## Installation

### Magento CE 1.6+

Install with [modgit](https://github.com/jreinke/modgit):

    $ cd /path/to/magento
    $ modgit init
    $ modgit -e README.md clone jr-api https://github.com/jreinke/magento-improve-api.git

or download package manually:

* Download latest version [here](https://github.com/jreinke/magento-improve-api/downloads)
* Unzip in Magento root folder
* Clean cache

## How to associate simple products to configurable/grouped product

I wrote an article for this, [click here](http://www.johannreinke.com/en/2012/04/20/magento-api-associate-simple-products-to-configurable-or-grouped-product/).