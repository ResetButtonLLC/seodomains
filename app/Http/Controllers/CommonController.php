<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CommonController extends Controller {

    public function main() {
        if (Auth::user()) {
            return redirect()->route('domains');
        } else {
            return view('layouts.login');
        }
    }

}
