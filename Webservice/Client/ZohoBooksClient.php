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
use RTech\Zoho\Api\Data\ZohoBooksClientInterface;

class ZohoBooksClient implements ZohoBooksClientInterface {
  const CONTACTS_API = 'contacts';

  const GET = \Zend\Http\Request::METHOD_GET;
  const POST = \Zend\Http\Request::METHOD_POST;
  const PUT = \Zend\Http\Request::METHOD_PUT;
  const DELETE = \Zend\Http\Request::METHOD_DELETE;


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

  protected function callZoho($api, $method, $parameters) {
    $this->_zendClient->reset();
    $this->_zendClient->setUri($this->_configData->getZohoBooksEndpoint($this->_storeId) . '/' . $api);
    $this->_zendClient->setMethod($method);

    $parameters['authtoken'] = $this->_configData->getZohoBooksKey($this->_storeId);
    $parameters['organization_id'] = $this->_configData->getZohoOrganistionId($this->_storeId);

    if ($method === self::GET || $method === self::DELETE) {
      $this->_zendClient->setParameterGet($parameters);
    } else {
      $this->_zendClient->setParameterPost($parameters);
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
  public function lookupContact($contactName) {
    $response = $this->callZoho(self::CONTACTS_API, self::GET, ['contact_name' => $contactName]);
    return count($response['contacts']) == 1 ? $response['contacts'][0] : null;
  }

  /**
  * @inheritdoc
  */
  public function addContact($contact) {
    return $this->callZoho(self::CONTACTS_API, self::POST, ['JSONString' => json_encode($contact, true)])['contact'];
  }

  /**
  * @inheritdoc
  */
  public function getContact($contactId) {
    return $this->callZoho(self::CONTACTS_API . '/' . $contactId, self::GET, [])['contact'];
  }

  /**
  * @inheritdoc
  */
  public function updateContact($contact) {
    return $this->callZoho(self::CONTACTS_API . '/' . $contact['contact_id'], self::PUT, ['JSONString' => json_encode($contact, true)])['contact'];
  }

  /**
  * @inheritdoc
  */
  public function deleteContact($contactId) {
    $this->callZoho(self::CONTACTS_API . '/' . $contactId, self::DELETE, []);
  }

  /**
  * @inheritdoc
  */
  public function contactSetInactive($contactId) {
    $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/inactive', self::POST, []);
  }
}