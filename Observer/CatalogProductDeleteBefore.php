<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use Magento\Framework\Event\ObserverInterface;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;

class CatalogProductDeleteBefore implements ObserverInterface {
  const ZOHO_ITEM = 'item';
  const ZOHO_GROUP = 'group';
  const ZOHO_COMPOSITE = 'composite';

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
    $product = $observer->getProduct();

    try {
      $zohoInventory = $this->_zohoInventoryRepository->getById($product->getId());
      if ($zohoInventory->getZohoType() == self::ZOHO_ITEM) {

        //****************
        // **** ITEMS ****
        //****************
        try {
          $this->_zohoClient->itemDelete($zohoInventory->getZohoId());
        } catch (ZohoOperationException $e) {
          // Item is in use in a transaction so mark as inactive and ungroup
          $this->_zohoClient->itemSetInactive($zohoInventory->getZohoId());
          $this->_zohoClient->itemUngroup($zohoInventory->getZohoId());
          $this->_messageManager->addNotice('Zoho inventory item "' . $zohoInventory->getProductName() . '" set to inactive');
        } catch (ZohoItemNotFoundException $e) {
          $this->_messageManager->addNotice('Zoho inventory item for "' . $zohoInventory->getProductName() . '" not found');
        }
      } elseif ($zohoInventory->getZohoType() == self::ZOHO_COMPOSITE) {

        //******************
        // **** BUNDLES ****
        //******************
        try {
          $this->_zohoClient->itemCompositeDelete($zohoInventory->getZohoId());
        } catch (ZohoOperationException $e) {
          // Item is in use in a transaction so mark as inactive
          $this->_zohoClient->itemCompositeInactive($zohoInventory->getZohoId());
          $this->_messageManager->addNotice('Zoho inventory item "' . $zohoInventory->getProductName() . '" set to inactive');
        } catch (ZohoItemNotFoundException $e) {
          $this->_messageManager->addNotice('Zoho inventory item for "' . $zohoInventory->getProductName() . '" not found');
        }
      } else {

        //*****************
        // **** GROUPS ****
        //*****************
        foreach ($product->getTypeInstance()->getUsedProducts($product) as $child) {
          $zohoInventory = $this->_zohoInventoryRepository->getById($child->getId());
          try {
            $this->_zohoClient->itemUngroup($zohoInventory->getZohoId());
          } catch (ZohoItemNotFoundException $e) {
            $this->_messageManager->addNotice('Zoho Inventory group item "' . $zohoInventory->getProductName() . '" not found');
          }
        }
        try {
            $zohoInventory = $this->_zohoInventoryRepository->getById($product->getId());
            $this->_zohoClient->itemGroupDelete($zohoInventory->getZohoId());
        } catch (ZohoItemNotFoundException $e) {
          $this->_messageManager->addNotice('Zoho inventory group for "' . $zohoInventory->getProductName() . '" not found');
        }
      }
    } catch (NoSuchEntityException $e) {
      // Do nothing
    } catch (ZohoOperationException $e) {
      $this->_messageManager->addNotice('Zoho inventory error ' . $e->getMessage() . '. Product may not have been deleted from Zoho.');
    }
  }
}
