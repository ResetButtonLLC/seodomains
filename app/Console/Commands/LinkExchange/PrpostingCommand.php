<?php

namespace App\Console\Commands\LinkExchange;

use App\Services\Parsers\Prposting;
use Illuminate\Console\Command;

class PrpostingCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:prposting {page=1}';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Parse domains from prposting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        $page = intval($this->argument('page'));

        if ($page) {
            $parser = new Prposting();
            $parser->parse($page);
        } else {
            $this->info('{page} parameter is incorrect');
        }

        return 0;
    }
}