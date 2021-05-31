<?php

namespace App\Http\Controllers;

use Storage;

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

        Storage::putFileAs('cookies', $request->file('cookie'), $request->input('name') . '.txt');

        return redirect()->back()->with('success','Cookies успешно добавлены!');
    }
}
