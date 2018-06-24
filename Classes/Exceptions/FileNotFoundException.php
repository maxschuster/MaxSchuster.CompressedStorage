<?php

namespace MaxSchuster\CompressedStorage\Exceptions;

/*
 * This file is part of the MaxSchuster.CompressedResource package.
 */

use Neos\Flow\Exception;

/**
 * Exception that is thrown if a resource was not found.
 */
class FileNotFoundException extends Exception
{

    protected $statusCode = 404;

}