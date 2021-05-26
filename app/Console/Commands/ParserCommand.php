<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:log';

    protected $message;

    protected $log_name;
    protected $log_path;

    public function __construct() {
        parent::__construct();

        $this->log_path = storage_path('logs/parsers/' . $this->log_name . '/' . $this->log_name . '.log');

        config(['logging.channels.parser.path' => $this->log_path]);

        file_put_contents($this->log_path, "");
    }

    public function writeLog($message)
    {
        $this->line($message);
        Log::channel('parser')->info($message);
    }

    public function writeLogFile($file_name, $data)
    {
        file_put_contents(storage_path('logs/parsers/' . $this->log_name . '/' . $file_name), $data);
    }
}