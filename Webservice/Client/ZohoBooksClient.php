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
    $this->_logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
    parent::__construct($zendClient, $endpoint, $key, $organisationId);
  }

  /**
  * @inheritdoc
  */
  public function lookupContact($contactName, $email) {
    try {
      if (!empty($contactName)) {
        $response = $this->callZoho(self::CONTACTS_API, self::GET, ['contact_name' => $contactName, 'email' => $email]);
      } else {
        $response = $this->callZoho(self::CONTACTS_API, self::GET, ['email' => $email]);
      }
      return count($response['contacts']) == 1 ? $response['contacts'][0] : null;
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: lookupContact'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addContact($contact) {
    try {
      return $this->callZoho(self::CONTACTS_API, self::POST, ['JSONString' => json_encode($contact, true)])['contact'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addContact'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getContact($contactId) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $contactId, self::GET, [])['contact'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getContact'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function updateContact($contact) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $contact['contact_id'], self::PUT, ['JSONString' => json_encode($contact, true)])['contact'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: updateContact'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function deleteContact($contactId) {
    try {
      $this->callZoho(self::CONTACTS_API . '/' . $contactId, self::DELETE, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: deleteContact'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function contactSetInactive($contactId) {
    try {
      $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/inactive', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: contactSetInactive'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addEstimate($estimate) {
    try {
      return $this->callZoho(self::ESTIMATES_API, self::POST, ['JSONString' => json_encode($estimate, true)])['estimate'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addEstimate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function updateEstimate($estimateId, $estimate) {
    try {
      return $this->callZoho(self::ESTIMATES_API .'/' . $estimateId, self::PUT, ['JSONString' => json_encode($estimate, true)])['estimate'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: updateEstimate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function markEstimateSent($estimateId) {
    try {
      $this->callZoho(self::ESTIMATES_API .'/' . $estimateId . '/status/sent', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: markEstimateSent'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function markEstimateAccepted($estimateId) {
    try {
      $this->callZoho(self::ESTIMATES_API .'/' . $estimateId . '/status/accepted', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: markEstimateAccepted'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function deleteEstimate($estimateId) {
    try {
      if ($estimateId) {
        $this->callZoho(self::ESTIMATES_API .'/' . $estimateId, self::DELETE, []);
      }
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: deleteEstimate'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addSalesOrder($salesOrder) {
    try {
      return $this->callZoho(self::SALESORDERS_API, self::POST, ['JSONString' => json_encode($salesOrder, true)])['salesorder'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addSalesOrder'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function markSalesOrderOpen($salesOrderId) {
    try {
      $this->callZoho(self::SALESORDERS_API .'/' . $salesOrderId . '/status/open', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: markSalesOrderOpen'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function convertSalesOrderToInvoice($salesOrderId) {
    try {
      return $this->callZoho(self::INVOICES_API .'/fromsalesorder' , self::POST, ['salesorder_id' => $salesOrderId])['invoice'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: convertSalesOrderToInvoice'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function deleteSalesOrder($salesOrderId) {
    try {
      if ($salesOrderId) {
        $this->callZoho(self::SALESORDERS_API .'/' . $salesOrderId, self::DELETE, []);
      }
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: deleteSalesOrder'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function updateInvoice($invoiceId, $invoice) {
    try {
      return $this->callZoho(self::INVOICES_API . '/' . $invoiceId, self::PUT, ['JSONString' => json_encode($invoice, true)])['invoice'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: updateInvoice'), ['exception' => $e]);
      throw $e;
    }
  }


  /**
  * @inheritdoc
  */
  public function markInvoiceSent($invoiceId) {
    try {
      $this->callZoho(self::INVOICES_API .'/' . $invoiceId . '/status/sent', self::POST, []);
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: markInvoiceSent'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function deleteInvoice($invoiceId) {
    try {
      if ($invoiceId) {
        $this->callZoho(self::INVOICES_API .'/' . $invoiceId, self::DELETE, []);
      }
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: deleteInvoice'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function getAddress($addressId) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $addressId . '/address', self::GET, [])['contact'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: getAddress'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addAddress($contactId, $address) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address', self::POST, ['JSONString' => json_encode($address, true)])['address_info'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addAddress'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function updateAddress($contactId, $addressId, $address) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address/' . $addressId, self::PUT, ['JSONString' => json_encode($address, true)])['address_info'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: updateAddress'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function deleteAddress($contactId, $addressId) {
    try {
      return $this->callZoho(self::CONTACTS_API . '/' . $contactId . '/address/' . $addressId, self::DELETE, []);
    } catch (ZohoItemNotFoundException $e) {
      // Address does not exist so do nothing
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: deleteAddress'), ['exception' => $e]);
      throw $e;
    }
  }

  /**
  * @inheritdoc
  */
  public function addCreditNote($invoiceId, $creditNote) {
    try {
      return $creditNote = $this->callZoho(self::CREDIT_NOTES_API, self::POST, [
        'invoice_id' => $invoiceId,
        'JSONString' => json_encode($creditNote, true)])['creditnote'];
    } catch (\Exception $e) {
      $this->_logger->error(__('Zoho API Error: addCreditNote'), ['exception' => $e]);
      throw $e;
    }
  }
}
