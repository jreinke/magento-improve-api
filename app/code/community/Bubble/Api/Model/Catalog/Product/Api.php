<?php

class Bubble_Api_Model_Catalog_Product_Api extends Mage_Catalog_Model_Product_Api
{
    public function create($type, $set, $sku, $productData, $store = null)
    {
        // Allow attribute set name instead of id
        if (is_string($set) && !is_numeric($set)) {
            $set = Mage::helper('bubble_api')->getAttributeSetIdByName($set);
        }

        return parent::create($type, $set, $sku, $productData, $store);
    }

    protected function _prepareDataForSave($product, $productData)
    {
        /* @var $product Mage_Catalog_Model_Product */

        if (isset($productData['categories'])) {
            $categoryIds = Mage::helper('bubble_api/catalog_product')
                ->getCategoryIdsByNames((array) $productData['categories']);
            if (!empty($categoryIds)) {
                $productData['categories'] = array_unique($categoryIds);
            }
        }

        if (isset($productData['website_ids'])) {
            $websiteIds = $productData['website_ids'];
            foreach ($websiteIds as $i => $websiteId) {
                if (!is_numeric($websiteId)) {
                    $website = Mage::app()->getWebsite($websiteId);
                    if ($website->getId()) {
                        $websiteIds[$i] = $website->getId();
                    }
                }
            }
            $product->setWebsiteIds($websiteIds);
            unset($productData['website_ids']);
        }

        foreach ($productData as $code => $value) {
            $productData[$code] = Mage::helper('bubble_api/catalog_product')
                ->getOptionKeyByLabel($code, $value);
        }

        parent::_prepareDataForSave($product, $productData);

        if (isset($productData['associated_skus'])) {
            $simpleSkus = $productData['associated_skus'];
            $priceChanges = isset($productData['price_changes']) ? $productData['price_changes'] : array();
            $configurableAttributes = isset($productData['configurable_attributes']) ? $productData['configurable_attributes'] : array();
            Mage::helper('bubble_api/catalog_product')->associateProducts($product, $simpleSkus, $priceChanges, $configurableAttributes);
        }
    }
    
    // Not tested
    public function info($productId, $store = null, $attributes = null, $identifierType = null)
    {
        $result = parent::info($productId, $store, $attributes, $identifierType);
        if ($result['type'] == 'configurable') {
            $associated_skus = array();
            $product = Mage::getModel('catalog/product')->load($productId);
            $used_products = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$product);
            foreach ($used_products as $used_product) {
                $associated_skus[] = $used_product->getSku();
            }
            $result += ['associated_skus' => $associated_skus];
            $configurable_attributes = array();
            $_configurable_attributes = Mage::getModel('catalog/product_type_configurable')->getUsedProductAttributeIds($product);
            foreach ($_configurable_attributes as $configurable_attribute) {
                $configurable_attributes[] = Mage::getModel('eav/entity_attribute')->load($configurable_attribute)->getAttributeCode();
            }
            $result += ['configurable_attributes' => $configurable_attributes];
        }
        return $result;
    }
}