<?php

namespace App\Http\Controllers;

use App\Models\Cookie;

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

        $cookie = Cookie::updateOrCreate(
            ['name' => $request->input('name')],
            ['cookie' => $request->input('cookie')]
        );

        return redirect()->back()->with('success','Cookies успешно добавлены!');
    }
}
