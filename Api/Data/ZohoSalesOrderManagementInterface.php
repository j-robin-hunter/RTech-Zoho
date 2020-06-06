<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api\Data;

interface ZohoSalesOrderManagementInterface {
  const ORDER_ID = 'order_id';
  const ZOHO_ID = 'zoho_id';
  const ESTIMATE_ID = 'estimate_id';
  const SALES_ORDER_ID = 'sales_order_id';
  const INVOICE_ID = 'invoice_id';
  const CREDIT_NOTE_ID = 'credit_note_id';

  /**
  * Get order id
  *
  * @return int|null
  */
  public function getId();

  /**
  * Set order id
  *
  * @param int $orderd
  * @return $this
  */
  public function setId($orderId);

  /**
  * Get Zoho id
  *
  * @return string|null
  */
  public function getZohoId();

  /**
  * Set Zoho id
  *
  * @param string $zohoId
  * @return $this
  */
  public function setZohoId($zohoId);

  /**
  * Get Zoho estimate id
  *
  * @return string|null
  */
  public function getEstimateId();

  /**
  * Set Zoho estimate id
  *
  * @param string $estimateId
  * @return $this
  */
  public function setEstimateId($estimateId);

  /**
  * Get Zoho sales order id
  *
  * @return string|null
  */
  public function getSalesOrderId();

  /**
  * Set Zoho sales order id
  *
  * @param string $salesOrderId
  * @return $this
  */
  public function setSalesOrderId($salesOrderId);

  /**
  * Get Zoho invoice id
  *
  * @return string|null
  */
  public function getInvoiceId();

  /**
  * Set Zoho invoice id
  *
  * @param string $invoiceId
  * @return $this
  */
  public function setInvoiceId($invoioceId);

  /**
  * Get Zoho credit note id
  *
  * @return string|null
  */
  public function getCreditNoteId();

  /**
  * Set Zoho credit note id
  *
  * @param string $creditNoteId
  * @return $this
  */
  public function setCreditNoteId($creditNoteId);
}