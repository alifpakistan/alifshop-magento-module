<?php

namespace AlifShop\AlifShop\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;

class Data
{
    const MODULE_NAME = "AlifShop_AlifShop";
    protected const ALIFSHOP_CONFIG_PATH = "payment/alifshop/";
    protected $urlBuilder;
    protected $storeManager;
    protected $composerJsonPath;
    protected $scopeConfig;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
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
}