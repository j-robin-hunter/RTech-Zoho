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
    return $this->callZoho(self::ITEMS_API, self::POST, ['JSONString' => json_encode($item, true)])['item'];
  }

  /**
  * @inheritdoc
  */
  public function itemGroupAdd($itemGroup) {
    return $this->callZoho(self::ITEM_GROUPS_API, self::POST, ['JSONString' => json_encode($itemGroup, true)])['item_group'];
  }

  /**
  * @inheritdoc
  */
  public function imageAdd($itemId, $imageFile) {
    return $this->callZoho(self::ITEMS_API . '/' . $itemId . '/image', self::POST, [], $imageFile);
  }

  /**
  * @inheritdoc
  */
  public function packageAdd($salesOrderId, $package) {
    try {
      return $this->callZoho(self::PACKAGES_API, self::POST, ['salesorder_id' => $salesOrderId, 'JSONString' => json_encode($package, true)])['package'];
    } catch (\Exception $e) {
      throw new $e;
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
      ])['shipment_order'];
    } catch (\Exception $e) {
      throw new $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getItemByName($itemName) {
    $response = $this->callZoho(self::ITEMS_API, self::GET, ['name' => $itemName]);
    return count($response['items']) == 1 ? $response['items'][0] : null;
  }

  /**
  * @inheritdoc
  */
  public function getItemGroup($groupId) {
    return $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId, self::GET, [])['item_group'];
  }

  /**
  * @inheritdoc
  */
  public function getItemGroupByName($groupName) {
    $response = $this->callZoho(self::ITEM_GROUPS_API, self::GET, ['group_name' => $groupName]);
    return count($response['itemgroups']) == 1 ? $response['itemgroups'][0] : null;
  }

  /**
  * @inheritdoc
  */
  public function getSalesOrder($salesOrderId) {
    return $this->callZoho(self::SALESORDERS_API . '/' . $salesOrderId, self::GET, [])['salesorder'];
  }

  /**
  * @inheritdoc
  */
  public function itemUpdate($item) {
    unset($item['documents']); // Currently, if this is set then the API call will fail!!
    return $this->callZoho(self::ITEMS_API . '/' . $item['item_id'], self::PUT, ['JSONString' => json_encode($item, true)])['item'];
  }

  /**
  * @inheritdoc
  */
  public function itemGroupUpdate($itemGroup) {
    $this->callZoho(self::ITEM_GROUPS_API . '/' . $itemGroup['group_id'], self::PUT, ['JSONString' => json_encode($itemGroup, true)]);
  }

  /**
  * @inheritdoc
  */
  public function itemDelete($itemId) {
    $this->callZoho(self::ITEMS_API . '/' . $itemId, self::DELETE, []);
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
    $this->callZoho(self::ITEM_UNGROUP_API, self::POST, ['item_ids' => $itemId]);
  }

  /**
  * @inheritdoc
  */
  public function itemSetActive($itemId) {
    $this->callZoho(self::ITEMS_API . '/' . $itemId . '/active', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function itemSetInactive($itemId) {
    $this->callZoho(self::ITEMS_API . '/' . $itemId . '/inactive', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function itemGroupDelete($itemGroupId) {
    $this->callZoho(self::ITEM_GROUPS_API . '/' . $itemGroupId, self::DELETE, []);
  }

  /**
  * @inheritdoc
  */
  public function itemGroupSetActive($groupId) {
    $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId . '/active', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function itemGroupSetInactive($groupId) {
    $this->callZoho(self::ITEM_GROUPS_API . '/' . $groupId . '/inactive', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function imageDelete($itemId) {
    return $this->callZoho(self::ITEMS_API . '/' . $itemId . '/image', self::DELETE, []);
  }
}
