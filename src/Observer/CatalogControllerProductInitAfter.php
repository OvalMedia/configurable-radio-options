<?php
declare(strict_types=1);

namespace OM\ConfigurableRadioOptions\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OM\ConfigurableRadioOptions\Service\CurrentProductService;

class CatalogControllerProductInitAfter implements ObserverInterface
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
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->_currentProductService->set($observer->getData('product'));
    }
}
