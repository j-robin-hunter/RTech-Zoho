<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoOrderManagementInterface;
use RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use Magento\Framework\Exception\NoSuchEntityException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use Magento\Framework\Exception\LocalizedException;

class ZohoOrderManagement implements ZohoOrderManagementInterface {

  protected $_zohoBooksClient;
  protected $_zohoInventoryClient;
  protected $_zohoOrderContact;
  protected $_zohoInventoryRepository;
  protected $_zohoShippingSkuId;
  protected $_estimateValidity;
  protected $_estimateTerms;
  protected $_invoiceTerms;
  protected $_stockRegistry;
  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoSalesOrderManagementFactory;
  protected $_customerSession;
  protected $_priceHelper;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoOrderContact $zohoOrderContact,
    \RTech\Zoho\Model\ZohoInventoryRepository $zohoInventoryRepository,
    \Magento\Catalog\Model\ProductRepository $productRepository,
    \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoSalesOrderManagementFactory $zohoSalesOrderManagementFactory,
    \Magento\Customer\Model\Session $customerSession
  ) {
    $this->_zohoBooksClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_zohoOrderContact = $zohoOrderContact;

    $storeId = $storeManager->getStore()->getId();
    $shippingSku = $configData->getZohoShippingSku($storeId);
    $shippingProduct = $productRepository->get($shippingSku);
    $this->_zohoShippingSkuId = $zohoInventoryRepository->getById($shippingProduct->getId())->getZohoId();
    $this->_estimateValidity = $configData->getZohoEstimateValidity($storeId);
    $this->_estimateTerms = $configData->getZohoEstimateTerms($storeId);
    $this->_invoiceTerms = $configData->getZohoInvoiceTerms($storeId);
    $this->_stockRegistry = $stockRegistry;
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
    $this->_customerSession = $customerSession;
  }

  /**
  * @inheritdoc
  */
  public function quoteEstimate($contactId, $quote, $shippingAmount) {
    $estimate = [
      'customer_id' => $contactId,
      'reference_number' => sprintf('wqt-%06d', $quote->getId()),
      'expiry_date' => date('Y-m-d', strtotime($quote->getUpdatedAt() . ' + ' . $this->_estimateValidity . 'days')),
      'is_inclusive_tax' => false,
      'terms' => $this->_estimateTerms
    ];
    $estimate['line_items'] = $this->createLineitems($quote, $shippingAmount);
    $zohoEstimate = $this->_zohoBooksClient->addEstimate($estimate);
    $this->_zohoBooksClient->markEstimateSent($zohoEstimate['estimate_id']);

    return $zohoEstimate;
  }

  /**
  * @inheritdoc
  */
  public function orderEstimate($order) {
    $contact = $this->_zohoOrderContact->getContactForOrder($order);
    $contact = $this->_zohoOrderContact->updateOrderContact($contact, $order);

    $estimate = [
      'customer_id' => $contact['contact_id'],
      'reference_number' => sprintf('web-%06d', $order->getIncrementId()),
      'expiry_date' => date('Y-m-d', strtotime($order->getCreatedAt() . ' + ' . $this->_estimateValidity . 'days')),
      'is_inclusive_tax' => false,
      'terms' => $this->_estimateTerms
    ];

    if ($order->getStatusHistories()) {
      $histories = $order->getStatusHistories();
      $estimate['notes'] = array_pop($histories)->getComment();
    }

    $estimate['line_items'] = $this->createLineitems($order, $order->getBaseShippingAmount());

    $zohoEstimate = $this->_zohoBooksClient->addEstimate($estimate);
    $this->_zohoBooksClient->markEstimateSent($zohoEstimate['estimate_id']);

    $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementFactory->create();
    $zohoSalesOrderManagement->setData([
      ZohoSalesOrderManagementInterface::ORDER_ID => $order->getId(),
      ZohoSalesOrderManagementInterface::ZOHO_ID => $contact['contact_id'],
      ZohoSalesOrderManagementInterface::ESTIMATE_ID => $zohoEstimate['estimate_id']
    ]);

    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function updateEstimate($estimateId, $contactId, $source, $shippingAmount) {
    $estimate = [
      'customer_id' => $contactId,
      'terms' => $this->_estimateTerms
    ];
    $estimate['line_items'] = $this->createLineitems($source, $shippingAmount);
    $zohoEstimate = $this->_zohoBooksClient->updateEstimate($estimateId, $estimate);

    return $zohoEstimate;
  }

  /**
  * @inheritdoc
  */
  public function acceptEstimate($zohoSalesOrderManagement) {
    $estimateId = $zohoSalesOrderManagement->getEstimateId();
    $this->_zohoBooksClient->markEstimateAccepted($estimateId);
  }

  /**
  * @inheritdoc
  */
  public function createSalesOrder($zohoSalesOrderManagement, $order, $ref) {
    $salesOrder = [
      'customer_id' => $zohoSalesOrderManagement->getZohoId(),
      'reference_number' => $ref ? : sprintf('web-%06d', $order->getIncrementId()),
      'is_inclusive_tax' => false,
      'terms' => $this->_invoiceTerms
    ];

    if ($order->getStatusHistories()) {
      $histories = $order->getStatusHistories();
      $salesOrder['notes'] = array_pop($histories)->getComment();
    }
    $salesOrder['line_items'] = $this->createLineitems($order, $order->getBaseShippingAmount());

    $zohoSalesOrder = $this->_zohoBooksClient->addSalesOrder($salesOrder);

    $zohoSalesOrderManagement->setSalesOrderId($zohoSalesOrder['salesorder_id']);
    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function openSalesOrder($zohoSalesOrderManagement) {
    $salesOrderId = $zohoSalesOrderManagement->getSalesOrderId();
    $this->_zohoBooksClient->markSalesOrderOpen($salesOrderId);
  }

  /**
  * @inheritdoc
  */
  public function createInvoice($zohoSalesOrderManagement, $order) {
    // Get payment terms
    $contact = $this->_zohoBooksClient->getContact($zohoSalesOrderManagement->getZohoId());

    // Create invoice from sales order
    $zohoInvoice = $this->_zohoBooksClient->convertSalesOrderToInvoice($zohoSalesOrderManagement->getSalesOrderId());

    // Update invoice to set reference number and notes
    $comments = '';
    foreach($order->getInvoiceCollection() as $invoice) {
      if ($invoice->getComments()) {
        foreach ($invoice->getComments() as $comment) {
          $comments = strlen($comments) > 0 ? '\r\n\r\n' . $comment->getComment() : $comment->getComment();
        }
      }
    }

    $invoice = [
      'customer_id' => $zohoSalesOrderManagement->getZohoId(),
      'reference_number' => sprintf('web-%06d', $order->getIncrementId()),
      'notes' => $comments,
      'payment_terms' => $contact['payment_terms'] ?? 0,
      'terms' => $this->_invoiceTerms
    ];

    $zohoInvoice = $this->_zohoBooksClient->updateInvoice($zohoInvoice['invoice_id'], $invoice);
    $this->_zohoBooksClient->markInvoiceSent($zohoInvoice['invoice_id']);

    $zohoSalesOrderManagement->setInvoiceId($zohoInvoice['invoice_id']);
    return $this->_zohoSalesOrderManagementRepository->save($zohoSalesOrderManagement);
  }

  /**
  * @inheritdoc
  */
  public function deleteAll($zohoSalesOrderManagement) {
    try {
      $this->_zohoBooksClient->deleteInvoice($zohoSalesOrderManagement->getInvoiceId());
    } catch (ZohoItemNotFoundException $e) {
      // ignore as this will only occur if there is no Zoho invoice
    } catch (\Exception $e) {
      throw $e;
    }
    try {
      // Get the sales order ....
      // If is has any sales returns delete and receivables from each as well as the sales return and then ....
      // If it has any package delete any shipments from each as well as the package and then ....
      // Delete the sales order
      $zohoSalesOrder = $this->_zohoInventoryClient->getSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
      foreach ($zohoSalesOrder['salesreturns'] ?? [] as $returns) {
        $zohoReturns = $this->_zohoInventoryClient->getSalesReturn($returns['salesreturn_id']);
        foreach ($zohoReturns['salesreturnreceives'] ?? [] as $returnable) {
          $this->_zohoInventoryClient->salesReturnReceivableDelete($returnable['receive_id']);
        }
        $this->_zohoInventoryClient->salesReturnDelete($returns['salesreturn_id']);
      }
      foreach ($zohoSalesOrder['packages'] ?? [] as $package) {
        $this->_zohoInventoryClient->shipmentDelete($package['shipment_id']);
        $this->_zohoInventoryClient->packageDelete($package['package_id']);
      }
      $this->_zohoBooksClient->deleteSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
    } catch (ZohoItemNotFoundException $e) {
      // ignore as this will only occur if there is no Zoho sales order
    }
    try {
      $this->_zohoBooksClient->deleteEstimate($zohoSalesOrderManagement->getEstimateId());
    } catch (ZohoItemNotFoundException $e) {
      // ignore as this will only occur if there is no Zoho estimate
    }
  }

  /**
  * @inheritdoc
  */
  public function isRefundAllowed($zohoSalesOrderManagement, $creditmemo) {
    $zohoOrder = $this->_zohoInventoryClient->getSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
    foreach ($creditmemo->getAllItems() as $item) {
      $orderItem = $item->getOrderItem();
      $productId = $orderItem->getProductId();
      $zohoItemId  = $this->_zohoInventoryRepository->getById($productId)->getZohoId();
      $zohoOrderLine = array_search($zohoItemId, array_column($zohoOrder['line_items'], 'item_id'));
      if ($zohoOrderLine !== false) {
        $zohoOrderItem = $zohoOrder['line_items'][$zohoOrderLine];
        $quantityShipped = $zohoOrderItem['quantity_shipped'] ?? 0;
        $quantityReturned = $zohoOrderItem['quantity_returned'] ?? 0;
        if ($quantityShipped > 0 && ($item->getQty() - $quantityReturned) != 0) {
          throw new LocalizedException(
            __('Unable to create credit memo as there is no matching Zoho sales return for %1', 
            sprintf(':  qty %d - %s', $item->getQty(), $zohoOrder['line_items'][$zohoOrderLine]['name'])));
        }
      }
    }
    foreach ($zohoOrder['salesreturns'] ?? [] as $salesReturn) {
      $zohoSalesReturn = $this->_zohoInventoryClient->getSalesReturn($salesReturn['salesreturn_id']);
      foreach ($zohoSalesReturn['line_items'] as $lineItem) {
        if ($lineItem['quantity'] != $lineItem['quantity_received']) {
          throw new LocalizedException(__('Unable to create credit memo as not all Zoho sales return items have been returned to stock'));
        }
      }
    }
    return $zohoOrder;
  }

  /**
  * @inheritdoc
  */
  public function createCreditNote($zohoOrder, $creditmemo) {
    // Update credit note to set notes
    $comments = '';
    foreach ($creditmemo->getComments() as $comment) {
      $comments = strlen($comments) > 0 ? '\r\n\r\n' . $comment->getComment() : $comment->getComment();
    }

    $creditNote = [
      'customer_id' => $zohoOrder['customer_id'],
      'date' => date('Y-m-d'),
      'notes' => $comments,
      'reference_number' => $zohoOrder['salesorder_number'] ?? sprintf('web-%06d', $creditmemo->getOrder()->getIncrementId()),
      'is_inclusive_tax' => false,
      'line_items' => []
    ];
    foreach ($creditmemo->getAllItems() as $item) {
      $orderItem = $item->getOrderItem();
      $productId = $orderItem->getProductId();
      $zohoItemId  = $this->_zohoInventoryRepository->getById($productId)->getZohoId();
      $zohoOrderLine = array_search($zohoItemId, array_column($zohoOrder['line_items'], 'item_id'));
      if ($zohoOrderLine !== false) {
        $zohoOrderItem = $zohoOrder['line_items'][$zohoOrderLine];
        if ($item->getQty() > ($zohoOrderItem['quantity_shipped'] - $zohoOrderItem['quantity_returned'])) {
          throw new LocalizedException(__('Unable to create credit memo as not all creditable items have been returned in Zoho Inventory'));
        }
        $creditNote['line_items'][] = [
          'item_id' => $zohoItemId,
          'name' => $zohoOrderItem['name'] ?? '',
          'quantity' => $item->getQty(),
          'rate' => $item->getPrice(),
          'discount' => $zohoOrderItem['discount'] ?? '',
          'tax_id' => $zohoOrderItem['tax_id'] ?? ''
        ];
      }
    }

    $zohoShippingLine = array_search($this->_zohoShippingSkuId, array_column($zohoOrder['line_items'], 'item_id'));
    if ($zohoShippingLine !== false && $creditmemo->getBaseShippingAmount() > 0) {
      $zohoOrderShipping = $zohoOrder['line_items'][$zohoShippingLine];
      $creditNote['line_items'][] = [
        'item_id' => $this->_zohoShippingSkuId,
        'name' => $zohoInvoiceShipping['name'] ?? '',
        'quantity' => $zohoInvoiceShipping['quantity'] ?? 1,
        'rate' => $creditmemo->getBaseShippingAmount(),
        'tax_id' => $zohoOrderShipping['tax_id'] ?? ''
      ];
    }
    
    if ($creditmemo->getAdjustmentPositive() != 0) {
      $creditNote['line_items'][] = [
        'description' => __('Refund adjustment'),
        'rate' => $creditmemo->getAdjustmentPositive(),
      ];
    }

    if ($creditmemo->getAdjustmentNegative() != 0) {
      $creditNote['line_items'][] = [
        'description' => __('Refund fee'),
        'rate' => -$creditmemo->getAdjustmentNegative(),
      ];
    }

    $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($creditmemo->getOrderId());
    $zohoCreditNote = $this->_zohoBooksClient->addCreditNote($zohoSalesOrderManagement->getInvoiceId(), $creditNote);
  }

  private function createLineitems($items, $shippingAmount) {
    $taxes = $this->_zohoBooksClient->getTaxes();
    $zeroRate = $taxes[array_search(0, array_column($taxes, 'tax_percentage'))]['tax_id'];
    $euVat = $this->_zohoOrderContact->getVatTreatment($items) == 'eu_vat_registered' ? true : false;

    $lineItems = array();
    foreach ($items->getAllItems() as $item) {
      if ($item->getProductType() != 'configurable' && $item->getProductType() != 'bundle') {
        $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());

        // If this is a child we now need the parent to get the price if this
        // is a configurable product. If the parent is a bundle product then 
        // we use the price of the item.
        $parentItem = $item->getParentItem();
        if ($parentItem && $parentItem == 'configurable') {
          $item = $parentItem;
        }
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
    }
    $shipping = [
      'item_id' => $this->_zohoShippingSkuId,
      'quantity' => 1,
      'rate' => $shippingAmount
    ];
    if ($euVat == true) {
      $shipping['tax_id'] = $zeroRate;
    }
    $lineitems[] = $shipping;
    return $lineitems;
  }
}
