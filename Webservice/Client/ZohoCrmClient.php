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
use RTech\Zoho\Api\Data\ZohoCrmClientInterface;
use RTech\Zoho\Webservice\Client\AbstractZohoClient;

class ZohoCrmClient extends AbstractZohoClient implements ZohoCrmClientInterface {

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager
  ) {
    $storeId = $storeManager->getStore()->getId();
    $endpoint = $configData->getZohoCrmEndpoint($storeId);
    parent::__construct($zendClient, $configData, $storeManager, $endpoint);
  }

  /**
  * @inheritdoc
  */
  public function getRecord($module, $id) {
    if (!$module || !$id) {
      return null;
    }
    try {
      return $this->callZoho($module .'/' . $id, self::GET, [])['data'][0];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getRecord'), ['exception' => $e]);
      throw $e;
    }
  }
}