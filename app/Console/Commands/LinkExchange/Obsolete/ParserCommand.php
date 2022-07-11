<?php

namespace App\Console\Commands\LinkExchange\Obsolete;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:log';

    protected $message;

    protected $log_path;

    private $parser_name;

    public function __construct() {
        parent::__construct();
    }

    public function initLog($name)
    {
        $this->parser_name = $name;
        $this->log_path = storage_path('logs/parsers/' . $this->parser_name . '/' . $this->parser_name . '.log');
        config(['logging.channels.parser.path' => $this->log_path]);
        file_put_contents($this->log_path, "");
    }

    public function writeLog($message)
    {
        $this->line($message);
        Log::channel('parser')->info($message);
    }

    public function writeHtmlLogFile($file_name, $data)
    {
        file_put_contents(storage_path('logs/parsers/' . $this->parser_name . '/' . $file_name), $data);
    }

    public function getCookie()
    {
        return Storage::path('cookies/' . $this->parser_name . '.txt');
    }

    public function sendErrorNotification(string $errorMessage) : void {
        \Sentry\captureMessage($this->parser_name . ': ' . $errorMessage);
    }

}