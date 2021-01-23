<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     *
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function render($request)
    {
        response()->json(["success" => false, "error_code" => $this->getCode(), "message" => $this->getMessage()],$this->getCode(),['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],JSON_UNESCAPED_UNICODE)->send();
        exit();
    }


}