<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Models\Domains;
use App\Models\Update;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinalizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:finalize 
        {--table=null : Table, that has to be cleaned from domains, that don\'t exist anymore} 
        {--hours=3 : If domain update time is older than this option - it\'ll be deleted }';

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

        $this->line ('Run finalize command');
        //Если передана дочерняя таблица, то удаляем из нее домены для которых время обновления больше переданного в параметре hours, т.к. это значит что они не были обновлены при последнем обновлении и в бирже их больше нету
        $child_table= $this->option('table');
        $child_table_timeout= (int)$this->option('hours');

        if ($child_table != 'null' && is_int($child_table_timeout)) {
            $this->line ('Deleting domains, that are no more exist in '.$child_table.' from database');
            DB::table($child_table)->where('updated_at', '<=',Carbon::now()->subHours($child_table_timeout)->toDateTimeString())->delete();
            DB::table($child_table)->whereNull('updated_at')->delete(); //Очистка от старого мусора, потом можно удалить
        }

        //Удаляем домены которых нету ни в одной бирже
        $this->line("Deleting domains, that doesn't exist in any service");

        Domains::doesntHave('miralinks')->doesntHave('gogetlinks','and')->doesntHave('sape','and')->doesntHave('rotapost','and')->doesntHave('prnews','and')->doesntHave('collaborators','and')->delete();

        //Присваиваем регион в зависимости от доменной зоны, если его нету
        $domains_without_country = Domains::select('id', 'url')->whereNull('country')->get();
        $this->line('Setting country for '.count($domains_without_country).' domains');

        foreach ($domains_without_country as $no_country_domain) {
            $country = $this->detectCountryByDomain($no_country_domain->url);
            Domains::where('id',$no_country_domain->id)->update(['country'=> $country]);
        }

        if ($child_table != 'null') {

            $this->line ('Writing '.$child_table.' update data');
            Update::setLinkExchangeUpdated($child_table);
        }

        $this->line("Create new XLS file");

        $this->call('domains:generate');

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
