<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domains;

class DomainsController extends Controller {

    public function index(Request $request) {
        $domains = Domains::whereNotNull('url');

        if ($request->source) {
            $domains = $domains->where('source', $request->source);
        }

        $domains = $domains->orderBy('url')->paginate(20);
        $data = [];
        foreach ($domains as $domain) {
            $data[$domain->url] = ['lang' => '', 'links' => ''];
            $data[$domain->url]['miralinks'] = Domains::where('url', $domain->url)->where('source', 'miralinks')->first();
            $data[$domain->url]['sape'] = Domains::where('url', $domain->url)->where('source', 'sape')->first();
            $data[$domain->url]['rotapost'] = Domains::where('url', $domain->url)->where('source', 'rotapost')->first();
            $data[$domain->url]['gogetlinks'] = Domains::where('url', $domain->url)->where('source', 'gogetlinks')->first();
            if($data[$domain->url]['miralinks']){
                $data[$domain->url]['lang'] = $data[$domain->url]['miralinks']->language;
                $data[$domain->url]['links'] = $data[$domain->url]['miralinks']->links;
            }
        }
        
        return view('domains.index', compact('data'));
    }

}
