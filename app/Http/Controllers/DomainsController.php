<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Domain,
    Update
};

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ApiException;
use App\Services\DomainsService;

class DomainsController extends Controller {

    public function index(Request $request) {

        if (isset($request->export)) {
            return response()->download(storage_path('app/domains.xlsx'), 'domains-' . date('Y-m-d-H-i-s') . '.xlsx');
        } else {

            $domains_count = Domain::where('domain', '<>', '')->count();

            $link_stocks = [];
            foreach (Update::all() as $update_date) {
                $link_stocks[$update_date->name]['update_date'] = date('d-m-Y', strtotime($update_date->updated_at));
                $model = 'App\\Models\\' . ucfirst($update_date->name);
                $link_stocks[$update_date->name]['count'] = $model::count();
            }

            //dd($link_stocks);

            return view('domains.index', compact(['domains_count', 'link_stocks']));
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
            ->select('domains.id','domains.domain', 'domains.ahrefs_dr', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
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
