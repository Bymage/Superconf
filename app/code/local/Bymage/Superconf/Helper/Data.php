<?php
/**
* @author Bymage Team
* @copyright Copyright (c)  2015 Bymage (http://www.bymage.com)
* @package Bymage_Superconf
*/
class Bymage_Superconf_Helper_Data extends Mage_Catalog_Helper_Product
{
    public function initProduct($productId, $controller, $params = null)
    {
        // Prepare data for routine
        if (!$params) {
            $params = new Varien_Object();
        }

        // Init and load product
        Mage::dispatchEvent('catalog_controller_product_init_before', array(
            'controller_action' => $controller,
            'params' => $params,
        ));

        if (!$productId) {
            return false;
        }
        /*bymage code start*/
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
        if (!empty($parentIds) && Mage::getStoreConfig('bysuperconf/general/enable'))
        {
            $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($parentIds[0]);
            $simple  = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($productId);
            $productId = $product->getId();

            $_attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
            $object  =  new Varien_Object();
            $values = array();
            foreach($_attributes as $_attribute){
                $attributeCode = $_attribute->getData('product_attribute')->getData('attribute_code');
                $value = $simple->getData($attributeCode);
                $values[$_attribute->getData("attribute_id")] = $value;
            }
            $object->setData('super_attribute', $values);

            $product->setData('preconfigured_values',$object);
        }
        else{
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);
        }
        /*bymage code end*/


        if (!$this->canShow($product)) {
            return false;
        }
        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }

        // Load product current category
        $categoryId = $params->getCategoryId();
        if (!$categoryId && ($categoryId !== false)) {
            $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
            if ($product->canBeShowInCategory($lastId)) {
                $categoryId = $lastId;
            }
        } elseif (!$product->canBeShowInCategory($categoryId)) {
            $categoryId = null;
        }

        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        // Register current data and dispatch final events
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        try {
            Mage::dispatchEvent('catalog_controller_product_init', array('product' => $product));
            Mage::dispatchEvent('catalog_controller_product_init_after',
                array('product' => $product,
                    'controller_action' => $controller
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $product;
    }
}
