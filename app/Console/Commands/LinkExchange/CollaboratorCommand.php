<?php

namespace App\Console\Commands\LinkExchange;

use App\Services\Parsers\Collaborator;
use Illuminate\Console\Command;

class CollaboratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:collaborator';

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

        $parser = new Collaborator();

        $parser->parse();

        return 0;
    }
}
