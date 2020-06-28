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
use RTech\Zoho\Api\Data\ZohoClientInterface;

abstract class AbstractZohoClient implements ZohoClientInterface {
  const CONTACTS_API = 'contacts';
  const ESTIMATES_API = 'estimates';
  const SALESORDERS_API = 'salesorders';
  const INVOICES_API = 'invoices';
  const ITEMS_API = 'items';
  const ITEM_GROUPS_API = 'itemgroups';
  const ITEM_GROUPING_API = 'items/grouping';
  const ITEM_COMPOSITE_API = 'compositeitems';
  const ITEM_UNGROUP_API = 'items/ungroup';
  const PACKAGES_API = 'packages';
  const SHIPMENTS_API = 'shipmentorders';
  const TAXES_API = 'settings/taxes';
  const CREDIT_NOTES_API = 'creditnotes';
  const SALES_RETURN_API = 'salesreturns';
  const SALES_RETURN_RECEIVABLE_API = 'salesreturnreceives';

  const GET = \Zend\Http\Request::METHOD_GET;
  const POST = \Zend\Http\Request::METHOD_POST;
  const PUT = \Zend\Http\Request::METHOD_PUT;
  const DELETE = \Zend\Http\Request::METHOD_DELETE;

  const NO_ZOHO_INVENTORY_CODE = 2006;
  const EXISTS_ZOHO_INVENTORY_CODE = 1001;

  protected $_zendClient;
  protected $_endpoint;
  protected $_key;
  protected $_organisationId;
  protected $_logger;

  public function __construct(
    \Zend\Http\Client $zendClient,
    string $endpoint,
    string $key,
    string $organisationId
  ) {
    $this->_zendClient = $zendClient;
    $this->_endpoint = $endpoint;
    $this->_key = $key;
    $this->_organisationId = $organisationId;
    $this->_logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
  }

  protected function callZoho($api, $method, $parameters, $imageFile=null) {
    $this->_zendClient->reset();
    $this->_zendClient->setUri($this->_endpoint . '/' . $api);
    $this->_zendClient->setMethod($method);

    $parameters['authtoken'] = $this->_key;
    $parameters['organization_id'] = $this->_organisationId;

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
      $zohoError = json_decode($response->getBody(), true);
      $zohoErrorCode = $zohoError['code'];
      $zohoErrorMessage = $zohoError['message'];
      if ($zohoErrorCode == self::NO_ZOHO_INVENTORY_CODE || $response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
        throw new ZohoItemNotFoundException($zohoErrorMessage, $zohoErrorCode);
      }
      if ($zohoErrorCode == self::EXISTS_ZOHO_INVENTORY_CODE) {
        throw new ZohoItemExistsException($zohoErrorMessage, $zohoErrorCode);
      }
      throw new ZohoOperationException($zohoErrorMessage, $zohoErrorCode);
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
  public function getInvoice($invoiceId) {
    try {
      return $this->callZoho(self::INVOICES_API . '/' . $invoiceId, self::GET, [])['invoice'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getInvoice'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getTaxes() {
    try {
      return $this->callZoho(self::TAXES_API, self::GET, [])['taxes'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getTaxes'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getItem($itemId) {
    try {
      return $this->callZoho(self::ITEMS_API . '/' . $itemId, self::GET, [])['item'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getItem'), ['exception' => $e]);
      throw $e;
    }
  }
}
