<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Webservice\Exception;

class ZohoOperationException extends \Exception {

  public static function create($responseBody) {
    $response = json_decode($responseBody, true);
    if ($response && isset($response['message'])) {
      $message = $response['message'];
    } else {
      $message = sprintf('API operation failed. Response: "%s"', $responseBody);
    }

    return new static($message);
  }
}