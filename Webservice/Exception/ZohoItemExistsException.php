<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Webservice\Exception;

class ZohoItemExistsException extends \Exception {

  public function __construct($message, $code = 0, \Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }
}