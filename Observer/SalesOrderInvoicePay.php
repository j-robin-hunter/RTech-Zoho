<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;
use RTech\Zoho\Webservice\Client\ZohoInventoryClient;

class SalesOrderInvoicePay implements ObserverInterface {

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
    $this->_logger->info('SalesOrderInvoicePay');

    $invoice = $observer->getEvent()->getInvoice();
    $this->_logger->info(get_class($invoice));
    $order = $invoice->getOrder();
    $this->_logger->info(get_class($order));

    $this->_logger->info($order->getTotalPaid());
    $order->setTotalPaid(0);
    $order->setBaseTotalPaid(0);
  }

}