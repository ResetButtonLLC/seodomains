<?php

namespace App\Http\Controllers;

use http\Env\Response;
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

        if (isset($request->export)) {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '2048M');
            $offset = $limit = 35000;
            $sites = $domains->orderBy('domains.url', 'ASC')
                    ->offset(0)
                    ->limit($limit)
                    ->get();

            $data = $this->addData($request, $sites);

            while ($sites = $domains->orderBy('domains.url', 'ASC')->offset($offset)->limit($limit)->get()) {
                if(count($sites) == 0){
                    break;
                }
                $data = $this->addData($request, $sites, $data[0], $data[1], $data[2], $data[3]);
                $offset = $offset + $offset;
            }

            
//            dd($data);


            return response()->download($data[0])->deleteFileAfterSend();

            // Call writer methods here
        } else {

            $domains = $domains->orderBy('url')->paginate(env('PAGE_COUNT'));

            foreach (Update::all() as $update_date) {
                $update_dates[$update_date->name] = date('d-m-Y', strtotime($update_date->updated_at));
            }

            return view('domains.index', compact(['domains', 'update_dates']));
        }
    }

    private function addData($request, $domains, $filename = null, $r = 0, $spreadsheet = null, $sheet = null) {
        if (!$filename) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers_placeholder['miralinks'] = isset($request->resource['miralinks']) ? ['Miralinks цена размещения', 'Miralinks цена написания'] : [];
            $headers_placeholder['gogetlinks'] = isset($request->resource['gogetlinks']) ? ['Gogetlinks цена размещения'] : [];
            $headers_placeholder['rotapost'] = isset($request->resource['rotapost']) ? ['Rotapost цена размещения', 'Rotapost цена написания'] : [];
            $headers_placeholder['sape'] = isset($request->resource['sape']) ? ['PR.Sape.ru цена размещения'] : [];
            $headers_placeholder['prnews'] = isset($request->resource['prnews']) ? ['Prnews цена размещения', 'Prnews посещаемость'] : [];

            $sheet->fromArray(
                    array_merge(
                            [
                'URL',
                'Ahrefs DR',
                'Ahrefs Outlinks',
                'Ahrefs Positions Top10',
                'Ahrefs Traffic Top10'], $headers_placeholder['miralinks'], $headers_placeholder['gogetlinks'], $headers_placeholder['rotapost'], $headers_placeholder['sape'], $headers_placeholder['prnews'], ['страна',
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
        }

        foreach ($domains as $data) {
            if (!$data->url)
                continue;
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
                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks['placement_price'] ? $data->miralinks['placement_price'] : '-');
                if ($data->miralinks['site_id']) {
                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?"https://www.miralinks.ru/catalog/profileView/' . $data->miralinks['site_id'] . '"');
                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                }
                $column++;

                $sheet->setCellValueByColumnAndRow($column, $row, $data->miralinks['writing_price'] ? $data->miralinks['writing_price'] : '-');
//                if ($data->miralinks['site_id']) {
//                    $sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl('https://anonym.to/?"https://www.miralinks.ru/catalog/profileView/' . $data->miralinks['site_id'] . '"');
//                    $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
//                }
                $column++;
            }

            //GOGETLINKS
            if (isset($request->resource['gogetlinks'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->gogetlinks['placement_price']);
            }

            //ROTAPOST
            if (isset($request->resource['rotapost'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost['placement_price']);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->rotapost['writing_price']);
            }

            //SAPE
            if (isset($request->resource['sape'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->sape['placement_price']);
            }
            //PRNEWS
            if (isset($request->resource['prnews'])) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews['price']);
                $sheet->setCellValueByColumnAndRow($column++, $row, $data->prnews['audience']);
            }
            ////////////
            //Тематика, регион, описание, язык
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->country);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks ? $data->miralinks['theme'] : '');

            //Google Index
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks ? $data->miralinks['google_index'] : '');

            //Количество размещаемых ссылок (Миралинкс),
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks ? $data->miralinks['links'] : '');

            $sheet->setCellValueByColumnAndRow($column++, $row, $data->ahrefs_inlinks);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks ? $data->miralinks['lang'] : '');
//            dd($column++, $row);
            //Majestic
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_cf);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->majestic_tf);
            $sheet->setCellValueByColumnAndRow($column++, $row, $data->miralinks ? $data->miralinks['desc'] : '');
            //Serpstat
            //$sheet->setCellValueByColumnAndRow($column++, $row, $data->serpstat_traffic);
            $r++;
        }

        $writer = new Xlsx($spreadsheet);
        if (!$filename) {
            $filename = storage_path('app/domains-' . date('Y-m-d-H-i-s') . '.xlsx');
        }
        $writer->save($filename);

        return [$filename, $row, $spreadsheet, $sheet];
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
            return response()->json((object)$dr);
        } else {
            return view('domains.averagedr',[
                'dr' => $dr
            ]);
        }

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
