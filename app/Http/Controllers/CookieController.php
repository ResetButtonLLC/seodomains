<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class CookieController extends Controller
{
    public function show()
    {
        return view('cookie');
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'cookie' => 'required',
        ]);

        Storage::put('cookies/'.$request->input('name') . '.txt', trim($request->input('cookie')));

        return redirect()->back()->with('success','Cookies успешно добавлены!');
    }
}
