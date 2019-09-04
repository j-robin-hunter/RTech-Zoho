<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;
use RTech\Zoho\Webservice\Client\ZohoInventoryClient;

class SalesOrderShipmentSaveBefore implements ObserverInterface {

  protected $_zohoClient;
  protected $_zohoInventoryRepository;
  protected $_stockRegistry;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
  ) {
    $this->_zohoClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_stockRegistry = $stockRegistry;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $shipment = $observer->getEvent()->getShipment();
    $order = $shipment->getOrder();
    foreach ($order->getAllItems() as $item) {
      $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());
      $zohoItem = $this->_zohoClient->getItem($zohoInventory->getZohoId());
      $sku = $item->getSku();
      $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
      if ($stockItem->getManageStock()) {
        $stockItem->setQty($zohoItem['available_stock']);
        $stockItem->setIsInStock(true);
        $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
      }
    }
  }

}
