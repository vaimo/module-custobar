<?php

namespace Custobar\CustoConnector\Block;

use Custobar\CustoConnector\Model\Config;
use Custobar\CustoConnector\Api\WebsiteValidatorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class Statistics extends Template
{
    /**
     * @var WebsiteValidatorInterface
     */
    private $websiteValidator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param WebsiteValidatorInterface $websiteValidator
     * @param mixed[] $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        WebsiteValidatorInterface $websiteValidator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->websiteValidator = $websiteValidator;
    }

    /**
     * Get tracking script (v1) from config
     *
     * @return string
     */
    public function getTrackingScript()
    {
        return $this->config->getTrackingScript();
    }

    /**
     * Get tracking script (v2) from config
     *
     * @return string
     */
    public function getTrackingScriptV2()
    {
        return $this->config->getTrackingScriptV2();
    }

    /**
     * Get tracking mode from config
     *
     * @return int
     */
    public function getTrackingMode()
    {
        return $this->config->getTrackingMode();
    }

    /**
     * Check if tracking can be done
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canTrack()
    {
        $mode = $this->getTrackingMode();
        if (!$this->isAllowedWebsite() || !$mode) {
            return false;
        }

        return true;
    }

    /**
     * Check if we are currently in website configured in module settings
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isAllowedWebsite()
    {
        $store = $this->_storeManager->getStore();
        $websiteId = (int)$store->getWebsiteId();

        return $this->websiteValidator->isWebsiteAllowed($websiteId);
    }
}
