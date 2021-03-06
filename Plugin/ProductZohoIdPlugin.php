<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use RTech\Zoho\Api\Data\ZohoInventoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductZohoIdPlugin {
  const ITEM_TYPES = array('simple', 'virtual', 'downloadable');
  const GROUP_TYPES = array('configurable', 'grouped', 'bundle');

  const ZOHO_ITEM = 'item';
  const ZOHO_GROUP = 'group';

  protected $zohoClient;
  protected $zohoInventoryRepository;
  protected $zohoInventoryFactory;
  protected $messageManager;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \RTech\Zoho\Model\ZohoInventoryFactory $zohoInventoryFactory,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->zohoClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->zohoInventoryRepository = $zohoInventoryRepository;
    $this->zohoInventoryFactory = $zohoInventoryFactory;
    $this->messageManager = $messageManager;
  }

  public function afterGetById (
    \Magento\Catalog\Api\ProductRepositoryInterface $subject,
    \Magento\Catalog\Api\Data\ProductInterface $product
  ) {
    
    try {
      // Find the Zoho inventory id for the product unless the product is a bundle
      // since bundled products are not a Zoho inventory concept as a bundle is
      // simply a collection of other products 
      if ($product->getTypeId() != 'bundle') {
        $zohoInventory = $this->zohoInventoryRepository->getById($product->getId());
      }
    } catch (NoSuchEntityException $eNoSuchEntityException) {
      // Need to verify that the product is not simply missing from the Zoho Inventory
      // table by verifying that there is no Zoho Inventory product with the same name and sku
      try {
        $inventoryItem = $this->zohoClient->getItemByName($product->getName());
        if ($inventoryItem['name'] == $product->getName() && $inventoryItem['sku'] == $product->getSku()) {
          $zohoInventory = $this->zohoInventoryFactory->create();
          $zohoInventory->setData([
            ZohoInventoryInterface::PRODUCT_ID => $product->getId(),
            ZohoInventoryInterface::PRODUCT_NAME => $product->getName(),
            ZohoInventoryInterface::ZOHO_ID => $inventoryItem['item_id'],
            ZohoInventoryInterface::ZOHO_TYPE => in_array($product->getTypeId(), self::ITEM_TYPES) ? self::ZOHO_ITEM : self::ZOHO_GROUP
          ]);
          $this->zohoInventoryRepository->save($zohoInventory);
          $this->zohoClient->itemSetActive($inventoryItem['item_id']);
          $this->messageManager->addNotice('Created Zoho inventory item association for product "' . $product->getName() . '"');
        }
      } catch (ZohoItemNotFoundException $eZohoItemNotFoundException) {
        //throw new NoSuchEntityException($eNoSuchEntityException->getMessage());
      }
    }

    return $product;
  }

}
