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
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('SalesOrderShipmentSaveBefore');

    $shipment = $observer->getEvent()->getShipment();
    $order = $shipment->getOrder();
    foreach ($order->getAllItems() as $item) {
      $this->_logger->info('Product id: ' . $item->getProductId());
      $this->_logger->info('Qty to invoice: ' . $item->getQtyToInvoice());
      $this->_logger->info('Qty ordered: ' . $item->getQtyOrdered());
      $this->_logger->info('Qty backordered: ' . $item->getQtyBackordered());
      $zohoInventory = $this->_zohoInventoryRepository->getId($item->getProductId());
      $this->_logger->info('Zoho id: ' . $zohoInventory->getZohoId());
      $zohoItem = $this->_zohoClient->getItem($zohoInventory->getZohoId());
      $sku = $item->getSku();
      $this->_logger->info('Product sku: ' . $sku);
      $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
      if ($stockItem->getManageStock()) {
        $this->_logger->info($stockItem->getQty());
        $this->_logger->info('Zoho available stock: ' . $zohoItem['available_stock']);
        $stockItem->setQty($zohoItem['available_stock']);
        $stockItem->setIsInStock(true);
        $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
      }
    }
    bust;
  }

}