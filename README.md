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

 - In Magento Admin go to `STORES > Configuration > SALES > Payment Methods > OTHER PAYMENT METHODS > AlifShop` and set your Alif Shop token in `Cashbox Token` Field and then press `Save Config` 


## Specifications

 - Payment Method
	- AlifShop
