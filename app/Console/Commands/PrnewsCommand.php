<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;

class PrnewsCommand extends ParserCommand {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:prnews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get domains from prnews';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->initLog('prnews');

        $this->writeLog('prnews get with dusk test');

        $testResult = Artisan::call('dusk', [
            ' --filter' => 'PrnewsTest',
        ]);

        if ($testResult == 0) {
            $this->call('domains:finalize', [
                '--table' => 'prnews',
            ]);
        } else {
            $this->writeLog('prnews test has error');
        }

        return true;
    }
}