<?php

class Bubble_Api_Model_Catalog_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
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

        if (property_exists($productData, 'categories')) {
            $categoryIds = Mage::helper('bubble_api/catalog_product')
                ->getCategoryIdsByNames((array) $productData->categories);
            if (!empty($categoryIds)) {
                $productData->categories = array_unique($categoryIds);
            }
        }

        if (property_exists($productData, 'additional_attributes')) {
            $singleDataExists = property_exists((object) $productData->additional_attributes, 'single_data');
            $multiDataExists = property_exists((object) $productData->additional_attributes, 'multi_data');
            if ($singleDataExists || $multiDataExists) {
                if ($singleDataExists) {
                    foreach ($productData->additional_attributes->single_data as $_attribute) {
                        $_attrCode = $_attribute->key;
                        $productData->$_attrCode = Mage::helper('bubble_api/catalog_product')
                            ->getOptionKeyByLabel($_attrCode, $_attribute->value);
                    }
                }
                if ($multiDataExists) {
                    foreach ($productData->additional_attributes->multi_data as $_attribute) {
                        $_attrCode = $_attribute->key;
                        $productData->$_attrCode = Mage::helper('bubble_api/catalog_product')
                            ->getOptionKeyByLabel($_attrCode, $_attribute->value);
                    }
                }
            } else {
                foreach ($productData->additional_attributes as $_attrCode => $_value) {
                    $productData->$_attrCode = Mage::helper('bubble_api/catalog_product')
                        ->getOptionKeyByLabel($_attrCode, $_value);
                }
            }
            unset($productData->additional_attributes);
        }

        if (property_exists($productData, 'website_ids')) {
            $websiteIds = (array) $productData->website_ids;
            foreach ($websiteIds as $i => $websiteId) {
                if (!is_numeric($websiteId)) {
                    $website = Mage::app()->getWebsite($websiteId);
                    if ($website->getId()) {
                        $websiteIds[$i] = $website->getId();
                    }
                }
            }
            $product->setWebsiteIds($websiteIds);
            unset($productData->website_ids);
        }

        parent::_prepareDataForSave($product, $productData);

        if (property_exists($productData, 'associated_skus')) {
            $simpleSkus = (array) $productData->associated_skus;
            $priceChanges = array();
            if (property_exists($productData, 'price_changes')) {
                if (key($productData->price_changes) === 0) {
                    $priceChanges = $productData->price_changes[0];
                } else {
                    $priceChanges = $productData->price_changes;
                }
            }
            $configurableAttributes = array();
            if (property_exists($productData, 'configurable_attributes')) {
                $configurableAttributes = $productData->configurable_attributes;
            }
            Mage::helper('bubble_api/catalog_product')->associateProducts($product, $simpleSkus, $priceChanges, $configurableAttributes);
        }
    }
    
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
