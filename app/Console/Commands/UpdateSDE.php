<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class UpdateSDE extends Command
{
    const SDE_URLS = [
        "https://www.fuzzwork.co.uk/dump/latest/invTypes.sql.bz2",
        "https://www.fuzzwork.co.uk/dump/latest/invGroups.sql.bz2",
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:sde';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the SDE';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $opts = [
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:90.0) Gecko/20100101 Firefox/90.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        foreach (self::SDE_URLS as $url) {
            $compressed_sde = file_get_contents($url, false, $context);
            $sde = bzdecompress($compressed_sde);
            DB::unprepared($sde);
        }

        echo "SDE update successful\n";
        return 0;
    }
}
