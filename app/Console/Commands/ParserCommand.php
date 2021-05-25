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

    public function __construct() {
        parent::__construct();
    }

    public function clearLog($command)
    {
        $log_name = explode(':', $command)[1];
        $log_path = storage_path('logs/parsers/' . $log_name . '/' . $log_name . '.log');
        config(['logging.channels.parser.path' => $log_path]);
        file_put_contents($log_path, "");
    }

    public function writeLog($command, $message)
    {
        $log_name = explode(':', $command)[1];
        $log_path = storage_path('logs/parsers/' . $log_name . '/' . $log_name . '.log');
        config(['logging.channels.parser.path' => $log_path]);
        $this->line($message);
        Log::channel('parser')->info($message);
    }

    public function writeLogFile($command, $file_name, $data)
    {
        $log_name = explode(':', $command)[1];
        file_put_contents(storage_path('logs/parsers/' . $log_name . '/' . $file_name), $data);
    }
}