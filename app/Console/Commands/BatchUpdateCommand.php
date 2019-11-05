<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BatchUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:batchupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all update commands in nessesary order';

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
        //Обновляем домены из бирж
        $this->call('domains:sape');
        $this->call('domains:miralinks');
        $this->call('domains:gogetlinks');
        //$this->call('domains:rotapost'); //не проверен


        //Обновляем параметры
        $this->call('domains:ahrefs');
        $this->call('domains:traffic');
        /*
        $this->call('domains:majestic');
        */


    }
}
