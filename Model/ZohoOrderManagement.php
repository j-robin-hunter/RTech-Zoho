<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoOrderManagementInterface;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use Magento\Framework\Exception\NoSuchEntityException;

class ZohoOrderManagement implements ZohoOrderManagementInterface {

  protected $_zohoClient;
  protected $_zohoOrderContact;
  protected $_zohoInventoryRepository;
  protected $_zohoShippingSkuId;
  protected $_quoteValidity;
  protected $_stockRegistry;
  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoSalesOrderManagementFactory;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoOrderContact $zohoOrderContact,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \Magento\Catalog\Model\ProductRepository $productRepository,
    \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoSalesOrderManagementFactory $zohoSalesOrderManagementFactory
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_zohoOrderContact = $zohoOrderContact;

    $storeId = $storeManager->getStore()->getId();
    $shippingSku = $configData->getZohoShippingSku($storeId);
    $shippingProduct = $productRepository->get($shippingSku);
    $this->_zohoShippingSkuId = $zohoInventoryRepository->getById($shippingProduct->getId())->getZohoId();
    $this->_quoteValidity = $configData->getZohoQuoteValidity($storeId);
    $this->_stockRegistry = $stockRegistry;
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
  }

  /**
  * @inheritdoc
  */
  public function createEstimate($order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('createEstimate');
    $contact = $this->_zohoOrderContact->getContactForOrder($order);
    $contact = $this->_zohoOrderContact->updateOrderContact($contact, $order);

    $estimate = [
      'customer_id' => $contact['contact_id'],
      'reference_number' => sprintf('web-%06d', $order->getIncrementId()),
      'expiry_date' => date('Y-m-d', strtotime($order->getCreatedAt() . ' + ' . $this->_quoteValidity . 'days')),
      'is_inclusive_tax' => false
    ];
    $this->_logger->info($estimate);

    if ($order->getStatusHistories()) {
      $histories = $order->getStatusHistories();
      $estimate['notes'] = array_pop($histories)->getComment();
    }

    $estimate['line_items'] = $this->createLineitems($contact, $order);

    $zohoEstimate = $this->_zohoClient->addEstimate($estimate);
    $this->_zohoClient->markEstimateSent($zohoEstimate['estimate_id']);

    $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementFactory->create();
    $zohoSalesOrderManagement->setData([
      'order_id' => $order->getId(),
      'zoho_id' => $contact['contact_id'],
      'estimate_id' => $zohoEstimate['estimate_id']
    ]);
    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function acceptEstimate($zohoSalesOrderManagement) {
    $estimateId = $zohoSalesOrderManagement->getEstimateId();
    $this->_zohoClient->markEstimateAccepted($estimateId);
  }

  /**
  * @inheritdoc
  */
  public function createSalesOrder($zohoSalesOrderManagement, $order) {
    $contact = $this->_zohoOrderContact->getContactForOrder($order);

    $salesOrder = [
      'customer_id' => $zohoSalesOrderManagement->getZohoId(),
      'reference_number' => sprintf('web-%06d', $order->getIncrementId()),
      'is_inclusive_tax' => false
    ];
    if ($order->getStatusHistories()) {
      $histories = $order->getStatusHistories();
      $salesOrder['notes'] = array_pop($histories)->getComment();
    }
    $salesOrder['line_items'] = $this->createLineitems($contact, $order);

    $zohoSalesOrder = $this->_zohoClient->addSalesOrder($salesOrder);

    $zohoSalesOrderManagement->setSalesOrderId($zohoSalesOrder['salesorder_id']);

    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function openSalesOrder($zohoSalesOrderManagement) {
    $salesOrderId = $zohoSalesOrderManagement->getSalesOrderId();
    $this->_zohoClient->markSalesOrderOpen($salesOrderId);
  }

  /**
  * @inheritdoc
  */
  public function createInvoice($zohoSalesOrderManagement, $order) {
    // Create invoice from sales order
    $zohoInvoice = $this->_zohoClient->convertSalesOrderToInvoice($zohoSalesOrderManagement->getSalesOrderId());

    // Update invoice to set reference number and notes
    $comments = '';
    foreach($order->getInvoiceCollection() as $invoice) {
      foreach ($invoice->getComments() as $comment) {
        $comments = strlen($comments) > 0 ? '\r\n\r\n' . $comment->getComment() : $comment->getComment();
      }
    }

    $invoice = [
      'customer_id' => $zohoSalesOrderManagement->getZohoId(),
      'reference_number' => sprintf('web-%06d', $order->getIncrementId()),
      'notes' => $comments
    ];

    $zohoInvoice = $this->_zohoClient->updateInvoice($zohoInvoice['invoice_id'], $invoice);
    $this->_zohoClient->markInvoiceSent($zohoInvoice['invoice_id']);

    $zohoSalesOrderManagement->setInvoiceId($zohoInvoice['invoice_id']);
    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function deleteAll($zohoSalesOrderManagement) {
    $this->_zohoClient->deleteInvoice($zohoSalesOrderManagement->getInvoiceId());
    $this->_zohoClient->deleteSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
    $this->_zohoClient->deleteEstimate($zohoSalesOrderManagement->getEstimateId());
  }

  private function createLineitems($contact, $order) {
    $taxes = $this->_zohoClient->getTaxes();
    $zeroRate = $taxes[array_search(0, array_column($taxes, 'tax_percentage'))]['tax_id'];
    $euVat = $contact['vat_treatment'] == 'eu_vat_registered' ? true : false;
    $lineItems = array();
    foreach ($order->getAllItems() as $item) {
      $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());
      $lineitem = [
        'item_id' => $zohoInventory->getZohoId(),
        'quantity' => $item->getQtyOrdered(),
        'rate' => $item->getPrice(),
        'discount' => sprintf('%01.2f%%', $item->getDiscountPercent())
      ];
      if ($euVat == true) {
        $lineitem['tax_id'] = $zeroRate;
      }
      $lineitems[] = $lineitem;
    }
    $shipping = [
      'item_id' => $this->_zohoShippingSkuId,
      'quantity' => 1,
      'rate' => $order->getBaseShippingAmount()
    ];
    if ($euVat == true) {
      $shipping['tax_id'] = $zeroRate;
    }
    $lineitems[] = $shipping;
    return $lineitems;
  }
}
