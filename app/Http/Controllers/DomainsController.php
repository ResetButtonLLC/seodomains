<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domains;

class DomainsController extends Controller {

    public function index(Request $request) {
        $domains = Domains::whereNotNull('url');

        if ($request->resource) {
            $domains = $domains->whereIn('source', array_keys($request->resource));
        }

        if ($request->theme) {
            $domains = $domains->where('theme', 'like', '%' . $request->theme . '%');
        }

        if ($request->price_from) {
            $domains = $domains->where('placement_price', '>=', $request->price_from);
        }
        if ($request->price_to) {
            $domains = $domains->where('placement_price', '<=', $request->price_to);
        }

        $domains = $domains->orderBy('url')->paginate(20);
        $data = [];
        foreach ($domains as $domain) {
            $data[$domain->url] = ['created_at' => '', 'lang' => '', 'links' => '', 'google_index' => '', 'theme' => '', 'desc' => '', 'region' => '', 'placement_price' => [], 'writing_price' => []];

            $miralinks = Domains::where('url', $domain->url)->where('source', 'miralinks')->first();
            $sape = Domains::where('url', $domain->url)->where('source', 'sape')->first();
            $rotapost = Domains::where('url', $domain->url)->where('source', 'rotapost')->first();
            $gogetlinks = Domains::where('url', $domain->url)->where('source', 'gogetlinks')->first();

            $data[$domain->url]['created_at'] = Domains::select('created_at')->where('url', $domain->url)->orderBy('created_at', 'desc')->first()->created_at;

            if ($miralinks) {
                $data[$domain->url]['lang'] = $miralinks->language;
                $data[$domain->url]['links'] = $miralinks->links;
                $data[$domain->url]['google_index'] = $miralinks->google_index;
                $data[$domain->url]['theme'] = $miralinks->theme;
                $data[$domain->url]['desc'] = $miralinks->name;
                $data[$domain->url]['region'] = $miralinks->region;
                $data[$domain->url]['site_id'] = $miralinks->site_id;
                
                if ($miralinks->writing_price) {
                    $data[$domain->url]['writing_price']['miralinks'] = $miralinks->writing_price;
                }
                if ($miralinks->placement_price) {
                    $data[$domain->url]['placement_price']['miralinks'] = $miralinks->placement_price;
                }
            }

            if ($rotapost) {
                if ($rotapost->writing_price) {
                    $data[$domain->url]['writing_price']['rotapost'] = $rotapost->writing_price;
                }
                if ($rotapost->placement_price) {
                    $data[$domain->url]['placement_price']['rotapost'] = $rotapost->placement_price;
                }
            }

            if ($sape) {
                if ($sape->writing_price) {
                    $data[$domain->url]['writing_price']['sape'] = $sape->writing_price;
                }
                if ($sape->placement_price) {
                    $data[$domain->url]['placement_price']['sape'] = $sape->placement_price;
                }
            }

            if ($gogetlinks) {
                if ($gogetlinks->writing_price) {
                    $data[$domain->url]['writing_price']['gogetlinks'] = $gogetlinks->writing_price;
                }
                if ($gogetlinks->placement_price) {
                    $data[$domain->url]['placement_price']['gogetlinks'] = $gogetlinks->placement_price;
                }
            }
        }

        foreach ($data as $domain=>$stats) {
            if ($stats['placement_price']) {
                asort($data[$domain]['placement_price']);
            }

            if ($stats['writing_price']) {
                asort($data[$domain]['writing_price']);
            }
        }

        return view('domains.index', compact(['data', 'domains']));
    }

}
