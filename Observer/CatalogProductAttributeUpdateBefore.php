<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use Magento\Framework\Event\ObserverInterface;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;

class CatalogProductAttributeUpdateBefore implements ObserverInterface {
  const ENABLED = 1;
  const DISABLED = 2;

  const ZOHO_ITEM = 'item';
  const ZOHO_GROUP = 'group';

  protected $_zohoClient;
  protected $_zohoInventoryRepository;
  protected $_messageManager;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->_zohoClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_messageManager = $messageManager;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    if (isset($observer->getData('attributes_data')['status'])) {
      $status = $observer->getData('attributes_data')['status'];
      foreach ($observer->getData('product_ids') as $productId) {
        $zohoInventory = $this->_zohoInventoryRepository->getId($productId);
        try {
          if ($zohoInventory->getZohoType() == self::ZOHO_ITEM) {
            ($status == self::ENABLED)?$this->_zohoClient->itemSetActive($zohoInventory->getZohoId()):$this->_zohoClient->itemSetInActive($zohoInventory->getZohoId());
          } else {
            ($status == self::ENABLED)?$this->_zohoClient->itemGroupSetActive($zohoInventory->getZohoId()):$this->_zohoClient->itemGroupSetInActive($zohoInventory->getZohoId());
          }
        } catch (\Exception $ex) {
          $this->_messageManager->addNotice('Unable to change status for product "' . $zohoInventory->getProductName() . '" due to ' . $ex->getMessage());
        }
      }
    }
  }
}
