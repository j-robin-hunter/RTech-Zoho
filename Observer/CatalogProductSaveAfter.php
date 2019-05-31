<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Observer;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use RTech\Zoho\Webservice\Exception\ZohoItemExistsException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;

class CatalogProductSaveAfter implements ObserverInterface {
  const ENABLED = 1;
  const DISABLED = 2;
  const ITEM_TYPES = array('simple', 'virtual', 'downloadable');
  const GROUP_TYPES = array('configurable', 'grouped', 'bundle');
  const ZOHO_ITEM = 'item';
  const ZOHO_GROUP = 'group';
  const MANAGE_SOCK = 1;

  protected $_zohoClient;
  protected $__storeId;
  protected $_configurable;
  protected $_productTax;
  protected $_directoryList;
  protected $_zohoInventoryRepository;
  protected $_zohoInventoryFactory;
  protected $_messageManager;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
    \Magento\Catalog\Model\ProductRepository $productRepository,
    \Magento\Tax\Model\TaxClass\Source\Product $productTax,
    \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \RTech\Zoho\Model\ZohoInventoryFactory $zohoInventoryFactory,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->_zohoClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_storeId = $storeManager->getStore()->getId();
    $this->_configurable = $configurable;
    $this->_productTax = $productTax;
    $this->_directoryList = $directoryList;
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_zohoInventoryFactory = $zohoInventoryFactory;
    $this->_messageManager = $messageManager;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $product = $observer->getProduct();
    try {
      $zohoInventory = $this->_zohoInventoryRepository->getId($product->getId());
      $zohoItemId = $zohoInventory->getZohoId();
    } catch (NoSuchEntityException $ex) {
      // Treat as a new product
    }

