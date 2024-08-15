<?php
declare(strict_types=1);

namespace OM\ConfigurableRadioOptions\Service;

use Magento\Catalog\Api\Data\ProductInterface;

class CurrentProductService
{
    /**
     * @var ProductInterface
     */
    protected $_currentProduct;


    public function set(ProductInterface $product): void
    {
        $this->_currentProduct = $product;
    }

    /**
     * @return ProductInterface
     * @throws \RuntimeException
     */
    public function get()
    {
        return $this->_currentProduct;
    }
}