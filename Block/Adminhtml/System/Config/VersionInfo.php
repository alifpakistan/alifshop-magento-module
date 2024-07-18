<?php

namespace AlifShop\AlifShop\Block\Adminhtml\System\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Filesystem\DirectoryList;
use AlifShop\AlifShop\Helper\Data as AlifShopHelper;

class VersionInfo extends Value
{
    protected $_helper;

    public function __construct(
        AlifShopHelper $_helper
    ) {
      $this->_helper = $_helper;  
    }

    public function afterLoad()
    {
        $version = $this->_helper->getVersionInfo();
        if ($version) {
            $this->setValue($version);
        } else {
            $this->setValue('N/A');
        }
    }
}