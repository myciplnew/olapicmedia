<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Myciplnew\Olapicmedia\Repositories\StreamRepository;
use Illuminate\Support\Facades\Log;

class StreamMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $streamrep;
    protected $signature = 'execute:StreamMedia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stream media execution';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(StreamRepository $streamrep)
    {
        parent::__construct();
        $this->streamrep = $streamrep;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->streamrep->executeStream();
        echo 'Stream media execution completed successfully';
    }
}
