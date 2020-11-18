<?php

namespace Vexsoluciones\Linkser\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Vexsoluciones\Linkser\Helper\Config;

/**
 * Class LinkserConfigProvider
 *
 * @package Vexsoluciones\Linkser\Model
 */
class LinkserConfigProvider implements ConfigProviderInterface
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Vexsoluciones\Linkser\Helper\Config
     */
    protected $config;

    /**
     * LinkserConfigProvider constructor.
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Vexsoluciones\Linkser\Helper\Config $config
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Config $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'processLinkserUrl' => $this->urlBuilder->getUrl('linkser/process/redirect'),
            'payment' => [
                'ccform' => [
                    'use_name_on_card' => [
                        Config::PAYMENT_METHOD => (boolean)$this->config->isUseNameOnCard()
                    ]
                ]
            ]
        ];
    }
}
