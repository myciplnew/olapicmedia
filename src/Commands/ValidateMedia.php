<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Myciplnew\Olapicmedia\Repositories\ValidateCronRepository;
use Illuminate\Support\Facades\Log;

class ValidateMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $validateCron;
    protected $signature = 'execute:ValidateMedia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate media execution';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ValidateCronRepository $validateCron)
    {
        parent::__construct();
        $this->validateCron = $validateCron;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $getDetails = $this->validateCron->getActiveExternalMedia();
        Log::info('Validate process started...');
        if(count($getDetails)>0)
        {
            foreach($getDetails as $row => $value)
            {
                Log::info('Processing media : '.$value['id']);
                $this->validateCron->validateMedia($value);
                Log::info('Completed media : '.$value['id']);
            }
        }
        Log::info('Validate process Completed');
        echo 'Validate media execution completed successfully';
    }
}
