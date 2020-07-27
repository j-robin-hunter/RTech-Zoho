<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoBooksClientInterface {

  /**
  * Lookup a contact in Zoho Books
  *
  * @param string $displayName
  * @return array
  */
  public function lookupContact($displayName, $email);

  /**
  * Add a contact to Zoho Books
  *
  * @param array $contact
  * @return array
  */
  public function addContact($contact);

  /**
  * Get a contact from Zoho Books
  *
  * @param string $contactId
  * @return array
  */
  public function getContact($contactId);

  /**
  * Update a contact to Zoho Books
  *
  * @param array $contact
  * @return array
  */
  public function updateContact($contact);

  /**
  * Delete a contact from Zoho Books
  *
  * @param string $contactId
  * @return array
  */
  public function deleteContact($contactId);

  /**
  * Mark a Zoho Books contact as inactive
  *
  * @param string $contactId
  */
  public function contactSetInactive($contactId);

  /**
  * Add an estimate to Zoho Books
  *
  * @param array $estimate
  * @return array
  */
  public function addEstimate($estimate);

  /**
  * Update a Zoho Books estimate
  *
  * @param string $estimateId
  * @param array $estimate
  * @return array
  */
  public function updateEstimate($estimateId, $estimate);

  /**
  * Mark an estimate in Zoho Books as sent
  *
  * @param string $estimateId
  * @param string $to
  */
  public function emailEstimate($estimateId, $to);

  /**
  * Mark an estimate in Zoho Books as sent
  *
  * @param string $estimateId
  */
  public function markEstimateSent($estimateId);

  /**
  * Mark an estimate in Zoho Books as accepted
  *
  * @param string $estimateId
  */
  public function markEstimateAccepted($estimateId);

  /**
  * Delete an estimate in Zoho Books
  *
  * @param string $estimateId
  */
  public function deleteEstimate($estimateId);

  /**
  * Add a sales order to Zoho Books
  *
  * @param array $salesOrder
  * @return array
  */
  public function addSalesOrder($salesOrder);

  /**
  * Mark an estimate in Zoho Books as open
  *
  * @param string $salesOrderId
  */
  public function markSalesOrderOpen($salesOrderId);
  /**
  * Convert a sales order to an invoice
  *
  * @param array $invoice
  * @return array
  */
  public function convertSalesOrderToInvoice($salesOrderId);

  /**
  * Delete a sales order in Zoho Books
  *
  * @param string $salesOrderId
  */
  public function deleteSalesOrder($salesOrderId);

  /**
  * Add an invoice to Zoho Books
  *
  * @param string $invoiceId
  * @param array $invoice
  * @return array
  */
  public function updateInvoice($invoiceId, $invoice);

  /**
  * Mark an invoice in Zoho Books as sent
  *
  * @param string $invoiceId
  */
  public function markInvoiceSent($invoiceId);

  /**
  * Delete an invoice in Zoho Books
  *
  * @param string $invoiceId
  */
  public function deleteInvoice($invoiceId);

  /**
  * Get an address from Zoho Books
  *
  * @param string $addressId
  * @return array
  */
  public function getAddress($addressId);

  /**
  * Add an address to Zoho Books
  *
  * @param string $contactId
  * @param array $address
  * @return array
  */
  public function addAddress($contactId, $address);

  /**
  * Update an address to Zoho Books
  *
  * @param string $contactId
  * @param string $addressId
  * @param array $address
  * @return array
  */
  public function updateAddress($contactId, $addressId, $address);

  /**
  * Delete an address from Zoho Books
  *
  * @param string $contactId
  * @param string $addressId
  */
  public function deleteAddress($contactId, $address);

  /**
  * Add a credit note to Zoho Books based on an invoice
  *
  * @param string $invoiceId
  * @param array $creditNote
  * @return array
  */
  public function addCreditNote($invoiceId, $creditNote);
}