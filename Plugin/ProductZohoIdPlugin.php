<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
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
      $zohoInventory = $this->zohoInventoryRepository->getId($product->getId());
    } catch (NoSuchEntityException $eNoSuchEntityException) {
      // Need to verify that the product is not simply missing from the Zoho Inventory
      // table by verifying that there is no Zoho Inventory product with the same name and sku
      try {
        $inventoryItem = $this->zohoClient->getItemByName($product->getName());
        if ($inventoryItem['name'] == $product->getName() && $inventoryItem['sku'] == $product->getSku()) {
          $zohoInventory = $this->zohoInventoryFactory->create();
          $zohoInventory->setData([
            'product_id' => $product->getId(),
            'product_name'=> $product->getName(),
            'zoho_id' => $inventoryItem['item_id'],
            'zoho_type' => in_array($product->getTypeId(), self::ITEM_TYPES) ? self::ZOHO_ITEM : self::ZOHO_GROUP
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
