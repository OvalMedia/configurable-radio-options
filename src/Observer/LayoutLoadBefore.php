<?php

declare(strict_types=1);

namespace OM\ConfigurableRadioOptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OM\ConfigurableRadioOptions\Service\CurrentProductService;

class LayoutLoadBefore implements ObserverInterface
{
    /**
     * @var \OM\ConfigurableRadioOptions\Service\CurrentProductService
     */
    protected CurrentProductService $_currentProductService;

    /**
     * @param \OM\ConfigurableRadioOptions\Service\CurrentProductService $currentProductService
     */
    public function __construct(
        CurrentProductService $currentProductService
    ) {
        $this->_currentProductService = $currentProductService;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer): LayoutLoadBefore
    {
        $product = $this->_currentProductService->get();

        if ($product){
            if ($product->getAttributeText('configurable_display_type') == 'Radio') {
                $layout = $observer->getLayout();
                $layout->getUpdate()->addHandle('catalog_product_view_type_configurable_radio');
            }
        }

        return $this;
    }
}