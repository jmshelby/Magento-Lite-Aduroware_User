<?php
/**
 * Account dashboard sidebar
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Aduroware_User_Block_Account_Dashboard_Sidebar extends Mage_Core_Block_Template
{
    protected $_cartItemsCount;

    /**
     * Enter description here...
     *
     * @var Mage_Wishlist_Model_Wishlist
     */
    protected $_wishlist;

    protected $_compareItems;

    public function getShoppingCartUrl()
    {
        return Mage::getUrl('checkout/cart');
    }

    public function getCartItemsCount()
    {
        if( !$this->_cartItemsCount ) {
            $this->_cartItemsCount = Mage::getModel('sales/quote')
                ->setId(Mage::getModel('checkout/session')->getQuote()->getId())
                ->getItemsCollection()
                ->getSize();
        }

        return $this->_cartItemsCount;
    }

    public function getWishlist()
    {
        if( !$this->_wishlist ) {
            $this->_wishlist = Mage::getModel('wishlist/wishlist')
                ->loadByUser(Mage::getSingleton('user/session')->getUser());
            $this->_wishlist->getItemCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('small_image')
                ->addAttributeToFilter('store_id', array('in' => $this->_wishlist->getSharedStoreIds()))
                ->addAttributeToSort('added_at', 'desc')
                ->setCurPage(1)
                ->setPageSize(3)
                ->load();
        }

        return $this->_wishlist->getItemCollection();
    }

    public function getWishlistCount()
    {
        return $this->getWishlist()->getSize();
    }

    public function getWishlistAddToCartLink($wishlistItem)
    {
        return Mage::getUrl('wishlist/index/cart', array('item' => $wishlistItem->getId()));
    }

     public function getCompareItems()
     {
         if( !$this->_compareItems ) {
             $this->_compareItems = Mage::getResourceModel('catalog/product_compare_item_collection')
                 ->setStoreId(Mage::app()->getStore()->getId());
            $this->_compareItems->setUserId(Mage::getSingleton('user/session')->getUserId());
            $this->_compareItems
                ->addAttributeToSelect('name')
                ->useProductItem()
                ->load();

         }

         return $this->_compareItems;
     }

     public function getCompareJsObjectName()
     {
         return "dashboardSidebarCompareJsObject";
     }

     public function getCompareRemoveUrlTemplate()
     {
         return $this->getUrl('catalog/product_compare/remove',array('product'=>'#{id}'));
     }

     public function getCompareAddUrlTemplate()
     {
         return $this->getUrl('catalog/product_compare/add',array('product'=>'#{id}'));
     }

     public function getCompareUrl()
     {
         return $this->getUrl('catalog/product_compare');
     }
}
