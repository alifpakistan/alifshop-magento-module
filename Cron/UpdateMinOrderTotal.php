<?php
namespace AlifShop\AlifShop\Cron;

class UpdateMinOrderTotal
{
    protected $_helper;

    public function __construct(
        \AlifShop\AlifShop\Helper\Data $_helper
    ) {
        $this->_helper = $_helper;
    }

    public function execute()
    {
        $this->_helper->updateMinOrderTotal();
    }
}