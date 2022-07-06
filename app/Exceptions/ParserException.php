<?php

namespace App\Exceptions;

use Exception;

class ParserException extends Exception
{
    public function report()
    {
        dd($this);
    }
}
