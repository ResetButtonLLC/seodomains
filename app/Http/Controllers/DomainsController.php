<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domains;
use Illuminate\Database\Eloquent\Builder;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;

class DomainsController extends Controller {

    public function index(Request $request) {
        $domains = Domains::whereNotNull('url');

        if ($request->resource) {
            $sources = $request->resource;
            foreach (array_keys($request->resource) as $key => $source) {
                if ($key == 0) {
                    $domains = $domains->has($source);
                } else {
                    $domains = $domains->orHas($source);
                }
            }
        }

        if ($request->theme) {
            $theme = $request->theme;
            $domains = $domains->whereHas('miralinks', function (Builder $query) use ($theme) {
                        $query->where('theme', 'like', '%' . $theme . '%');
                    })->orWhereHas('rotapost', function (Builder $query) use ($theme) {
                $query->where('theme', 'like', '%' . $theme . '%');
            });
        }

        if ($request->price_from) {
            $domains = $domains->where('placement_price', '>=', $request->price_from);
        }
        if ($request->price_to) {
            $domains = $domains->where('placement_price', '<=', $request->price_to);
        }

        $domains = $domains->orderBy('url')->paginate(env('PAGE_COUNT'));
        if (isset($request->export)) {
            $data = DB::table('domains')
                    ->leftJoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
                    ->leftJoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
                    ->leftJoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
                    ->leftJoin('sape', 'domains.id', '=', 'sape.domain_id')
                    ->select('domains.url', 'miralinks.placement_price_rur as miralinks_price', 'miralinks.writing_price_rur as miralinks_writing_price', 'rotapost.placement_price  as rotapost_price', 'rotapost.writing_price  as rotapost_writing_price', 'gogetlinks.placement_price as gogetlinks_price', 'sape.placement_price as sape_price', 'miralinks.theme', 'miralinks.desc', 'miralinks.region', 'miralinks.google_index', 'miralinks.lang', 'miralinks.links', 'domains.ahrefs_dr', 'domains.ahrefs_inlinks', 'domains.ahrefs_outlinks', 'domains.serpstat_traffic')
                    ->get();
            return (new FastExcel($data))->download('file.xlsx');
        }
        return view('domains.index', compact(['domains']));
    }

}
