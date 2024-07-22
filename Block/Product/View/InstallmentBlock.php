<?php
namespace AlifShop\AlifShop\Block\Product\View;

use AlifShop\AlifShop\Helper\Data as AlifShopHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;

class InstallmentBlock extends Template
{
    protected $_helper;
    protected $registry;
    protected $priceCurrency;
    protected $numberOfInstallments = 3;

    public function __construct(
        AlifShopHelper $_helper,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        Template\Context $context,
        array $data = []
    ) {
        $this->_helper = $_helper;
        $this->registry = $registry;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    public function getProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    public function getFormattedPrice($priceAmount)
    {
        $product = $this->getProduct();
        if ($product) {
            return $this->priceCurrency->format(
                $priceAmount,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $product->getStore()
            );
        }
        return null;
    }

    public function getInstallmentAmount() {
        $product = $this->getProduct();
        return $this->getFormattedPrice($product->getFinalPrice() / $this->numberOfInstallments);
    }

    public function getInstallmentInfo() {
        $installmentInfo = $this->_helper->getAlifShopConfig("product_page_installement_instructions");
        return $installmentInfo;
    }
    
    public function canShowBlock() {
        $product = $this->getProduct();
        return $this->getProduct() 
            && $this->getInstallmentInfo()
            && !$this->_helper->hasCatalogPriceRule($product);
    }

    public function getLogoUrl() {
        return $this->getViewFileUrl('AlifShop_AlifShop::images/alif-shop.svg');
    }

}