<?php
/**
 * Copyright © 2018 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Webservice\Exception;

class ZohoCommunicationException extends \Exception
{
    const SETUP_EXCEPTION_MESSAGE   = 'API connection could not be prepared, please check your configuration.';
    const RUNTIME_EXCEPTION_MESSAGE = 'API connection could not be established.';

    /**
     * @param string $message
     *
     * @return static
     */
    public static function setup($message)
    {
        $message = sprintf('%s %s', self::SETUP_EXCEPTION_MESSAGE, $message);
        return new static($message);
    }

    /**
     * @param string $message
     *
     * @return static
     */
    public static function runtime($message)
    {
        $message = sprintf('%s %s', self::RUNTIME_EXCEPTION_MESSAGE, $message);
        return new static($message);
    }
}