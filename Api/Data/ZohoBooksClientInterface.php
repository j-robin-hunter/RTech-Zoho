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
  * @param int $contactId
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
  * @param array $contactId
  * @return array
  */
  public function deleteContact($contactId);

  /**
  * Mark a Zoho Books contact as inactive
  *
  * @param int $contactId
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
}