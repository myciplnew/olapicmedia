<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Myciplnew\Olapicmedia\Repositories\CronRepository;
use Illuminate\Support\Facades\Log;

class CronMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $cronrep;
    protected $signature = 'execute:CronMedia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron media execution';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CronRepository $cronrep)
    {
        parent::__construct();
        $this->cronrep = $cronrep;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Upload process started...');
        $getDetails = $this->cronrep->getCronMedia();
        if(count($getDetails)>0)
        {
            foreach($getDetails as $row => $value)
            {
                Log::info('Processing media : '.$value['id']);
                $this->cronrep->processCronMedia($value);
                Log::info('Completed media : '.$value['id']);
            }
        }
        Log::info('Upload process completed.');
        echo 'Cron execution completed successfully';
    }
}
