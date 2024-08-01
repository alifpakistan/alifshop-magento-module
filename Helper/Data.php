<?php

namespace AlifShop\AlifShop\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;

class Data
{
    const MODULE_NAME = "AlifShop_AlifShop";
    protected const ALIFSHOP_CONFIG_PATH = "payment/alifshop/";
    protected $urlBuilder;
    protected $storeManager;
    protected $composerJsonPath;
    protected $scopeConfig;
    protected $ruleResource;
    protected $customerSession;
    protected $productRepository;
    protected $imageHelper;
    protected $logger;
    protected $orderRepository;
    protected $orderStatusHistoryFactory;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        UrlInterface $urlBuilder,
        Rule $ruleResource,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->ruleResource = $ruleResource;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $moduleDir = $componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $this->composerJsonPath = $moduleDir . '/composer.json';
    }

    /**
     * Get Module current Composer version
     *
     * @return string
     */
    public function getVersionInfo()
    {
        $version = 'N/A';
        $composerJsonPath = $this->getComposerJsonPath();

        if (file_exists($composerJsonPath)) {
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            if (isset($composerJson['version'])) {
                $version = $composerJson['version'];
            }
        }

        return $version;
    }

    protected function getComposerJsonPath()
    {
        if (!file_exists($this->composerJsonPath)) {
            throw new FileSystemException(__('The composer.json file does not exist.'));
        }
        return $this->composerJsonPath;
    }

    /**
     * Get Current store currency store
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Get Store Base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->urlBuilder->getBaseUrl();
    }

    /**
     * Get endpoint's Store URL
     *
     * @return string
     */
    public function getStoreUrl($endpoint, $params = [])
    {
        $endpoint = ltrim($endpoint, '/');
        return $this->urlBuilder->getUrl($endpoint, $params);
    }

    /**
     * Get AlifShop Configuration values
     *
     * @return string
     */
    public function getAlifShopConfig($fieldName) {
        return $this->scopeConfig->getValue(
            self::ALIFSHOP_CONFIG_PATH . $fieldName,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if any discount is applied on the product
     *
     * @return boolean
     */
    public function hasCatalogPriceRule($product) {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $productId = $product->getId();

        $rulePrice = $this->ruleResource->getRulePrice(
            new \DateTime(),
            $websiteId,
            $customerGroupId,
            $productId
        );

        return $rulePrice !== false;
    }

    /**
     * Get Product Image URL from Order Item
     *
     * @return string
     */
    public function getOrderItemImageUrl($orderItem)
    {
        try {
            $product = $this->productRepository->getById($orderItem->getProductId());
            
            // Check if the product has an image
            $imageFile = $product->getImage();
            
            if ($imageFile && $imageFile != 'no_selection') {
                $imageUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                    ->setImageFile($imageFile)
                    ->getUrl();
            } else {
                $imageUrl = null;
            }
            
            return $imageUrl;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Add comment to order
     *
     * @return null
     */
    public function addCommentToOrder(OrderInterface $order, $comment)
    {
        $history = $this->orderStatusHistoryFactory->create();
        $history->setComment($comment);
        $history->setEntityName('order');
        $history->setStatus($order->getStatus());
        
        $order->addStatusHistory($history);

        $this->orderRepository->save($order);
    }
}