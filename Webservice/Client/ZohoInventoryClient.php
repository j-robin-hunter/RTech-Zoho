<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Webservice\Client;

use RTech\Zoho\Webservice\Exception\ZohoCommunicationException;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use RTech\Zoho\Webservice\Exception\ZohoItemExistsException;
use RTech\Zoho\Api\Data\ZohoInventoryClientInterface;
use RTech\Zoho\Webservice\Client\AbstractZohoClient;

class ZohoInventoryClient extends AbstractZohoClient implements ZohoInventoryClientInterface {

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager
  ) {
    $storeId = $storeManager->getStore()->getId();
    $endpoint = $configData->getZohoInventoryEndpoint($storeId);
    $key = $configData->getZohoInventoryKey($storeId);
    $organisationId = $configData->getZohoOrganistionId($storeId);
    parent::__construct($zendClient, $endpoint, $key, $organisationId);
  }

  /**
  * @inheritdoc
  */
  public function itemAdd($item) {
    try {
      return $this->callZoho(self::ITEMS_API, self::POST, ['JSONString' => json_encode($item, true)])['item'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroupAdd($itemGroup) {
    try {
      return $this->callZoho(self::ITEM_GROUPING_API, self::POST, ['JSONString' => json_encode($itemGroup, true)])['item_group'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemGroupAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemCompositeAdd($compositeItem) {
    try {
      return $this->callZoho(self::ITEM_COMPOSITE_API, self::POST, ['JSONString' => json_encode($compositeItem, true)])['composite_item'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemCompositeAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function imageAdd($itemId, $imageFile) {
    try {
      return $this->callZoho(self::ITEMS_API . '/' . $itemId . '/image', self::POST, [], $imageFile);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: imageAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function packageAdd($salesOrderId, $package) {
    try {
      return $this->callZoho(self::PACKAGES_API, self::POST, ['salesorder_id' => $salesOrderId, 'JSONString' => json_encode($package, true)])['package'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: packageAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function packageDelete($packageId) {
    try {
      $this->callZoho(self::PACKAGES_API . '/' . $packageId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: packageDelete'), ['exception' => $e]);
      throw $e;
    }
  }
  
  /**
  * @inheritdoc
  */
  public function shipmentAdd($package, $shipment) {
    try {
      return $this->callZoho(self::SHIPMENTS_API, self::POST, [
        'salesorder_id' => $package['salesorder_id'],
        'package_ids' => $package['package_id'],
        'JSONString' => json_encode($shipment, true)
      ])['shipmentorder'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: shipmentAdd'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getShipment($shipmentId) {
    try {
      return $this->callZoho(self::SHIPMENTS_API . '/' . $shipmentId, self::GET, [])['shipmentorder'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getShipment'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function shipmentDelete($shipmentId) {
    try {
      $this->callZoho(self::SHIPMENTS_API . '/' . $shipmentId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: shipmentDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getItemByName($itemName) {
    try {
      $response = $this->callZoho(self::ITEMS_API, self::GET, ['name' => $itemName]);
      return count($response['items']) == 1 ? $response['items'][0] : null;
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getItemByName'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getItemGroup($groupId) {
    try {
      return $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId, self::GET, [])['item_group'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getItemGroup'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getItemGroupByName($groupName) {
    try {
      $response = $this->callZoho(self::ITEM_GROUPS_API, self::GET, ['group_name' => $groupName]);
      return count($response['itemgroups']) == 1 ? $response['itemgroups'][0] : null;
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getItemGroupByName'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getCompositeItem($compositeItemId) {
    try {
      return $this->callZoho(self::ITEM_COMPOSITE_API . '/' . $compositeItemId, self::GET, [])['composite_item'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getCompositeItem'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getCompositeItemByName($compositeName) {
    try {
      $response = $this->callZoho(self::ITEM_COMPOSITE_API, self::GET, ['name' => $compositeName]);
      return count($response['composite_items']) == 1 ? $response['composite_items'][0] : null;
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getCompositeItemByName'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getSalesOrder($salesOrderId) {
    try {
      $response = $this->callZoho(self::SALESORDERS_API . '/' . $salesOrderId, self::GET, []);
      if (isset($response['salesorder'])) {
        return $response['salesorder'];
      }
      throw new ZohoItemNotFoundException(__('No sales order'));
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getSalesOrder'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemUpdate($item) {
    try  {
      unset($item['documents']); // Currently, if this is set then the API call will fail!!
      return $this->callZoho(self::ITEMS_API . '/' . $item['item_id'], self::PUT, ['JSONString' => json_encode($item, true)])['item'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemUpdate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroupUpdate($itemGroup) {
    try {
      $this->callZoho(self::ITEM_GROUPING_API . '/' . $itemGroup['group_id'], self::PUT, ['JSONString' => json_encode($itemGroup, true)]);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemGroupUpdate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemCompositeUpdate($compositeItem) {
    try {
      $this->callZoho(self::ITEM_COMPOSITE_API . '/' . $compositeItem['composite_item_id'], self::PUT, ['JSONString' => json_encode($compositeItem, true)]);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemCompositeUpdate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemDelete($itemId) {
    try {
      $this->callZoho(self::ITEMS_API . '/' . $itemId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroup($groupId, $itemId) {

  }

  /**
  * @inheritdoc
  */
  public function itemUngroup($itemId) {
    try {
      $this->callZoho(self::ITEM_UNGROUP_API, self::POST, ['item_ids' => $itemId]);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemUngroup'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemSetActive($itemId) {
    try {
      $this->callZoho(self::ITEMS_API . '/' . $itemId . '/active', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemSetActive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemSetInactive($itemId) {
    try {
      $this->callZoho(self::ITEMS_API . '/' . $itemId . '/inactive', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemSetInactive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroupDelete($groupId) {
    try {
      $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemGroupDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroupSetActive($groupId) {
    try {
      $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId . '/active', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemGroupSetActive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemGroupSetInactive($groupId) {
    try {
      $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId . '/inactive', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemGroupSetInactive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemCompositeDelete($compositeItem) {
    try {
      $this->callZoho(self::ITEM_COMPOSITE_API . '/' . $compositeItem, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemCompositeDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemCompositeSetActive($compositeItemId) {
    try {
      $this->callZoho(self::ITEM_COMPOSITE_API . '/' . $compositeItemId . '/active', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemCompositeSetActive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function itemCompositeSetInactive($compositeItemId) {
    try {
      $this->callZoho(self::ITEM_COMPOSITE_API . '/' . $compositeItemId . '/inactive', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: itemCompositeSetInactive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function imageDelete($itemId) {
    try {
      return $this->callZoho(self::ITEMS_API . '/' . $itemId . '/image', self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: imageDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */ 
  public function getSalesReturn($returnId) {
    try {
      return $this->callZoho(self::SALES_RETURN_API . '/' . $returnId, self::GET, [])['salesreturn'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getSalesReturn'), ['exception' => $e]);
      throw $e;
    }    
  }
  
  /**
  * @inheritdoc
  */   
  public function salesReturnDelete($returnId) {
    try {
      return $this->callZoho(self::SALES_RETURN_API . '/' . $returnId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: salesReturnDelete'), ['exception' => $e]);
      throw $e;
    }    
  }

  /**
  * @inheritdoc
  */ 
  public function salesReturnReceivableDelete($receivableId) {
    try {
      return $this->callZoho(self::SALES_RETURN_RECEIVABLE_API . '/' . $receivableId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: salesReturnReceivableDelete'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addCreditNote($salesReturnId, $creditNote) {
    try {
      return $creditNote = $this->callZoho(self::CREDIT_NOTES_API, self::POST, [
        'salesreturn_id' => $salesReturnId,
        'JSONString' => json_encode($creditNote, true)])['creditnote'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addCreditNote'), ['exception' => $e]);
      throw $e;
    }
  }


  /**
  * @inheritdoc
  */
  public function applyCredit($creditNoteId, $credits) {
    try {
      $this->callZoho(self::CREDIT_NOTES_API . '/' . $creditNoteId . '/invoices', self::POST, ['JSONString' => json_encode($credits, true)]);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: applyCredits'), ['exception' => $e]);
      throw $e;
    }
  }
}