    if (in_array($product->getTypeId(), self::ITEM_TYPES)) {
      //****************
      // **** ITEMS ****
      //****************
      if (isset($zohoItemId)) {

        // EXISTING CHILD PRODUCTS
        try {
          $inventoryItem = $this->_zohoClient->getItem($zohoItemId);
          $inventoryItem = $this->getItemArray($product, $inventoryItem);
          $inventoryItem = $this->_zohoClient->itemUpdate($inventoryItem);
        } catch (ZohoItemNotFoundException $ex) {
          // Item has been deleted from or does not exists in Zoho Inventory
          // Recreate item from current product entry
          $item = $this->getItemArray($product, []);
          $inventoryItem = $this->_zohoClient->itemAdd($item);
          $zohoInventory->setData([
            'product_id' => $product->getId(),
            'product_name'=> $product->getName(),
            'zoho_id' => $inventoryItem['item_id'],
            'zoho_type' => self::ZOHO_ITEM
          ]);
          $this->_zohoInventoryRepository->save($zohoInventory);
        }

        if ($zohoInventory->getProductName() != $product->getName()) {
          $zohoInventory->setProductName($product->getName());
          $this->_zohoInventoryRepository->save($zohoInventory);
        }

        // Check that the Zoho Inventory group is enabled is this product is enabled
        if ($product->getStatus() == self::ENABLED) {
          foreach ($this->_configurable->getParentIdsByChild($product->getId()) as $parentId) {
            $zohoInventory = $this->_zohoInventoryRepository->getId($parentId);
            if ($zohoInventory->getZohoId()) {
              $this->_zohoClient->itemGroupSetActive($zohoInventory->getZohoId());
            }
          }
        }
      } else {

        // NEW CHILD PRODUCTS
        $item = $this->getItemArray($product, []);
        try {
          $inventoryItem = $this->_zohoClient->itemAdd($item);
        } catch (ZohoItemExistsException $ex) {
          // A Zoho Item of this name already exists so try to link this product to it.
          // To successfully link the namne and the sku must match the existing Zoho Inventory item
          $inventoryItem = $this->_zohoClient->getItemByName($product->getName());
          if ($inventoryItem['sku'] != $product->getSku()) {
            throw ZohoItemExistsException::create('Zoho Inventory item "' . $inventoryItem['name'] . '" already exists with sku "' . $inventoryItem['sku'] . '"');
          }
          $this->_messageManager->addNotice('Zoho Inventory item "' . $inventoryItem['name']  . '" has been linked');
          $this->_zohoClient->itemSetActive($inventoryItem['item_id']);
        }
        $zohoInventory = $this->_zohoInventoryFactory->create();
        $zohoInventory->setData([
          'product_id' => $product->getId(),
          'product_name'=> $product->getName(),
          'zoho_id' => $inventoryItem['item_id'],
          'zoho_type' => self::ZOHO_ITEM
        ]);
        $this->_zohoInventoryRepository->save($zohoInventory);
      }
      if ($product->getStatus() == self::DISABLED && $inventoryItem['status'] == 'active') {
        $this->_zohoClient->itemSetInactive($inventoryItem['item_id']);
      } else if ($product->getStatus() == self::ENABLED && $inventoryItem['status'] == 'inactive') {
        $this->_zohoClient->itemSetActive($inventoryItem['item_id']);
      }
      $this->setInventoryImage($product, $inventoryItem);
    } else {

      //*****************
      // **** GROUPS ****
      //*****************
      if (isset($zohoItemId)) {

        // EXISTING PARENT PRODUCTS
        $inventoryGroup = $this->_zohoClient->getItemGroup($zohoItemId);
        $inventoryGroup = $this->getGroupArray($product, $inventoryGroup);
        $inventoryGroup = $this->_zohoClient->itemGroupUpdate($inventoryGroup);
        if ($zohoInventory->getProductName() != $product->getName()) {
          $zohoInventory->setProductName($product->getName());
          $this->_zohoInventoryRepository->save($zohoInventory);
        }
      } else {
        $group = $this->getGroupArray($product);
        $inventoryGroup = $this->_zohoClient->itemGroupAdd($group);
        $zohoInventory = $this->_zohoInventoryFactory->create();
        $zohoInventory->setData([
          'product_id' => $product->getId(),
          'product_name'=> $product->getName(),
          'zoho_id' => $inventoryGroup['group_id'],
          'zoho_type' => self::ZOHO_GROUP
        ]);
        $this->_zohoInventoryRepository->save($zohoInventory);
      }
    }
  }

  protected function getItemArray($product, $item) {
    $taxes = $this->_zohoClient->getTaxes();
    $tax = $taxes[
      array_search(
        $this->_productTax->getAllOptions()[
          array_search($product->getData('tax_class_id'), array_column($this->_productTax->getAllOptions(), 'value'))
        ]['label'],
        array_column($taxes, 'tax_name')
      )
    ];
    if (!$item && $product->getStockData()['manage_stock'] == self::MANAGE_SOCK) {
      $item = [
      'item_type' => 'inventory',
      'initial_stock' => 0,
      'initial_stock_rate' => 0,
      ];
    }
    $item = array_merge($item, [
      'name' => $product->getName(),
      'sku' => $product->getSku(),
      'unit' => 'each',
      'package_details' => [
        'length' => $product->getData('ts_dimensions_length')?:'',
        'width' => $product->getData('ts_dimensions_width')?:'',
        'height' => $product->getData('ts_dimensions_height')?:'',
        'weight' => $product->getData('weight')?:''
      ],
      'manufacturer' => $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product)?:'',
      'rate' => $product->getData('price'),
      'is_returnable' => true,
      'tax_id' => $tax['tax_id']?:''
    ]);
    return $item;
  }

  protected function getGroupArray($product, $inventoryGroup=null) {
    $taxes = $this->_zohoClient->getTaxes();
    $tax = $taxes[
      array_search(
        $this->_productTax->getAllOptions()[
          array_search($product->getData('tax_class_id'), array_column($this->_productTax->getAllOptions(), 'value'))
        ]['label'],
        array_column($taxes, 'tax_name')
      )
    ];

    $group = array(
      'group_name' => $product->getName(),
      'unit' => 'each',
      'manufacturer' => $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product)?:'',
      'tax_id' => $tax['tax_id']?:'',
      'items' => []
    );

    foreach ($product->getTypeInstance()->getUsedProducts($product) as $child) {
      $zohoInventory = $this->_zohoInventoryRepository->getId($child->getId());

      if (!$zohoInventory->getId()) {
        // No Zoho Inventory cross reference so retrieve from Zoho Inventory by name
        $inventoryChild= $this->_zohoClient->getItemByName($child->getName());
        $zohoInventory->setData([
          'product_id' => $child->getId(),
          'product_name'=>  $child->getName(),
          'zoho_id' => $inventoryChild['item_id'],
          'zoho_type' => self::ZOHO_ITEM
        ]);
        $this->_zohoInventoryRepository->save($zohoInventory);
      }

      $item = array(
        'item_id' => $zohoInventory->getZohoId(),
        'name' => $zohoInventory->getProductName()
      );
      $group['items'][] = $item;
    }

    if ($inventoryGroup) {
      $group['group_id'] = $inventoryGroup['group_id'];
      foreach ($inventoryGroup['items'] as $item) {
        if (!in_array($item['item_id'], array_column($group['items'], 'item_id'))) {
          $this->_zohoClient->itemUngroup($item['item_id']);
        }
      }
    }

    return $group;
  }

  private function setInventoryImage($product, $inventoryItem) {
    // The value no_selection can be returned for a product that has no selected image. In this case
    // delete image from Zoho inventory
    $image = $product->getData('image');
    if ($image) {

      if ($image == 'no_selection') {
        $this->_zohoClient->imageDelete($inventoryItem['item_id']);
      } else {
        $this->_zohoClient->imageAdd($inventoryItem['item_id'], $this->_directoryList->getPath('pub') . '/media/catalog/product' . $image);
      }
    } else if (isset($inventoryItem['image_name'])) {
      $this->_zohoClient->imageDelete($inventoryItem['item_id']);
    }
  }
}