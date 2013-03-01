<?php

class JR_Api_Model_Catalog_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
{
    public function create($type, $set, $sku, $productData, $store = null)
    {
        // Allow attribute set name instead of id
        if (is_string($set) && !is_numeric($set)) {
            $set = Mage::helper('jr_api')->getAttributeSetIdByName($set);
        }

        //If the product exists with diffrent configurable attributes, drop and create a similar product
        if($type == 'configurable' && isset($productData->additional_attributes['config_attributes'])) {
            
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            $configAttributes = explode(',', $productData->additional_attributes['config_attributes']);
            
            if ($product && !empty($configAttributes)) {
                $old_attrs  = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                
                if((count($old_attrs) != count($configAttributes)) || $old_attrs[0]['attribute_code'] && ($old_attrs[0]['attribute_code'] != $configAttributes[0])) {
                    
                    $productData->name = $product->getName();
                    $productData->description = $product->getDescription();
                    $productData->short_description = $product->getShortDescription();
                    $productData->weight = $product->getWeight();
                    $productData->price = $product->getPrice();
                    $productData->status = $product->getStatus();
                    $productData->additional_attributes = array(
                        'orig_name' => $product->getData('orig_name'),
                        'composition' => $product->getData('composition'),
                        'maintenance' => $product->getData('maintenance'),
                        'size_chart' => $product->getData('size_chart'),
                        'config_attributes' => $productData->additional_attributes['config_attributes']
                    );
                    $productData->media = array('image' => $product->getData('image'));
                    $productData->categories = $product->getCategoryIds();
                    
                    Mage::dispatchEvent('catalog_controller_product_delete', array('product' => $product));
                    $product->delete();
                }
            }
        }
        
        return parent::create($type, $set, $sku, $productData, $store);
    }

    protected function _prepareDataForSave($product, $productData)
    {
        /* @var $product Mage_Catalog_Model_Product */

        $configAttributes = array();
        
        if (property_exists($productData, 'categories')) {
            $categoryIds = Mage::helper('jr_api/catalog_product')
                ->getCategoryIdsByNames((array) $productData->categories);
            if (!empty($categoryIds)) {
                $productData->categories = array_unique($categoryIds);
            }
        }

        
        if (property_exists($productData, 'media')) {
            $path = Mage::getBaseDir().'/media/catalog/product';
            if(file_exists($path.$productData->media['image'])) {
			    $product->addImageToMediaGallery($path.$productData->media['image'], array('image','small_image','thumbnail'), true, false);
            }
        }
        
        
        if (property_exists($productData, 'additional_attributes')) {
            $singleDataExists = property_exists((object) $productData->additional_attributes, 'single_data');
            $multiDataExists = property_exists((object) $productData->additional_attributes, 'multi_data');
            
            $configAttributesExists = property_exists((object) $productData->additional_attributes, 'config_attributes');
            if ($configAttributesExists) {
                $configAttributes = explode(',', $productData->additional_attributes['config_attributes']);
            }
            
            if ($singleDataExists || $multiDataExists) {
                if ($singleDataExists) {
                    foreach ($productData->additional_attributes->single_data as $_attribute) {
                        $_attrCode = $_attribute->key;
                        $productData->$_attrCode = Mage::helper('jr_api/catalog_product')
                            ->getOptionKeyByLabel($_attrCode, $_attribute->value);
                    }
                }
                if ($multiDataExists) {
                    foreach ($productData->additional_attributes->multi_data as $_attribute) {
                        $_attrCode = $_attribute->key;
                        $productData->$_attrCode = Mage::helper('jr_api/catalog_product')
                            ->getOptionKeyByLabel($_attrCode, $_attribute->value);
                    }
                }
            } else {
                foreach ($productData->additional_attributes as $_attrCode => $_value) {
                    $productData->$_attrCode = Mage::helper('jr_api/catalog_product')
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
            Mage::helper('jr_api/catalog_product')->associateProducts($product, $simpleSkus, $priceChanges, $configAttributes);
        }
    }
}
