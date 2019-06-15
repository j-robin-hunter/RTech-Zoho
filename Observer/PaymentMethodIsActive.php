<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActive implements ObserverInterface {

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('PaymentMethodIsActive');
  }
}