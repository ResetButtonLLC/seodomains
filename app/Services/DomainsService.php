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