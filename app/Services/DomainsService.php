<?php
/**
 * Created by PhpStorm.
 * User: a.shatrov
 * Date: 25.01.2021
 * Time: 12:17
 */

namespace App\Services;

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

    public static function exportXLS($domains)
    {
        //include xlsxwriter class
        include_once("vendor/mk-j/php_xlsxwriter/xlsxwriter.class.php");

        //new class instance
        $writer = new \XLSXWriter();

        //create header
        $header = array(
            'URL'=>'string',
            'Miralinks цена размещения'=>'integer',
            'Miralinks цена написания'=>'integer',
            'Gogetlinks цена размещения'=>'integer',
            'Rotapost цена размещения'=>'integer',
            'Rotapost цена написания'=>'integer',
            'PR.Sape.ru цена размещения'=>'integer',
            'Prnews цена размещения'=>'integer',
            'Prnews посещаемость'=>'string',
            'Collaborator цена размещения'=>'integer',
            'Страна'=>'string',
            'Тематика'=>'string',
            'Ahrefs DR'=>'integer',
            'Ahrefs Outlinks'=>'integer',
            'Ahrefs Positions Top10'=>'integer',
            'Ahrefs Traffic Top10'=>'integer',
            'Ahrefs Inlinks'=>'integer',
            'Google Index'=>'integer',
            'Количество размещаемых ссылок (Миралинкс)'=>'integer',
            'Язык'=>'string',
            'Majestic CF'=>'integer',
            'Majestic TF'=>'integer',
            'Описание'=>'string',
        );

        //write header
        $writer->writeSheetHeader('Sheet1', $header);

        //set styles
        $styles = array(['color'=>'#0000FF'], ['color'=>'#0000FF'], null, null, ['color'=>'#0000FF'], null, null, null, null, ['color'=>'#0000FF'], null, null, null, null, null, null, null, null, null, null, null, null, null);

        //set rows
        foreach ($domains as $data) {
            $writer->writeSheetRow('Sheet1', array(
                '=HYPERLINK("http://' . $data->url . '","' . $data->url . '")',
                (isset($data->miralinks_placement_price)) ? '=HYPERLINK("https://anonym.to/?https://www.miralinks.ru/catalog/profileView/' . $data->miralinks_site_id . '","' . $data->miralinks_placement_price . '")' : '',
                $data->miralinks_writing_price,
                $data->gogetlinks_placement_price,
                (isset($data->rotapost_placement_price)) ? '=HYPERLINK("https://anonym.to/?https://www.rotapost.ru/buy/site/?' . $data->url . '","' . $data->rotapost_placement_price . '")' : '',
                $data->rotapost_writing_price,
                $data->sape_placement_price,
                $data->prnews_placement_price,
                $data->prnews_audience,
                (isset($data->collaborators_placement_price)) ? '=HYPERLINK("https://collaborator.pro/creator/article/view?id=' . $data->collaborators_site_id . '","' . $data->collaborators_placement_price . '")' : '',
                $data->country,
                $data->miralinks_theme,
                $data->ahrefs_dr,
                $data->ahrefs_outlinks,
                $data->ahrefs_positions_top10,
                $data->ahrefs_traffic_top10,
                $data->ahrefs_inlinks,
                $data->miralinks_google_index,
                $data->miralinks_links,
                $data->miralinks_lang,
                $data->majestic_cf,
                $data->majestic_tf,
                $data->miralinks_desc
            ), $styles);
        }

        //export to exel
        $filename = storage_path('app/domains.xlsx');
        $writer->writeToFile($filename);

        return $filename;
    }

}