<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Domains,
    Update
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ApiException;
use App\Services\DomainsService;

class DomainsController extends Controller {

    public function index(Request $request) {
        $domains = Domains::where('url', '<>', '')->whereNull('deleted_at');

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
            })->orWhereHas('collaborator', function (Builder $query) use ($theme) {
                $query->where('theme', 'like', '%' . $theme . '%');
            });
        }

        if ($request->price_from > 0) {
            $price_from = $request->price_from;
            $domains = $domains->whereHas('sape', function (Builder $query) use ($price_from) {
                $query->where('placement_price','>', $price_from);
            })->orWhereHas('miralinks', function (Builder $query) use ($price_from) {
                $query->where('placement_price','>', $price_from);
            })->orWhereHas('gogetlinks', function (Builder $query) use ($price_from) {
                $query->where('placement_price','>', $price_from);
            })->orWhereHas('rotapost', function (Builder $query) use ($price_from) {
                $query->where('placement_price','>', $price_from);
            })->orWhereHas('prnews', function (Builder $query) use ($price_from) {
                $query->where('price','>', $price_from);
            })->orWhereHas('collaborator', function (Builder $query) use ($price_from) {
                $query->where('price','>', $price_from);
            });
        }

        if ($request->price_to > 0) {
            $price_to = $request->price_to;
            $domains = $domains->whereHas('sape', function (Builder $query) use ($price_to) {
                $query->where('placement_price','<', $price_to);
            })->orWhereHas('miralinks', function (Builder $query) use ($price_to) {
                $query->where('placement_price','<', $price_to);
            })->orWhereHas('gogetlinks', function (Builder $query) use ($price_to) {
                $query->where('placement_price','<', $price_to);
            })->orWhereHas('rotapost', function (Builder $query) use ($price_to) {
                $query->where('placement_price','<', $price_to);
            })->orWhereHas('prnews', function (Builder $query) use ($price_to) {
                $query->where('price','<', $price_to);
            })->orWhereHas('collaborator', function (Builder $query) use ($price_to) {
                $query->where('price','<', $price_to);
            });
        }

        if (isset($request->export)) {
            return response()->download(storage_path('app/domains.xlsx'), 'domains-' . date('Y-m-d-H-i-s') . '.xlsx');
        } else {

            $domains = $domains->orderBy('url')->paginate(env('PAGE_COUNT'));

            foreach (Update::all() as $update_date) {
                $update_dates[$update_date->name] = date('d-m-Y', strtotime($update_date->updated_at));
            }

            return view('domains.index', compact(['domains', 'update_dates']));
        }
    }



    public function averagePriceForDr()
    {

        $output = data_get(request()->route()->getAction(),'output','frontend');

        $domains = DB::table('domains')
            ->leftjoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
            ->leftjoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
            ->leftjoin('prnews', 'domains.id', '=', 'prnews.domain_id')
            ->leftjoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
            ->leftjoin('sape', 'domains.id', '=', 'sape.domain_id')
            ->select('domains.id','domains.url', 'domains.ahrefs_dr', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
            ->whereNull('domains.deleted_at')
            ->whereNotNull('domains.ahrefs_dr')
        //    ->limit(50)
            ->orderBy('domains.id')
            ->get();

        $dr = array_fill(0,100,0);
        //Из за того что Prnews заполняется не числом проблема с вычислениеями
        foreach ($domains as $domain) {
            //Вычисляем среднее для домена
            $domain_average = $this->getAverage($domain);

            //Вычисляем среднее для DR
            if ($domain_average) {
                //Если данные для ДР уже сущестуют - вычисляем среднее
                if ($dr[$domain->ahrefs_dr]) {
                    $dr[$domain->ahrefs_dr] = round(($dr[$domain->ahrefs_dr]+$domain_average)/2,0);
                } else {
                    $dr[$domain->ahrefs_dr] = $domain_average;
                }
            }

        }

        if ($output == "json") {
            return response()->success(["dr-price" => (object)$dr]);
        } else {
            return view('domains.averagedr',[
                'dr' => $dr
            ]);
        }

    }

    public function getDomainData(Request $request)
    {

        if (request()->method() == "GET") {
            $validator = Validator::make($request->all(),[
                'domain' => 'required|string',
            ]);
            $domains = [$request->input('domain','')];
        };

        if (request()->method() == "POST") {
            $validator = Validator::make($request->all(),[
                'domains' => 'required|array',
            ]);
            $domains = $request->input('domains');
        };

        if ($validator->fails()) {
            throw new ApiException(implode(',', $validator->errors()->all()), 422);
        }

        $result = DomainsService::getDataForDomains($domains);

        return response()->success((array)$result);

    }


    private function getAverage($values)
    {
        $prices = array();
        foreach ($values as $key => $data) {
            if (stripos($key,'price')) {
                $data = str_ireplace(',','.',$data);
                $prices[] = floatval($data);
            }
        }

        $prices = array_filter($prices);

        if ($prices) {
            $average = round(array_sum($prices)/count($prices),0);
        } else {
            $average = null;
        }

        return $average;

    }

}
