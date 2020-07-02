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
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use Magento\Framework\Exception\LocalizedException;

class ZohoOrderManagement implements ZohoOrderManagementInterface {

  protected $_zohoBooksClient;
  protected $_zohoInventoryClient;
  protected $_zohoOrderContact;
  protected $_zohoInventoryRepository;
  protected $_estimateValidity;
  protected $_estimateTerms;
  protected $_invoiceTerms;
  protected $_stockRegistry;
  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoSalesOrderManagementFactory;
  protected $_customerSession;
  protected $_scopeConfig;
  protected $_taxCalculation;
  protected $_groupRepository;
  protected $_coupon;
  protected $_ruleRepository;
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
    \Magento\Customer\Model\Session $customerSession,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Tax\Model\Calculation $taxCalculation,
    \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
    \Magento\SalesRule\Model\Coupon $coupon,
    \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository
  ) {
    $this->_zohoBooksClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_zohoInventoryRepository = $zohoInventoryRepository;
    $this->_zohoOrderContact = $zohoOrderContact;

    $storeId = $storeManager->getStore()->getId();
    $shippingSku = $configData->getZohoShippingSku($storeId);
    $shippingProduct = $productRepository->get($shippingSku);
    $this->_estimateValidity = $configData->getZohoEstimateValidity($storeId);
    $this->_estimateTerms = $configData->getZohoEstimateTerms($storeId);
    $this->_invoiceTerms = $configData->getZohoInvoiceTerms($storeId);
    $this->_stockRegistry = $stockRegistry;
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
    $this->_customerSession = $customerSession;
    $this->_scopeConfig = $scopeConfig;
    $this->_taxCalculation = $taxCalculation;
    $this->_groupRepository = $groupRepository;
    $this->_coupon = $coupon;
    $this->_ruleRepository = $ruleRepository;
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
    array_merge($estimate, $this->createDetails($quote, $shippingAmount));
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
      'terms' => $this->_estimateTerms,
      'notes' => $this->getOrderNotes($order)
    ];

    $estimate = array_merge($estimate, $this->createDetails($order, $order->getBaseShippingAmount()));

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

    $estimate = array_merge($estimate, $this->createDetails($source, $order->getBaseShippingAmount()));

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
      'terms' => $this->_invoiceTerms,
      'notes' => $this->getOrderNotes($order)
    ];

    $salesOrder = array_merge($salesOrder, $this->createDetails($order, $order->getBaseShippingAmount()));

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
          $comments = strlen($comments) > 0 ? "\n\n" . $comment->getComment() : $comment->getComment();
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
  public function updateStock($shipment) {
    $order = $shipment->getOrder();
    foreach ($order->getAllItems() as $item) {
      if ($item->getProductType() == 'simple') {
        $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());
        $zohoItem = $this->_zohoInventoryClient->getItem($zohoInventory->getZohoId());
        $sku = $item->getSku();
        $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
        if ($stockItem->getManageStock()) {
          // Set the Magento stock to the Zoho available for sale stock plus the number
          // of items ordered in Magento as the Magento items will have been set as committed stock
          // THIS IS NOT PERFECT
          $stockItem->setQty($item->getQtyOrdered() + $zohoItem['available_for_sale_stock']);
          $stockItem->setIsInStock(true);
          $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
        }
      }
    }
  }

  /**
  * @inheritdoc
  */
  public function createShipment($shipment) {
    $order = $shipment->getOrder();
    $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($order->getId());
    $zohoSalesOrder = $this->_zohoInventoryClient->getSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
    $zohoSalesOrderItems = array_column($zohoSalesOrder['line_items'], 'item_id');

    $lineitems = [];
    foreach ($shipment->getItemsCollection() as $item) {
      $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());
      // Can only ship Zoho inventory items not groups.

      // Use the Zoho sales order for details as this will
      // ensure that the data lines up correctly within Zoho
      // which will reduce errors
      if ($zohoInventory->getZohoType() == 'item') {
        $zohoSolesOrderLine = array_search($zohoInventory->getZohoId(), $zohoSalesOrderItems);
        if ($zohoSolesOrderLine !== false) {
          $zohoSalesOrderItem = $zohoSalesOrder['line_items'][$zohoSolesOrderLine];
          $lineitems[] = [
            'so_line_item_id' => $zohoSalesOrderItem['line_item_id'],
            'quantity' => $zohoSalesOrderItem['quantity']
          ];
        } else {
          throw new ZohoItemNotFoundException(__('The item %1 cannot be located on the Zoho sales order %2', $item->getName(), $zohoSalesOrder['salesorder_number']));
        }
      }
    }

    $package = [
      //Having this number can create auto-increment issues in Zoho:   'package_number' => sprintf('web-%06d', $order->getIncrementId()),
      'date' => date('Y-m-d'),
      'line_items' => $lineitems,
      'notes' => $shipment->getCommentsCollection()->getFirstItem()->getComment() ?: ''
    ];
    try {
      $package = $this->_zohoInventoryClient->packageAdd($zohoSalesOrderManagement->getSalesOrderId(), $package);
    } catch (\Exception $e) {
      throw new ZohoOperationException($e->getMessage());
    }

    $tracking = $shipment->getTracks();
    $shippedPackage = [
      // Having this number can create auto-increment issues in Zoho:   'shipment_number' => sprintf('web-%06d', $order->getIncrementId()),
      'date' => date('Y-m-d'),
      'tracking_number' => empty($tracking) ? '' : $tracking[0]->getTrackNumber(),
      'delivery_method' => empty($tracking) ? '' : $tracking[0]->getTitle(),
      'notes' => $shipment->getCommentsCollection()->getFirstItem()->getComment() ?: ''
    ];
    $this->_zohoInventoryClient->shipmentAdd($package, $shippedPackage);
  }


  /**
  * @inheritdoc
  */
  public function createCreditNote($zohoSalesOrderManagement, $creditmemo) {
    $zohoInvoices = null;
    $zohoSalesReturns = null;

    // Zoho has two mechanisms for creating credit notes
    // 1) against an invoice
    // 2) against a sales return
    // A Magento credit memo could result in either or both depending on whether there is a 
    // Zoho sales return for the credit memo line item

    // Work through each credit memo item and from the Zoho sales order it can be determined if the item
    // requires a Zoho credit note against an invoice or a sales return from the line_item fields
    // that indicates how many items have been invoiced, shipped, cancelled and returned

    // Knowing how many items are to be credited from this credit memo, and how many have already been 
    // credited from any previous credit memos, whether the line item requires a credit memo against the
    // Zoho invoice or sales return can be determined.

    // If there is no sales return for the item that has been shipped then this will also cause an error

    // Get the sales order using the Zoho inventory API as this returns more information than the corresponding call to Zoho Books
    try {
      $zohoSalesOrder = $this->_zohoInventoryClient->getSalesOrder($zohoSalesOrderManagement->getSalesOrderId());
    } catch  (\Exception $e) {
      throw new LocalizedException(__('Unable to create credit memo as there is no Zoho sales order for this Magento order'));
    }

    // Get all Zoho invoices that have been created from the sales order
    $zohoInvoices = [];
    foreach ($zohoSalesOrder['invoices'] ?? [] as $invoice) {
      $zohoInvoices[$invoice['invoice_id']] = $this->_zohoInventoryClient->getInvoice($invoice['invoice_id']);
    }
    $zohoReturns = null;

    $creditableItems = [];
    foreach ($creditmemo->getAllItems() as $item) {
      // Create a credit note line item for the credit memo using the Zoho invoice
      $creditNoteLineItem = $this->getCreditNoteLineItem($item, $zohoInvoices);
      if (!empty($creditNoteLineItem)) {
        // Use the invoice line item to locate the sales order line item as this will
        // provide details relating to quantity invoiced, packed, shipped, cancelled, returned
        $itemId = $creditNoteLineItem['line_item']['item_id'];
        $salesOrderLineItem = array_filter($zohoSalesOrder['line_items'], function($a) use ($itemId) {return $a['item_id'] == $itemId;});
        if (empty($salesOrderLineItem)) {
          throw new LocalizedException(__('There is a line item missing on the Zoho sales order for %1', $item->getName()));
        }

        // If there are invoiced items that have not been cancelled or shipped then the credit memo can be raised against the Zoho invoice
        $key = key($salesOrderLineItem);
        $creditableQty = min(
          $salesOrderLineItem[$key]['quantity_invoiced'] - $salesOrderLineItem[$key]['quantity_cancelled'] - $salesOrderLineItem[$key]['quantity_shipped'],
          $item->getQty());
        $creditNoteLineItem['line_item']['quantity'] = $creditableQty;
        $creditNoteLineItem['line_item']['rate'] = $salesOrderLineItem[$key]['rate'];
        $creditNoteLineItem['line_item']['discount'] = $salesOrderLineItem[$key]['discount_amount'];
        $creditableItems[$creditNoteLineItem['invoice_id']]['invoice']['line_items'][] = $creditNoteLineItem['line_item'];

        // If not all items can be raised against the Zoho invoice determine whether a sales return for the item exists
        // Do not worry if the quantity is wrong as the Zoho credit note create will generate an error if the actual credit
        // note cannot be raised
        if ($creditableQty < $item->getQty()) {
          foreach ($zohoSalesOrder['salesreturns'] as $salesReturn) {
            $salesReturnLineItem = array_filter($salesReturn['line_items'], function($a) use ($itemId) {
              return $a['item_id'] == $itemId;
            });
            if (!empty($salesReturnLineItem)) {
              $key = key($salesOrderLineItem);
              $creditableItems[$creditNoteLineItem['invoice_id']]['return'][] = [
                'salesreturn_id' => $salesReturn['salesreturn_id'],
                'item_id' => $salesReturnLineItem[$key]['item_id'],
                'line_item_id' => $salesReturnLineItem[$key]['line_item_id'],
                'quantity' => $item->getQty() - $creditableQty
              ];
              $creditableQty += $item->getQty();
              break;
            }
          }
          if ($creditableQty < $item->getQty()) {
            throw new LocalizedException(__('Zoho sales returns are missing for %1', $item->getName()));
          }
        }
      }
    }


    // Get any comments to add to the credit notes
    $comments = '';
    foreach ($creditmemo->getComments() as $comment) {
      $comments = strlen($comments) > 0 ? "\n\n" . $comment->getComment() : $comment->getComment();
    }

    // Create common credit note elements
    $creditNote = [
      'customer_id' => $zohoSalesOrder['customer_id'],
      'date' => date('Y-m-d'),
      'notes' => $comments,
      'reference_number' => $zohoSalesOrder['salesorder_number'] ?? sprintf('web-%06d', $creditmemo->getOrder()->getIncrementId()),
      'is_inclusive_tax' => false,
      'discount_type' => 'item_level'
    ];

    try {
      foreach ($creditableItems as $id => $items) {
        // Do returns credit notes first as this may error due to quanties or non-returns and
        // if this does error then there is less to manually tidy up in Zoho Books/Inventory
        // Having an error is not a problem as this will prevent any Magento on-line credit refund
        foreach ($items['return'] ?? [] as $return) {
          $itemId = $return['item_id'];
          $lineItem = array_filter($items['invoice']['line_items'], function($a) use ($itemId) {return $a['item_id'] == $itemId;});
          $lineItem = reset($lineItem); 
          $lineItem['quantity'] = $return['quantity'];
          $lineItem['salesreturn_item_id'] = $return['line_item_id'];
          $lineItem['is_item_shipped'] = true;
          $lineItem['is_returned_to_stock'] = true;
          $creditNote['line_items'][] = $lineItem;
        }
        $zohoReturnCreditNote = $this->_zohoInventoryClient->addCreditNote($return['salesreturn_id'], $creditNote);
        $this->_zohoInventoryClient->applyCredit($zohoReturnCreditNote['creditnote_id'], [
          'invoices' => [
            [
              'invoice_id' => $id,
              'amount_applied' => $zohoReturnCreditNote['total']
            ]
          ]
        ]);

        // Do invoice credit notes
        $creditNote['line_items'] = $items['invoice']['line_items'];

        // If this is the first invoice credit note within the foreach loop
        // then add any shipping and/or other credits and debits
        reset($creditableItems);
        if ($id === key($creditableItems)) {
          $creditNote['shipping_charge'] = $creditmemo->getShippingInclTax();
  
          if ($creditmemo->getAdjustmentPositive() != 0) {
            $creditNote['line_items'][] = [
              'description' => __('Refund adjustment'),
              'rate' => $creditmemo->getAdjustmentPositive(),
              'invoice_id' => strval($id)
            ];
          }
  
          if ($creditmemo->getAdjustmentNegative() != 0) {
            $creditNote['line_items'][] = [
              'description' => __('Refund fee'),
              'rate' => -$creditmemo->getAdjustmentNegative(),
              'invoice_id' => strval($id)
            ];
          }
        }
        // Set the rate and discount for any line items that have a quantity of 0 to 0
        foreach ($creditNote['line_items'] as $index => $lineItem) {
          if (isset($lineItem['quantity']) && $lineItem['quantity'] == 0) {
            $creditNote['line_items'][$index] = [
              'description' => $lineItem['name'],
              'rate' => 0,
              'quantity' => 0,
              'account_id' => $lineItem['account_id']
            ];
          } 
        }
        $zohoInvoiceCreditNote = $this->_zohoBooksClient->addCreditNote($id, $creditNote);
      }

    } catch (\Exception $e) {
      throw new LocalizedException(__('Error during Zoho credit note creation: %1', $e->getMessage()));
    }
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

  // The subject parameter is either:
  // -  a \Magento\DSales\Model\Order
  // -  a \Magento\Quote\Model\Quote
  // both have the same functions to get the cusomer group id and the line items
  private function createDetails($subject, $shippingAmount) {
    $customerTaxId = $this->_groupRepository->getById($subject->getCustomerGroupId())->getTaxClassId();

    $zohoTaxes = $this->_zohoBooksClient->getTaxes();
    //$zeroRate = $taxes[array_search(0, array_column($zohoTaxes, 'tax_percentage'))]['tax_id'];
    //$euVat = $this->_zohoOrderContact->getVatTreatment($items) == 'eu_vat_registered' ? true : false;

    $details = [];
    foreach ($subject->getAllItems() as $item) {
      if ($item->getProductType() != 'configurable' && $item->getProductType() != 'bundle') {
        $zohoInventory = $this->_zohoInventoryRepository->getById($item->getProductId());

        // If this is a child we now need the parent to get the price if this
        // is a configurable product. If the parent is a bundle product then 
        // we use the price of the item and the name of the bundle as the header
        $headerName = '';
        $parentItem = $item->getParentItem();
        if ($parentItem) {
          if ($parentItem->getProductType() == 'configurable') {
            $item = $parentItem;
          } //else if ($parentItem->getProductType() == 'bundle') {
            $headerName = $parentItem->getName();
          //}
        }

        $details['line_items'][] = [
          'item_id' => $zohoInventory->getZohoId(),
          'name' => $item->getName(),
          'quantity' => $item->getQtyOrdered(),
          'rate' => $item->getPrice(),
          'discount' => sprintf('%01.2f', $item->getDiscountAmount()),
          'tax_id' => $this->getZohoTaxId($zohoTaxes, $customerTaxId, $item->getProduct()->getTaxClassId()),
          'header_name' => $headerName,
          'description' => $headerName && $parentItem ? '(' . $parentItem->getName() . ')' : ''
        ];
      }
    }

    $details['shipping_charge'] = $shippingAmount;
    $shippingTaxClass = $this->_scopeConfig->getValue('tax/classes/shipping_tax_class', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    $details['shipping_charge_tax_id'] = $this->getZohoTaxId($zohoTaxes, $customerTaxId, $shippingTaxClass);

    return $details;
  }

  // This function will default the sales tax to either 0% or, if no Zoho tax esists for 0%, 
  // to the default tax percentage defined in Zoho
  private function getZohoTaxId($zohoTaxes, $customerTaxId, $productTaxId) {
    $countryCode = $this->_scopeConfig->getValue(\Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID);
    $taxRate = $this->_taxCalculation->getRate(
      new \Magento\Framework\DataObject(
        [
          'country_id' => $countryCode,
          'customer_class_id' => $customerTaxId,
          'product_class_id' => $productTaxId
        ]
      )
    );
    $index = array_search($taxRate, array_column($zohoTaxes, 'tax_percentage'));
    if ($index === false) {
      return '';
    }
    return $zohoTaxes[$index]['tax_id'];
  }

  private function getOrderNotes($order) {
    $notes = '';
    $couponCode = $order->getCouponCode();
    if (!empty($couponCode)) {
      $rule = $this->_ruleRepository->getById($this->_coupon->loadByCode($couponCode)->getRuleId());
      $notes .= __('Coupons: %1' , sprintf('%s, %s', $couponCode, $rule->getDescription())) . "\n\n";
    }

    if ($order->getStatusHistories()) {
      $histories = $order->getStatusHistories();
      $notes .= array_pop($histories)->getComment();
    }

    return $notes;
  }

  private function getCreditNoteLineItem($item, $zohoInvoices) {
    $orderItem = $item->getOrderItem();
    $zohoItemId  = $this->_zohoInventoryRepository->getById($orderItem->getProductId())->getZohoId();

    foreach ($zohoInvoices as $id => $invoice) {
      $lineItemIndex = array_search($zohoItemId, array_column($invoice['line_items'], 'item_id'));
      if ($lineItemIndex !== false) {
        return [
          'invoice_id' => strval($id),
          'line_item' => [
            'item_id' => $invoice['line_items'][$lineItemIndex]['item_id'],
            'name' => $invoice['line_items'][$lineItemIndex]['name'],
            'description' => $invoice['line_items'][$lineItemIndex]['description'],
            'tax_id' => $invoice['line_items'][$lineItemIndex]['tax_id'],
            'invoice_id' => strval($id),
            'invoice_item_id' => $invoice['line_items'][$lineItemIndex]['line_item_id'],
            'account_id' => $invoice['line_items'][$lineItemIndex]['account_id'],
            'is_returned_to_stock' => true,
            'is_item_shipped' => false
          ]
        ];
      }
    }
    return null;
  }
}
