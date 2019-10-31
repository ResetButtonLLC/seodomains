<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domains;
use Illuminate\Database\Eloquent\Builder;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DomainsExport;

class DomainsController extends Controller {

    public function index(Request $request) {

        if (isset($request->export))
        {
            /*
            $data = DB::table('domains')
                ->leftJoin('miralinks', 'domains.id', '=', 'miralinks.domain_id')
                ->leftJoin('rotapost', 'domains.id', '=', 'rotapost.domain_id')
                ->leftJoin('gogetlinks', 'domains.id', '=', 'gogetlinks.domain_id')
                ->leftJoin('sape', 'domains.id', '=', 'sape.domain_id')
                ->select('domains.url', 'miralinks.placement_price as miralinks_price', 'miralinks.writing_price as miralinks_writing_price', 'rotapost.placement_price  as rotapost_price', 'rotapost.writing_price  as rotapost_writing_price', 'gogetlinks.placement_price as gogetlinks_price', 'sape.placement_price as sape_price', 'miralinks.site_id as miralinks_site_id','miralinks.theme', 'miralinks.desc', 'miralinks.region', 'miralinks.google_index', 'miralinks.lang', 'miralinks.links', 'domains.ahrefs_dr', 'domains.ahrefs_inlinks', 'domains.ahrefs_outlinks', 'domains.majestic_cf','domains.majestic_tf','domains.serpstat_traffic')
                ->get();

            //dd($data[2]);

            //$data = collect(array( 'url' => 'rzn.aif.ru', 'miralinks_price' => '=HYPERLINK("http://example.microsoft.com/report/budget report.xlsx", "9300.0")', 'miralinks_writing_price' => 500.0, 'rotapost_price' => NULL, 'rotapost_writing_price' => NULL, 'gogetlinks_price' => NULL, 'sape_price' => NULL, 'miralinks_site_id' => 120549, 'theme' => 'Авто и Мото; Газеты, СМИ, порталы; Женский раздел', 'desc' => 'Рязанское подразделение Аргументов и Фактов. Все основные новости региона и РФ в целом. Корпоративная политика АиФ в материалах сайта. Требования к размещению: 1. Не размещаются простые сео-тексты; 2. Ссылка должна быть безанкорная, можно добавлять контакты в конце текста; 3. Текст должен иметь отношение к региону издания, быть интересен для читателя, а не для поисковиков. Пример хорошей статьи - http://www.oren.aif.ru/money/finance/v_orenburge_otyskat_rabotu_proshche_vsego_prodavcam_i_logistam Размещение материала происходит в основную ленту наравне с остальными редакторскими публикациями. Все материалы оформляются изображениями, хорошо форматируются. Также мы со своей стороны обеспечиваем усиление ссылки путем размещения ее в Твиттере/ЖЖ/социальных сетях.', 'region' => 'Россия', 'google_index' => 24100, 'lang' => 'ru', 'links' => 1, 'ahrefs_dr' => 83, 'ahrefs_inlinks' => 515, 'ahrefs_outlinks' => NULL, 'majestic_cf' => 41, 'majestic_tf' => 30, 'serpstat_traffic' => 0));
            */
            return Excel::download(new DomainsExport, 'domains.xlsx');

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

        return view('domains.index', compact(['domains']));
    }
}
