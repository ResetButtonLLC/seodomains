<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\DB;

class DomainExporter
{
    public static function exportXLS($domains)
    {
        $writer = new \XLSXWriter();

        $header = array(
            'domain' => 'string',
            'Лучшая цена' => 'string',
            'Collaborator цена' => '0.00',
            'Prnews цена' => '0.00',
            'Prposting цена' => '0.00',
            'Страна' => 'string',
            'Тематика' =>'string',
            'Траффик' => 'integer',
            'Ahrefs DR' => 'integer',
            'Majestic CF' => 'integer',
            'Majestic TF' => 'integer',
        );

        //write header
        $writer->writeSheetHeader('Sheet1', $header);

        //set styles
        $styles = array(['color'=>'#0000FF'], ['color'=>'#0000FF'], null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);

        //set rows
        $row = 2;
        foreach ($domains as $domain) {
            $writer->writeSheetRow('Sheet1', array(
                '=HYPERLINK("http://' . $domain->domain . '","' . $domain->domain . '")',
                "=AA".$row,
                ($domain->collaborator_price) ? '=HYPERLINK("https://collaborator.pro/creator/article/view?id=' . $domain->collaborator_domain_id . '","' . $domain->collaborator_price . '")' : '',
                ($domain->prnews_price) ? '=HYPERLINK("https://prnews.io/' . self::createPrnewsLink($domain->prnews_domain_id,$domain->prnews_domain_id) . '","' . $domain->prnews_price . '")' : '',
                ($domain->prposting_price) ? '=HYPERLINK("https://prposting.com/","' . $domain->prposting_price . '")' : '',
                $domain->country,
                $domain->theme,
                $domain->traffic,
                $domain->ahrefs_dr,
                $domain->majestic_cf,
                $domain->majestic_tf
            ), $styles);
            $row++;
        }

        //export to exel
        $filename = storage_path('app/domains.xlsx');
        $writer->writeToFile($filename);

        return $filename;
    }

    private static function createPrnewsLink(int $prnewsDomainId, string $domain)
    {
        $textpart = preg_replace('/[\W_]/','',$domain).'.html';
        return $prnewsDomainId.'-'.$textpart;
    }

}