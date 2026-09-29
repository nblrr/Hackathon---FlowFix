<?php
namespace App\Exceptions;

class ApiException extends \RuntimeException
{
    public function __construct(public string $errorCode, string $message, public int $status = 422) { parent::__construct($message); }
}
