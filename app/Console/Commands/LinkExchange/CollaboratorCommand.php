<?php

namespace App\Console\Commands\LinkExchange;

use App\Services\Parsers\Collaborator;
use App\Services\Parsers\Prposting;
use Illuminate\Console\Command;

class CollaboratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:collaborator {page=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse domains from collaborator';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $page = intval($this->argument('page'));

        if ($page) {
            $parser = new Collaborator();
            $parser->parse($this->argument('page'));
        } else {
            $this->info('{page} parameter is incorrect');
        }

        return 0;

    }
}
