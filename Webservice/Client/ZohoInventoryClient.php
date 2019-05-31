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

class ZohoInventoryClient implements ZohoInventoryClientInterface {
  const ITEMS_API = 'items';
  const ITEM_GROUPS_API = 'itemgroups';
  const ITEM_UNGROUP_API = 'items/ungroup';
  const TAXES_API = 'settings/taxes';

  const GET = \Zend\Http\Request::METHOD_GET;
  const POST = \Zend\Http\Request::METHOD_POST;
  const PUT = \Zend\Http\Request::METHOD_PUT;
  const DELETE = \Zend\Http\Request::METHOD_DELETE;

  const NO_ZOHO_INVENTORY_CODE = 2006;
  const EXISTS_ZOHO_INVENTORY_CODE = 1001;

  protected $_configData;
  protected $_zendClient;
  protected $_storeId;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager
  ) {
    $this->_configData = $configData;
    $this->_zendClient = $zendClient;
    $this->_storeId = $storeManager->getStore()->getId();
  }

  protected function callZoho($api, $method, $parameters, $imageFile=null) {
    $this->_zendClient->reset();
    $this->_zendClient->setUri($this->_configData->getZohoInventoryEndpoint($this->_storeId) . '/' . $api);
    $this->_zendClient->setMethod($method);

    $parameters['authtoken'] = $this->_configData->getZohoInventoryKey($this->_storeId);
    $parameters['organization_id'] = $this->_configData->getZohoOrganistionId($this->_storeId);

    if ($method === self::GET || $method === self::DELETE) {
      $this->_zendClient->setParameterGet($parameters);
    } else {
      $this->_zendClient->setParameterPost($parameters);
      if ($imageFile) {
        $this->_zendClient->setEncType('image/' . pathinfo($imageFile, PATHINFO_EXTENSION));
        $this->_zendClient->setFileUpload($imageFile,  'image');
      }
    }

    try {
      $this->_zendClient->send();
      $response = $this->_zendClient->getResponse();
    }
    catch (\Zend\Http\Exception\RuntimeException $runtimeException) {
      throw ZohoCommunicationException::runtime($runtimeException->getMessage());
    }
    $errorCodes = [
      \Zend\Http\Response::STATUS_CODE_400,
      \Zend\Http\Response::STATUS_CODE_401,
      \Zend\Http\Response::STATUS_CODE_403,
      \Zend\Http\Response::STATUS_CODE_404,
      \Zend\Http\Response::STATUS_CODE_405,
      \Zend\Http\Response::STATUS_CODE_406,
      \Zend\Http\Response::STATUS_CODE_429,
      \Zend\Http\Response::STATUS_CODE_500,
    ];
    if (in_array($response->getStatusCode(), $errorCodes)) {
      $zohoCode = json_decode($response->getBody(), true)['code'];
      if ($zohoCode == self::NO_ZOHO_INVENTORY_CODE || $response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
        throw ZohoItemNotFoundException::create($response->getBody());
      }
      if ($zohoCode == self::EXISTS_ZOHO_INVENTORY_CODE) {
        throw ZohoItemExistsException::create($response->getBody());
      }
      throw ZohoOperationException::create($response->getBody());
    }
    // unknown error response codes
    if (!$response->isSuccess()) {
      throw new ZohoCommunicationException($response->getBody());
    }
    return json_decode($response->getBody(), true);
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
  public function getTaxes() {
    return $this->callZoho(self::TAXES_API, self::GET, [])['taxes'];
  }

  /**
  * @inheritdoc
  */
  public function getItem($itemId) {
    return $this->callZoho(self::ITEMS_API . '/' . $itemId, self::GET, [])['item'];
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
