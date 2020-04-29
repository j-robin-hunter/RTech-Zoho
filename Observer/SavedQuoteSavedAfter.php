<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class SavedQuoteSavedAfter implements ObserverInterface {

  protected $_zohoCustomerRepository;
  protected $_savedQuoteRepository;
  protected $_zohoOrderManagement;
  protected $_logger;

  public function __construct(
    \RTech\Quote\Model\QuoteRepository $savedQuoteRepository,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_savedQuoteRepository = $savedQuoteRepository;
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoOrderManagement = $zohoOrderManagement;
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $quote = $observer->getQuote();
    try {
      $savedQuote = $this->_savedQuoteRepository->getById($quote->getId());
      $zohoCustomer = $this->_zohoCustomerRepository->getById($quote->getCustomerId());

      if (empty($savedQuote->getEstimateId())) {
        $estimate = $this->_zohoOrderManagement->quoteEstimate($zohoCustomer->getZohoId(), $quote, $savedQuote->getShippingAmount());
        $savedQuote->setEstimateId($estimate['estimate_id']);
        $this->_savedQuoteRepository->save($savedQuote);
      } else {
        $estimate = $this->_zohoOrderManagement->updateEstimate($savedQuote->getEstimateId(), $zohoCustomer->getZohoId(), $quote, $savedQuote->getShippingAmount());
      }
    } catch (\Exception $e) {
      // log the error but do not cascade as this will impact the customer
      // experience. The quote will not be sent ot Zoho but this can be managed
      // if the customer queries the quote
      $this->_logger->error(__('Error in SavedQuoteSavedAfter %1'),  ['exception' => $e]);
      throw $e;
    }
  }
}