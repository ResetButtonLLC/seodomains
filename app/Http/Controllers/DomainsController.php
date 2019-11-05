<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Domains, Update};
use Illuminate\Database\Eloquent\Builder;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell;


class DomainsController extends Controller {

    public function index(Request $request) {

        if (isset($request->export))
        {
            ini_set('max_execution_time', 0);

            $domains = DB::table('domains')
                ->leftJoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
                ->leftJoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
                ->leftJoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
                ->leftJoin('sape', 'domains.id', '=', 'sape.domain_id')
                ->select('domains.url', 'miralinks.placement_price as miralinks_price', 'miralinks.writing_price as miralinks_writing_price', 'rotapost.placement_price  as rotapost_price', 'rotapost.writing_price  as rotapost_writing_price', 'gogetlinks.placement_price as gogetlinks_price', 'sape.placement_price as sape_price', 'miralinks.site_id as miralinks_site_id','miralinks.theme', 'miralinks.desc', 'domains.country', 'miralinks.google_index', 'miralinks.lang', 'miralinks.links', 'domains.ahrefs_dr', 'domains.ahrefs_inlinks', 'domains.ahrefs_outlinks', 'domains.majestic_cf','domains.majestic_tf','domains.serpstat_traffic')
                ->whereNull('domains.deleted_at')
                ->orderBy('domains.url', 'ASC')
                //->limit(10000)
                ->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();


            //Заголовки
            $sheet->fromArray(
                [
                    'URL',
                    'Miralinks цена размещения',
                    'Miralinks цена написания',
                    'Gogetlinks цена размещения',
                    'Rotapost цена размещения',
                    'Rotapost цена написания',
                    'PR.Sape.ru цена размещения',

                    'страна',
                    'тематика',
                    'описание',
                    'язык',

                    'Google Index',

                    'Количество размещаемых ссылок (Миралинкс)',

                    'Ahrefs DR',
                    'Ahrefs Inlinks',
                    'Ahrefs Outlinks',

                    'Majestic CF',
                    'Majestic TF',

                    'Serpstat traffic',

                ],  // The data to set
                    NULL,        // Array values with this value will not be set
                    'A1'         // Top left coordinate of the worksheet range where
                //    we want to set these values (default is A1)
                );



            foreach($domains as $r => $data) {
                //Ряд Потому что эксель начинается с 1 а не с 0, первый ряд - заголовки
                $row = $r+2;
                $column = 1;

                //URL
                $sheet->setCellValueByColumnAndRow($column, $row, $data->url);
                $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('http://'.$data->url);
                $sheet->getStyleByColumnAndRow($column++, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);

                //MIRALINKS (какой то баг с ссылкой - не работает прямая)
                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_price);
                if ($data->miralinks_price) {
                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?https://www.miralinks.ru/catalog/profileView/'.$data->miralinks_site_id);
                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                }
                $column++;

                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_writing_price);
                if ($data->miralinks_writing_price) {
                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?https://www.miralinks.ru/catalog/profileView/'.$data->miralinks_site_id);
                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                }
                $column++;

                //GOGETLINKS
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->gogetlinks_price);

                //ROTAPOST
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost_price);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost_writing_price);

                //SAPE
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->sape_price);

                ////////////

                //Тематика, регион, описание, язык
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->country);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->theme);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->desc);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->lang);

                //Google Index
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->google_index);

                //Количество размещаемых ссылок (Миралинкс),
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->links);

                ///////Метрики

                //Ahrefs
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_dr);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_inlinks);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_outlinks);

                //Majestic
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_cf);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_tf);

                //Serpstat
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->serpstat_traffic);

           }

            $writer = new Xlsx($spreadsheet);
            $filename = storage_path('app/domains-'.date ('Y-m-d-H-i-s').'.xlsx');
            $writer->save($filename);

            return response()->download($filename)->deleteFileAfterSend();

                // Call writer methods here
        }

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

        foreach (Update::all() as $update_date) {
            $update_dates[$update_date->name] = date('d-m-Y',strtotime($update_date->updated_at));
        }

        return view('domains.index', compact(['domains', 'update_dates']));
    }
}
