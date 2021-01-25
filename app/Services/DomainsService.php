<?php
/**
 * Created by PhpStorm.
 * User: a.shatrov
 * Date: 25.01.2021
 * Time: 12:17
 */

namespace App\Services;

use App\Models\Domains;
use Illuminate\Support\Facades\DB;



class DomainsService
{
    static public function getDataForDomains(array $domains) : array
    {

        //Получаем домен
        //$result = Domains::with('gogetlinks','miralinks','prnews','rotapost','sape')->where('url', '=',$domain)->first();
        //Старый вариант для одного домена
        /*
        $result = DB::table('domains')
            ->leftjoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
            ->leftjoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
            ->leftjoin('prnews', 'domains.id', '=', 'prnews.domain_id')
            ->leftjoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
            ->leftjoin('sape', 'domains.id', '=', 'sape.domain_id')
            ->select('domains.*', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
            ->where('domains.url','=',$domain)
            ->first();
        */

        $dbresult = DB::table('domains')
            ->leftjoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
            ->leftjoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
            ->leftjoin('prnews', 'domains.id', '=', 'prnews.domain_id')
            ->leftjoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
            ->leftjoin('sape', 'domains.id', '=', 'sape.domain_id')
            ->select('domains.*', 'gogetlinks.placement_price as gogetlinks_placement_price','miralinks.placement_price as miralinks_placement_price','prnews.price as prnews_placement_price','rotapost.placement_price as rotapost_placement_price','sape.placement_price as sape_placement_price')
            ->whereNull('domains.deleted_at')
            ->whereIn('domains.url', $domains)
            //Сортировка сохраняет порядок $domains
            //Оставлю на память, но так не работает - если домена не существует, то пропуска не будет
            //->orderByRaw('FIELD(domains.url, '.'"'.implode('","',$domains).'"'.')')
            ->get();

        //Необходимо сохранить сортировку переданную на вход и для каждого домена, который отсутствует в базе, установить id:0

        foreach ($domains as $domain) {
            $item = $dbresult->firstWhere('url', $domain);
            if($item) {
                $item = (array) $item;
                $result[$domain] = array_merge(['found' => true],$item);
            } else {
                $result[$domain] = ['found' => false,"id" => 0, "url" => $domain];
            }

        }

        return $result;

    }
}