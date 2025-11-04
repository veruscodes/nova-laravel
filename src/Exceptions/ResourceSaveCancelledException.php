<?php

namespace Laravel\Nova\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ResourceSaveCancelledException extends HttpException
{
    public function __construct(?string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        if (empty($message)) {
            $message = __('The resource was prevented from being saved!');
        }

        parent::__construct(500, $message, $previous, $headers, $code);
    }
}
