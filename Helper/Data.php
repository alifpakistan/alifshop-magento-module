<?php

namespace AlifShop\AlifShop\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
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
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
class Data
{
    const MODULE_NAME = "AlifShop_AlifShop";
    protected const ALIFSHOP_CONFIG_PATH = "payment/alifshop/";
    protected $urlBuilder;
    protected $storeManager;
    protected $composerJsonPath;
    protected $scopeConfig;
    protected $configWriter;
    protected $ruleResource;
    protected $customerSession;
    protected $productRepository;
    protected $imageHelper;
    protected $logger;
    protected $orderRepository;
    protected $orderStatusHistoryFactory;
    protected $curl;
    protected $cache;
    protected $serializer;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        UrlInterface $urlBuilder,
        Rule $ruleResource,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory,
        Curl $curl,
        CacheInterface $cache,
        SerializerInterface $serializer,
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->ruleResource = $ruleResource;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->curl = $curl;
        $this->cache = $cache;
        $this->serializer = $serializer;
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
    public function getAlifShopConfig($fieldName)
    {
        return $this->scopeConfig->getValue(
            self::ALIFSHOP_CONFIG_PATH . $fieldName,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Set AlifShop Configuration values
     *
     * @return void
     */
    public function setAlifShopConfig($fieldName, $fieldVal)
    {
        return $this->configWriter->save(
            self::ALIFSHOP_CONFIG_PATH . $fieldName,
            $fieldVal
        );
    }

    /**
     * Check if any discount is applied on the product
     *
     * @return boolean
     */
    public function hasCatalogPriceRule($product)
    {
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
     * Check if product has a valid special price
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @return boolean
     */
    public function hasSpecialPrice($product) {
        // Check if the product is configurable
        if ($product->getTypeId() === 'configurable') {
            // Get associated simple products
            $associatedProducts = $product->getTypeInstance()->getUsedProducts($product);
            
            foreach ($associatedProducts as $simpleProduct) {
                if ($this->isSpecialPriceValid($simpleProduct)) {
                    return true;
                }
            }
        } else {
            // For simple products, check directly
            return $this->isSpecialPriceValid($product);
        }

        return false; // No valid special price found
    }

    /**
     * Check if a simple product has a valid special price
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @return boolean
     */
    private function isSpecialPriceValid($product) {
        $specialPrice = $product->getSpecialPrice();
        $regularPrice = $product->getPrice();

        // Check if special price is set and less than regular price
        if ($specialPrice && $specialPrice < $regularPrice) {
            // Get special from and to dates
            $specialFromDate = $product->getSpecialFromDate();
            $specialToDate = $product->getSpecialToDate();

            // Get current date
            $currentDate = new \DateTime();

            // Check if special price is within the date range
            if ($specialFromDate) {
                $fromDate = new \DateTime($specialFromDate);
                if ($currentDate < $fromDate) {
                    return false; // Special price not yet active
                }
            }

            if ($specialToDate) {
                $toDate = new \DateTime($specialToDate);
                if ($currentDate > $toDate) {
                    return false; // Special price has expired
                }
            }

            return true; // Valid special price
        }

        return false; // No valid special price
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

    /**
     * Get Max Order total value
     */
    public function getMinOrderTotal()
    {
        $cacheKey = 'alifshop_min_order_total';
        $cachedData = $this->cache->load($cacheKey);
        $cacheTtl = (int) $this->getAlifShopConfig('min_order_total_ttl') ?? 43200;

        if ($cachedData) {
            $this->logger->info('sending from cache');
            return $this->serializer->unserialize($cachedData);
        }

        $apiEndpoint = $this->getAlifShopConfig("api_endpoint") . "/merchant";
        $cashboxToken = $this->getAlifShopConfig("cashbox_token");

        if (!$apiEndpoint || !$cashboxToken) {
            return null;
        }

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Accept', 'application/json');
        $this->curl->addHeader('Cashbox-token', $cashboxToken);
        $this->curl->get($apiEndpoint);

        $response = $this->curl->getBody();
        $responseArray = json_decode($response, true);

        $minOrderTotal = isset($responseArray['min_installment_amount'])
            ? $responseArray['min_installment_amount']
            : 0;

        // Cache the result for 12 hours (43200 seconds)
        $this->cache->save(
            $this->serializer->serialize($minOrderTotal),
            $cacheKey,
            ['alifshop_cache'],
            $cacheTtl
        );

        $this->logger->info('sending from API response');

        return $minOrderTotal;
    }
}