<?php

namespace Custobar\CustoConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const CONFIG_BASE_ROOT = 'custobar/custobar_custoconnector/';
    public const CONFIG_ALLOWED_WEBSITES = self::CONFIG_BASE_ROOT . 'allowed_websites';
    public const CONFIG_API_PREFIX = self::CONFIG_BASE_ROOT . 'prefix';
    public const CONFIG_API_KEY = self::CONFIG_BASE_ROOT . 'apikey';
    public const CONFIG_MODE = self::CONFIG_BASE_ROOT . 'mode';
    public const CONFIG_TRACKING_MODE = self::CONFIG_BASE_ROOT . 'tracking_mode';
    public const CONFIG_TRACKING_SCRIPT = self::CONFIG_BASE_ROOT . 'tracking_script';
    public const CONFIG_TRACKING_SCRIPT_V2 = self::CONFIG_BASE_ROOT . 'tracking_script_v2';

    public const CONFIG_MAPPING_ROOT = 'custobar/custoconnector_field_mapping/';
    public const CONFIG_MAPPING_PRODUCT = self::CONFIG_MAPPING_ROOT . 'product';
    public const CONFIG_MAPPING_CUSTOMER = self::CONFIG_MAPPING_ROOT . 'customer';
    public const CONFIG_MAPPING_ORDER = self::CONFIG_MAPPING_ROOT . 'order';
    public const CONFIG_MAPPING_NEWSLETTER = self::CONFIG_MAPPING_ROOT . 'newsletter';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get allowed website ids from config
     *
     * @return int[]
     */
    public function getAllowedWebsites()
    {
        $websites = $this->scopeConfig->getValue(
            self::CONFIG_ALLOWED_WEBSITES,
            ScopeInterface::SCOPE_STORE
        );

        return \explode(',', (string) $websites);
    }

    /**
     * Get Custobar API prefix from config
     *
     * @return string
     */
    public function getApiPrefix()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_API_PREFIX,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Custobar API key from config
     *
     * @return string
     */
    public function getApiKey()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_API_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if we should use dev mode logic
     *
     * @return bool
     */
    public function isDevMode()
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get tracking mode from config
     *
     * @return int
     */
    public function getTrackingMode()
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_TRACKING_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get tracking script (v1) from config
     *
     * @return string
     */
    public function getTrackingScript()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_TRACKING_SCRIPT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get tracking script (v2) from config
     *
     * @return string
     */
    public function getTrackingScriptV2()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_TRACKING_SCRIPT_V2,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get field mapping for products from config
     *
     * @return string
     */
    public function getFieldMappingProduct()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_MAPPING_PRODUCT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get field mapping for customers from config
     *
     * @return string
     */
    public function getFieldMappingCustomer()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_MAPPING_CUSTOMER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get field mapping for orders from config
     *
     * @return string
     */
    public function getFieldMappingOrder()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_MAPPING_ORDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get field mapping for newsletters from config
     *
     * @return string
     */
    public function getFieldMappingNewsletter()
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_MAPPING_NEWSLETTER,
            ScopeInterface::SCOPE_STORE
        );
    }
}
