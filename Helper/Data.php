<?php

namespace AlifShop\AlifShop\Helper;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;

class Data
{
    const MODULE_NAME = "AlifShop_AlifShop";

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    private $storeManager;

    protected $composerJsonPath;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $moduleDir = $componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $this->composerJsonPath = $moduleDir . '/composer.json';
    }

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

    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Get Base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->urlBuilder->getBaseUrl();
    }
}