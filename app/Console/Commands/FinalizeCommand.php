<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Models\Domains;

class FinalizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:finalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run after every local update. Functions: [*] clean orphaned domains [*] set region ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //Удаляем домены которых нету ни в одной бирже
        $this->line("Deleting domains that doesn't exist in any service");
        Domains::doesntHave('miralinks')->doesntHave('gogetlinks','and')->doesntHave('sape','and')->delete();

        //Присваиваем регион в зависимости от доменной зоны, если его нету
        $domains_without_country = Domains::select('id', 'url')->whereNull('country')->get();
        $this->line('Setting country for '.count($domains_without_country).' domains');

        foreach ($domains_without_country as $no_country_domain) {
            $country = $this->detectCountryByDomain($no_country_domain->url);
            Domains::where('id',$no_country_domain->id)->update(['country'=> $country]);
        }

        $this->line("Job done");


    }

    private function detectCountryByDomain($domain) : string
    {
        $result_country = 'СНГ';

        $country_domains = array(
            'Украина' => ['ua','укр'],
            'Казахстан' => ['kz','kg'],
            'Россия' => ['ru','рф','москва','рус','moscow']
        );

        foreach ($country_domains as $country => $zones) {
            foreach ($zones as $zone) {
                if (preg_match ( '/\.'.$zone.'$/ui', $domain)) {
                    $result_country = $country;
                }
            }
        }

        return $result_country;
    }

    private function endsWith($haystack, $needle)
    {
        $needle = '.'.$needle;
        return substr_compare($haystack, $needle, -strlen($needle), true) === 0;
    }
}
