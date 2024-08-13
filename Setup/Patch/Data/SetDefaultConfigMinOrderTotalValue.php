<?php

declare(strict_types=1);

namespace AlifShop\AlifShop\Setup\Patch\Data;

use AlifShop\AlifShop\Helper\Data;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetDefaultConfigMinOrderTotalValue implements DataPatchInterface
{
    protected $_helper;

    public function __construct(
        Data $_helper
    ) {
        $this->_helper = $_helper;
    }

    public function apply()
    {
        $this->_helper->updateMinOrderTotal();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}