# AlifShop Payment Module

 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Installation
``composer require alifshop/module-alifshop``

### Detailed installation instruction via Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require alifshop/module-alifshop`
 - enable the module by running `php bin/magento module:enable AlifShop_AlifShop`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - AlifShop - payment/alifshop/*


## Specifications

 - Payment Method
	- AlifShop


## Attributes



