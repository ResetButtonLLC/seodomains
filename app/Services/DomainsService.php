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
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;



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

    public static function exportXLS($request, $domains) {

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //$headers_placeholder['miralinks'] = isset($request->resource['miralinks']) ? ['Miralinks цена размещения', 'Miralinks цена написания'] : [];
        //$headers_placeholder['gogetlinks'] = isset($request->resource['gogetlinks']) ? ['Gogetlinks цена размещения'] : [];
        //$headers_placeholder['rotapost'] = isset($request->resource['rotapost']) ? ['Rotapost цена размещения', 'Rotapost цена написания'] : [];
        //$headers_placeholder['sape'] = isset($request->resource['sape']) ? ['PR.Sape.ru цена размещения'] : [];
        //$headers_placeholder['prnews'] = isset($request->resource['prnews']) ? ['Prnews цена размещения', 'Prnews посещаемость'] : [];

//        $sheet->fromArray(
//            array_merge(
//                [
//                    'URL',
//                    'Ahrefs DR',
//                    'Ahrefs Outlinks',
//                    'Ahrefs Positions Top10',
//                    'Ahrefs Traffic Top10'], $headers_placeholder['miralinks'], $headers_placeholder['gogetlinks'], $headers_placeholder['rotapost'], $headers_placeholder['sape'], $headers_placeholder['prnews'], ['страна',
//                'тематика',
//                'Google Index',
//                'Количество размещаемых ссылок (Миралинкс)',
//                'Ahrefs Inlinks',
//                'язык',
//                'Majestic CF',
//                'Majestic TF',
//                'описание',
            //]), // The data to set
            //NULL, // Array values with this value will not be set
            //'A1'         // Top left coordinate of the worksheet range where
        //    we want to set these values (default is A1)
        //);


        $sheet->fromArray(
            array_merge(
                [
                    'URL',
                    'Miralinks цена размещения',
                    'Miralinks цена написания',
                    'Gogetlinks цена размещения',
                    'Rotapost цена размещения',
                    'Rotapost цена написания',
                    'PR.Sape.ru цена размещения',
                    'Prnews цена размещения',
                    'Prnews посещаемость',
                    'Collaborator цена размещения',
                    'Страна',
                    'Тематика',
                    'Ahrefs DR',
                    'Ahrefs Outlinks',
                    'Ahrefs Positions Top10',
                    'Ahrefs Traffic Top10',
                    'Ahrefs Inlinks',
                    'Google Index',
                    'Количество размещаемых ссылок (Миралинкс)',
                    'Язык',
                    'Majestic CF',
                    'Majestic TF',
                    'Описание',
                ]
            ), // The data to set
            NULL, // Array values with this value will not be set
            'A1'         // Top left coordinate of the worksheet range where
        //    we want to set these values (default is A1)
        );

        //Ряд Потому что эксель начинается с 1 а не с 0, первый ряд - заголовки
        $row = 2;
        foreach ($domains as $data) {
            $column = 1;

            //URL
            $sheet->setCellValueByColumnAndRow($column, $row, $data->url);
            $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('http://' . $data->url);
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
            $column++;

            //MIRALINKS (какой то баг с ссылкой - не работает прямая)
            //if (isset($request->resource['miralinks'])) {
                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_placement_price);
                if ($data->miralinks_site_id) {
                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?"https://www.miralinks.ru/catalog/profileView/' . $data->miralinks_site_id . '"');
                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                }
                $column++;

                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_writing_price);
                $column++;
            //}

            //GOGETLINKS
            //if (isset($request->resource['gogetlinks'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->gogetlinks_placement_price);
                //не нашел прямую сссылку
//                if ($data->gogetlinks_domain_id) {
//                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?"https://www.miralinks.ru/catalog/profileView/' . $data->gogetlinks_domain_id . '"');
//                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
//                }
                //$column++;

           // }

            //ROTAPOST
            //if (isset($request->resource['rotapost'])) {
                $sheet->setCellValueByColumnAndRow($column, $row, $data->rotapost_placement_price);
                if ($data->url) {
                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://www.rotapost.ru/buy/site/?' . $data->url);
                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                }
                $column++;

                $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost_writing_price);
            //}

            //SAPE
            //if (isset($request->resource['sape'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->sape_placement_price);
                //не нашел прямую сссылку
                //if ($data->sape_domain_id) {
                    //$sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?"https://www.sape.ru/seo/projects/edit/2865144' . $data->sape_domain_id . '"');
                    //$sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                //}
                //$column++;
            //}

            //PRNEWS
            //if (isset($request->resource['prnews'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews_placement_price);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews_audience);
            //}

            //COLLABORATOR
            $sheet->setCellValueByColumnAndRow($column, $row, $data->collaborators_placement_price);
            if ($data->id) {
                $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://collaborator.pro/creator/article/view?id=' . $data->id);
                $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
            }
            $column++;

            //Регион
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->country);

            //Тематика
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks_theme);

            //Ahrefs
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_dr);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_outlinks);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_positions_top10);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_traffic_top10);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_inlinks);

            //Google Index
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks_google_index);

            //Количество размещаемых ссылок (Миралинкс),
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks_links);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks_lang);

            //Majestic
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_cf);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_tf);

            //Фикс, убираем знак = в начале любого поля, т.к. это трактуется как формула
            //todo переделать на массив
            $data->miralinks_desc = preg_replace('/^=*/','',$data->miralinks_desc);

            //Описание
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks_desc);

            //Serpstat
            //$sheet->setCellValueByColumnAndRow($column++, $row, $data->serpstat_traffic);

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = storage_path('app/domains.xlsx');
        $writer->save($filename);

        return $filename;
    }
}