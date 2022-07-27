<?php

namespace App\Console\Commands\LinkExchange;

use Illuminate\Console\Command;
use App\Services\Parsers\Prnews;

class PrnewsCommand extends Command
{
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
    protected $description = 'Parse Prnews.io via downloading CSV file using chrome';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $parser = new Prnews();
        $parser->parse();
        return 0;
    }
}
