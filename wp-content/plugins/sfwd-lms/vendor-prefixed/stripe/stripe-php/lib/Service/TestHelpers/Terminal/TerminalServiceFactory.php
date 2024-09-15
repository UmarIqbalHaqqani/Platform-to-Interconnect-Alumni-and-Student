<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\TestHelpers\Terminal;

/**
 * Service factory class for API resources in the Terminal namespace.
 *
 * @property ReaderService $readers
 *
 * @license MIT
 * Modified by learndash on 27-May-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
class TerminalServiceFactory extends \StellarWP\Learndash\Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'readers' => ReaderService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
