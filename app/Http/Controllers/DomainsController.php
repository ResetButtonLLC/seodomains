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

class DomainsController extends Controller {

    public function index(Request $request) {


        if (isset($request->export)) {
            ini_set('max_execution_time', 0);

            $domains = DB::table('domains')
                    ->leftJoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
                    ->leftJoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
                    ->leftJoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
                    ->leftJoin('sape', 'domains.id', '=', 'sape.domain_id')
                    ->leftJoin('prnews', 'domains.id', '=', 'prnews.domain_id')
                    ->select('domains.url',
                        'miralinks.placement_price as miralinks_price', 'miralinks.writing_price as miralinks_writing_price',
                        'rotapost.placement_price  as rotapost_price', 'rotapost.writing_price  as rotapost_writing_price',
                        'gogetlinks.placement_price as gogetlinks_price',
                        'sape.placement_price as sape_price',
                        'miralinks.site_id as miralinks_site_id', 'miralinks.theme', 'miralinks.desc', 'domains.country', 'miralinks.google_index', 'miralinks.lang', 'miralinks.links',
                        'prnews.price as prnews_price','prnews.audience as prnews_audience',
                        'domains.ahrefs_dr', 'domains.ahrefs_inlinks', 'domains.ahrefs_outlinks', 'domains.ahrefs_positions_top10', 'domains.ahrefs_traffic_top10', 'domains.majestic_cf', 'domains.majestic_tf')
                    ->whereNull('domains.deleted_at')
                    ->orderBy('domains.url', 'ASC')
//                        ->limit(500)
                    ->get();

//

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers_placeholder['miralinks'] = isset($request->resource['miralinks']) ?  ['Miralinks цена размещения','Miralinks цена написания'] : [];
            $headers_placeholder['gogetlinks'] = isset($request->resource['gogetlinks']) ? ['Gogetlinks цена размещения'] : [];
            $headers_placeholder['rotapost'] = isset($request->resource['rotapost']) ? ['Rotapost цена размещения','Rotapost цена написания'] : [];
            $headers_placeholder['sape'] = isset($request->resource['sape']) ? ['PR.Sape.ru цена размещения'] : [];
            $headers_placeholder['prnews'] = isset($request->resource['prnews']) ? ['Prnews цена размещения', 'Prnews посещаемость'] : [];

            $sheet->fromArray(
                array_merge(
                    [
                'URL',
                'Ahrefs DR',
                'Ahrefs Outlinks',
                'Ahrefs Positions Top10',
                'Ahrefs Traffic Top10'],
                 $headers_placeholder['miralinks'],
                 $headers_placeholder['gogetlinks'],
                 $headers_placeholder['rotapost'],
                 $headers_placeholder['sape'],
                 $headers_placeholder['prnews'],
                ['страна',
                'тематика',
                'Google Index',
                'Количество размещаемых ссылок (Миралинкс)',
                'Ahrefs Inlinks',
                'язык',
                'Majestic CF',
                'Majestic TF',
                'описание',
                    ]), // The data to set
                    NULL, // Array values with this value will not be set
                    'A1'         // Top left coordinate of the worksheet range where
                    //    we want to set these values (default is A1)
            );


            foreach ($domains as $r => $data) {
                //Ряд Потому что эксель начинается с 1 а не с 0, первый ряд - заголовки
                $row = $r + 2;
                $column = 1;

                //URL
                $sheet->setCellValueByColumnAndRow($column, $row, $data->url);
                $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('http://' . $data->url);
                $sheet->getStyleByColumnAndRow($column++, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);

                ///////Метрики
                //Ahrefs
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_dr);
                
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_outlinks);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_positions_top10);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_traffic_top10);

                //MIRALINKS (какой то баг с ссылкой - не работает прямая)
                if (isset($request->resource['miralinks'])) {
                    $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_price);
                    if ($data->miralinks_price) {
                        $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?https://www.miralinks.ru/catalog/profileView/' . $data->miralinks_site_id);
                        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                    }
                    $column++;

                    $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks_writing_price);
                    if ($data->miralinks_writing_price) {
                        $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?https://www.miralinks.ru/catalog/profileView/' . $data->miralinks_site_id);
                        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                    }
                    $column++;
                }

                //GOGETLINKS
                if (isset($request->resource['gogetlinks'])) {
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->gogetlinks_price);
                }

                //ROTAPOST
                if (isset($request->resource['rotapost'])) {
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost_price);
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost_writing_price);
                }

                //SAPE
                if (isset($request->resource['sape'])) {
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->sape_price);
                }

                //PRNEWS
                if (isset($request->resource['prnews'])) {
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews_price);
                    $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews_audience);
                }

                ////////////
                //Тематика, регион, описание, язык
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->country);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->theme);
                
                //Google Index
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->google_index);

                //Количество размещаемых ссылок (Миралинкс),
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->links);
                
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_inlinks);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->lang);
                //Majestic
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_cf);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_tf);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->desc);
                //Serpstat
                //$sheet->setCellValueByColumnAndRow($column++, $row, $data->serpstat_traffic);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = storage_path('app/domains-' . date('Y-m-d-H-i-s') . '.xlsx');
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
            $update_dates[$update_date->name] = date('d-m-Y', strtotime($update_date->updated_at));
        }

        return view('domains.index', compact(['domains', 'update_dates']));
    }

}
