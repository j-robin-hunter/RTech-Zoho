<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Observer;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use RTech\Zoho\Api\Data\ZohoInventoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use RTech\Zoho\Webservice\Exception\ZohoItemExistsException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;

class CatalogProductSaveAfter implements ObserverInterface {
  const ENABLED = 1;
  const DISABLED = 2;
  const SIMPLE_TYPE = 'simple';
  const VIRTUAL_TYPE = 'virtual';
  const DOWNLOADABLE_TYPE = 'downloadable';
  const CONFIGURABLE_TYPE = 'configurable';
  const GROUP_TYPE= 'grouped';
  const BUNDLE_TYPE = 'bundle';
  const ITEM_TYPES = array(self::SIMPLE_TYPE, self::VIRTUAL_TYPE, self::DOWNLOADABLE_TYPE);
  const GROUP_TYPES = array(self::CONFIGURABLE_TYPE, self::GROUP_TYPE, self::BUNDLE_TYPE);
  const ZOHO_ITEM = 'item';
  const ZOHO_GROUP = 'group';
  const ZOHO_COMPOSITE = 'composite';
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
      $zohoInventory = $this->_zohoInventoryRepository->getById($product->getId());
      $zohoItemId = $zohoInventory->getZohoId();
    } catch (NoSuchEntityException $ex) {
      // Treat as a new product
    }

    $t = in_array($product->getTypeId(), self::ITEM_TYPES);
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
            ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
            ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
            ZohoInventoryInterface::ZOHO_ID => $inventoryItem['item_id'],
            ZohoInventoryInterface::ZOHO_TYPE => self::ZOHO_ITEM
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
            $zohoInventory = $this->_zohoInventoryRepository->getById($parentId);
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
          // To successfully link the name and the sku must match the existing Zoho Inventory item
          $inventoryItem = $this->_zohoClient->getItemByName($product->getName());
          if ($inventoryItem['sku'] != $product->getSku()) {
            // If the product sku and inventory sku are not the same then do not save to Zoho and do not do more work
            $this->_messageManager->addWarning('Product not saved to Zoho Inventory as item "' . $inventoryItem['name'] . '" already exists with sku "' . $inventoryItem['sku']. '"');
            return;
          } else {
            $this->_messageManager->addNotice('Zoho Inventory item "' . $inventoryItem['name']  . '" has been linked');
            $this->_zohoClient->itemSetActive($inventoryItem['item_id']);
          }
        }
        $zohoInventory = $this->_zohoInventoryFactory->create();
        $zohoInventory->setData([
          ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
          ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
          ZohoInventoryInterface::ZOHO_ID => $inventoryItem['item_id'],
          ZohoInventoryInterface::ZOHO_TYPE => self::ZOHO_ITEM
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

      // Only process configurable product as these are similar to Zoho item groups
      // while bundled products are not similar to Zoho composite items which  are essentially
      // a product built fromn sub-products rather than a product  built from a configuration
      // of sub-products
      if (isset($zohoItemId)) {

        // EXISTING PARENT PRODUCTS
        /*
        if ($product->getTypeId() == self::BUNDLE_TYPE) {
          try {
            $inventoryComposite = $this->_zohoClient->getCompositeItem($zohoItemId);
            $bundle = $this->getBundleArray($product, $inventoryComposite);
            $inventoryComposite = $this->_zohoClient->itemCompositeUpdate($bundle);
            if ($zohoInventory->getProductName() != $product->getName()) {
              $zohoInventory->setProductName($product->getName());
              $this->_zohoInventoryRepository->save($zohoInventory);
            }
          } catch (ZohoItemNotFoundException $ex) {
            // Compiste item has been deleted from or does not exists in Zoho Inventory
            // Recreate item from current product entry
            $bundle = $this->getBundleArray($product);
            $inventoryComposite = $this->_zohoClient->itemCompositeAdd($bundle);
            $zohoInventory->setData([
              ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
              ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
              ZohoInventoryInterface::ZOHO_ID => $inventoryComposite['composite_item_id'],
              ZohoInventoryInterface::ZOHO_TYPE => self::ZOHO_COMPOSITE
            ]);
            $this->_zohoInventoryRepository->save($zohoInventory);
          }
        } else {
        */
        if ($product->getTypeId == self::CONFIGURABLE_TYPE) {
          try {
            $inventoryGroup = $this->_zohoClient->getItemGroup($zohoItemId);
            $inventoryGroup = $this->getGroupArray($product, $inventoryGroup);
            $inventoryGroup = $this->_zohoClient->itemGroupUpdate($inventoryGroup);
            if ($zohoInventory->getProductName() != $product->getName()) {
              $zohoInventory->setProductName($product->getName());
              $this->_zohoInventoryRepository->save($zohoInventory);
            }
          } catch (ZohoItemNotFoundException $ex) {
            // If all of the items are removed from a Zoho Item Group
            // the Item Group will be deleted. As such the entire Item Group
            // will need to be be creatred, and the existing zoho_inventory will
            // need to be updated
            $group = $this->getGroupArray($product);
            $inventoryGroup = $this->_zohoClient->itemGroupAdd($group);
            $zohoInventory->setZohoId($inventoryGroup['group_id']);
            $this->_zohoInventoryRepository->save($zohoInventory);
          }
        }
      } else {
        /*
        if ($product->getTypeId() == self::BUNDLE_TYPE) {
          $bundle = $this->getBundleArray($product);
          $inventoryComposite = $this->_zohoClient->itemCompositeAdd($bundle);
          $zohoInventory = $this->_zohoInventoryFactory->create();
          $zohoInventory->setData([
            ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
            ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
            ZohoInventoryInterface::ZOHO_ID => $inventoryComposite['composite_item_id'],
            ZohoInventoryInterface::ZOHO_TYPE => self::ZOHO_COMPOSITE
          ]);
          $this->_zohoInventoryRepository->save($zohoInventory);
        } else {
        */
        if ($product->getTypeId() == self::CONFIGURABLE_TYPE) {
          $group = $this->getGroupArray($product);
          $inventoryGroup = $this->_zohoClient->itemGroupAdd($group);
          $zohoInventory = $this->_zohoInventoryFactory->create();
          $zohoInventory->setData([
            ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
            ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
            ZohoInventoryInterface::ZOHO_ID => $inventoryGroup['group_id'],
            ZohoInventoryInterface::ZOHO_TYPE => self::ZOHO_GROUP
          ]);
          $this->_zohoInventoryRepository->save($zohoInventory);
        }
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
    try {
      if (!$item && $product->getStockData()['manage_stock'] == self::MANAGE_SOCK) {
        $item = [
          'item_type' => 'inventory',
          'initial_stock' => 0,
          'initial_stock_rate' => 0,
        ];
      }
    } catch (\Exception $e) {
      // Just in case 'manage_stock' array index is not set in product stock data
    }
    $manufacturer = $product->getResource()->getAttribute('manufacturer');
    $salesOrderDescription =  $product->getResource()->getAttribute('sales_order_description');
    $purchaseOrderDescription = $product->getResource()->getAttribute('purchase_order_description');
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
      'manufacturer' => $manufacturer ? $manufacturer->getFrontend()->getValue($product) : '',
      'description' => $salesOrderDescription ? $salesOrderDescription->getFrontend()->getValue($product) : '',
      'purchase_description' => $purchaseOrderDescription ? $purchaseOrderDescription->getFrontend()->getValue($product) : '',
      'rate' => $product->getData('price'),
      'is_returnable' => empty($product->getIsVirtual()) ? true : false,
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
      $zohoInventory = $this->_zohoInventoryRepository->getById($child->getId());

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
        'name' => $zohoInventory->getProductName(),
        'sku' => $child->getSku()
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

  protected function getBundleArray($product, $compositeItem=null) {
    $taxes = $this->_zohoClient->getTaxes();
    $tax = $taxes[
      array_search(
        $this->_productTax->getAllOptions()[
          array_search($product->getData('tax_class_id'), array_column($this->_productTax->getAllOptions(), 'value'))
        ]['label'],
        array_column($taxes, 'tax_name')
      )
    ];

    $salesOrderDescription =  $product->getResource()->getAttribute('sales_order_description');
    $purchaseOrderDescription = $product->getResource()->getAttribute('purchase_order_description');

    $bundle = array(
      'name' => $product->getName(),
      'sku' => $product->getSku(),
      'unit' => 'each',
      'rate' => $product->getData('price'),
      'tax_id' => $tax['tax_id']?:'',
      'is_combo_product' => true,
      'item_type' => 'inventory',
      'is_returnable' => true,
      'description' => $salesOrderDescription ? $salesOrderDescription->getFrontend()->getValue($product) : '',
      'purchase_description' => $purchaseOrderDescription ? $purchaseOrderDescription->getFrontend()->getValue($product) : ''
    );

    $selectionCollection = $product->getTypeInstance(true)
	        ->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
    foreach ($selectionCollection as $child) {
      $zohoInventory = $this->_zohoInventoryRepository->getById($child->getProductId());

      if (!$zohoInventory->getId()) {
        // No Zoho Inventory cross reference so retrieve from Zoho Inventory by name
        $inventoryChild= $this->_zohoClient->getItemByName($child->getName());
        $zohoInventory->setData([
          'product_id' => $child->getProductId(),
          'product_name'=>  $child->getName(),
          'zoho_id' => $inventoryChild['item_id'],
          'zoho_type' => self::ZOHO_ITEM
        ]);
        $this->_zohoInventoryRepository->save($zohoInventory);
      }
      $item = array(
        'item_id' => $zohoInventory->getZohoId(),
        'quantity' => $child->getSelectionQty()
      );
      $bundle['mapped_items'][] = $item;
    }

    if ($compositeItem) {
      $bundle['composite_item_id'] = $compositeItem['composite_item_id'];
    }
    return $bundle;
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
