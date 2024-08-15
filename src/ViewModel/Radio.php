<?php
declare(strict_types=1);

namespace OM\ConfigurableRadioOptions\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection as ProductCollection;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection as AttributeCollection;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Block\Product\ImageBuilder;

class Radio implements ArgumentInterface
{
    /**
     *
     */
    const DEFAULT_IMAGE_ID = 'configurable_radio_options';

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected \Magento\Catalog\Api\ProductAttributeRepositoryInterface $_productAttributeRepository;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected LayoutInterface $_layout;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected \Magento\Store\Model\StoreManagerInterface $_storeManager;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected \Magento\CatalogInventory\Api\StockRegistryInterface $_stockRegistry;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder;

    /**
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        LayoutInterface $layout,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry,
        ImageBuilder $imageBuilder
    ) {
        $this->_productAttributeRepository = $productAttributeRepository;
        $this->_storeManager = $storeManager;
        $this->_stockRegistry = $stockRegistry;
        $this->_layout = $layout;
        $this->_imageBuilder = $imageBuilder;
    }

    /**
     *  This is a workaround/replacement for Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getAllowProducts()
     *
     *  The configuration setting "Display Out of Stock Products" indicates if a product without available
     *  stock can be found in the catalog. But this setting also decides if a product can be seen in
     *  the list of variants of a configurable product, which we want regardless.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @see \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getAllowProducts()
     */
    public function getAllowProducts(Product $product): ProductCollection
    {
        /** @var $type \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $type = $product->getTypeInstance();

        $collection = $type->getUsedProductCollection($product);
        $collection->setFlag('has_stock_status_filter', true);

        $collection
            ->addAttributeToSelect('*')
            ->addFilterByRequiredOptions()
            ->setStoreId($product->getStoreId())
        ;

        $collection->getSelect()->joinLeft(
            array(
                '_inv' => $collection->getResource()->getTable('cataloginventory_stock_status'))
            ,
            '_inv.product_id = e.entity_id and _inv.website_id=' . $this->_storeManager->getStore()->getWebsiteId(),
            array('stock_status')
        );

        $collection->addMediaGalleryData();
        $collection->addTierPriceData();
        $nostock = array();

        /**
         * Sort collection by availability. Unavailable products will be moved to the end of the list.
         */
        foreach ($collection as $key => $prd) {
            /** @var $stockItem \Magento\CatalogInventory\Model\Stock\Item */
            $stockItem = $this->_stockRegistry->getStockItem($prd->getId());
            $saleable = $stockItem->getIsInStock();
            $prd->setIsSalable($saleable);

            if (!$saleable) {
                $nostock[] = $prd;
                $collection->removeItemByKey($key);
            }
        }

        foreach ($nostock as $prd) {
            $collection->addItem($prd);
        }

        return $collection;
    }

    /**
     * @param int $id
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttributeCodeById($id): string
    {
        return $this->_productAttributeRepository->get($id)->getAttributeCode();
    }

    /**
     * @param $attributes
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllConfigurableAttributeCodes($attributes): array
    {
        $result = array();

        foreach ($attributes as $attribute) {
            $result[] = $this->getAttributeCodeById($attribute->getAttributeId());
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $codes
     * @param string $separator
     * @return string
     */
    public function getProductAttributeValuesString(Product $product, $codes, string $separator = ' | '): string
    {
        $values = array();

        foreach ($codes as $code) {
            $values[] = $product->getAttributeText($code);
        }

        return implode($separator, $values);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $attributes
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductAttributeValues(Product $product, AttributeCollection $attributes): array
    {
        $values = array();

        foreach ($attributes as $attribute) {
            $values[$attribute->getAttributeid()] = $product->getData($this->getAttributeCodeById($attribute->getAttributeId()));
        }

        return $values;
    }

    /**
     * @param $product
     * @param $attributes
     * @return string
     */
    public function getProductAttributeValuesJson(Product $product, $attributes): string
    {
        return json_encode($this->getProductAttributeValues($product, $attributes));
    }

    /**
     * @param $product
     * @param string $imageId
     * @param $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, string $imageId = null, array $attributes = []): ImageBlock
    {
        return $this->_imageBuilder->create($product, ($imageId ?: self::DEFAULT_IMAGE_ID), $attributes);
    }
}