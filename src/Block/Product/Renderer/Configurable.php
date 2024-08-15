<?php
/**
 * It is not possible to override the template file for this block via XML.
 * @see: https://github.com/magento/magento2/issues/4400
 *
 * In the module Magento_Swatches the template file is hardcoded.
 *
 */

declare(strict_types=1);

namespace OM\ConfigurableRadioOptions\Block\Product\Renderer;

class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * @return string
     */
    protected function getRendererTemplate(): string
    {
        $product = $this->getProduct();
        $template = $this->isProductHasSwatchAttribute ? self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;

        if ($product->getAttributeText('configurable_display_type') == 'Radio') {
            $template = 'OM_ConfigurableRadioOptions::product/view/type/options/configurable/radio.phtml';
        }

        return $template;
    }
}