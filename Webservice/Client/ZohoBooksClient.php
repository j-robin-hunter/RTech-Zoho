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
use RTech\Zoho\Webservice\Client\AbstractZohoClient;

class ZohoBooksClient extends AbstractZohoClient implements ZohoBooksClientInterface {

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager
  ) {
    $storeId = $storeManager->getStore()->getId();
    $endpoint = $configData->getZohoBooksEndpoint($storeId);
    $key = $configData->getZohoBooksKey($storeId);
    $organisationId = $configData->getZohoOrganistionId($storeId);
    parent::__construct($zendClient, $endpoint, $key, $organisationId);
  }

  /**
  * @inheritdoc
  */
  public function lookupContact($contactName, $email) {
    $response = $this->callZoho(self::CONTACTS_API, self::GET, ['contact_name' => $contactName, 'email' => $email]);
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

  /**
  * @inheritdoc
  */
  public function addEstimate($estimate) {
    return $this->callZoho(self::ESTIMATES_API, self::POST, ['JSONString' => json_encode($estimate, true)])['estimate'];
  }

  /**
  * @inheritdoc
  */
  public function updateEstimate($estimateId, $estimate) {
    return $this->callZoho(self::ESTIMATES_API .'/' . $estimateId, self::PUT, ['JSONString' => json_encode($estimate, true)])['estimate'];
  }

  /**
  * @inheritdoc
  */
  public function markEstimateSent($estimateId) {
    $this->callZoho(self::ESTIMATES_API .'/' . $estimateId . '/status/sent', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function markEstimateAccepted($estimateId) {
    $this->callZoho(self::ESTIMATES_API .'/' . $estimateId . '/status/accepted', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function deleteEstimate($estimateId) {
    if ($estimateId) {
      $this->callZoho(self::ESTIMATES_API .'/' . $estimateId, self::DELETE, []);
    }
  }

  /**
  * @inheritdoc
  */
  public function addSalesOrder($salesOrder) {
    return $this->callZoho(self::SALESORDERS_API, self::POST, ['JSONString' => json_encode($salesOrder, true)])['salesorder'];
  }

  /**
  * @inheritdoc
  */
  public function markSalesOrderOpen($salesOrderId) {
    $this->callZoho(self::SALESORDERS_API .'/' . $salesOrderId . '/status/open', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function convertSalesOrderToInvoice($salesOrderId) {
    return $this->callZoho(self::INVOICES_API .'/fromsalesorder' , self::POST, ['salesorder_id' => $salesOrderId])['invoice'];
  }

  /**
  * @inheritdoc
  */
  public function deleteSalesOrder($salesOrderId) {
    if ($salesOrderId) {
      $this->callZoho(self::SALESORDERS_API .'/' . $salesOrderId, self::DELETE, []);
    }
  }

  /**
  * @inheritdoc
  */
  public function updateInvoice($invoiceId, $invoice) {
    return $this->callZoho(self::INVOICES_API . '/' . $invoiceId, self::PUT, ['JSONString' => json_encode($invoice, true)])['invoice'];
  }


  /**
  * @inheritdoc
  */
  public function markInvoiceSent($invoiceId) {
    $this->callZoho(self::INVOICES_API .'/' . $invoiceId . '/status/sent', self::POST, []);
  }

  /**
  * @inheritdoc
  */
  public function deleteInvoice($invoiceId) {
    if ($invoiceId) {
      $this->callZoho(self::INVOICES_API .'/' . $invoiceId, self::DELETE, []);
    }
  }

  /**
  * @inheritdoc
  */
  public function getAddress($addressId) {
    return $this->callZoho(self::CONTACTS_API . '/' . $addressId . '/address', self::GET, [])['contact'];
  }

  /**
  * @inheritdoc
  */
  public function addAddress($contactId, $address) {
    return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address', self::POST, ['JSONString' => json_encode($address, true)])['address_info'];
  }

  /**
  * @inheritdoc
  */
  public function updateAddress($contactId, $addressId, $address) {
    return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address/' . $addressId, self::PUT, ['JSONString' => json_encode($address, true)])['address_info'];
  }

  /**
  * @inheritdoc
  */
  public function deleteAddress($contactId, $addressId) {
    return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address/' . $addressId, self::DELETE, []);
  }
}
