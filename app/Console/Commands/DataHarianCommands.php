<?php

namespace App\Console\Commands;

use App\Data;
use App\Jarkom;
use App\Result;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ResulltTodayCommands extends Command
{
    protected $signature    = "pdbanjir:result";

    protected $description  = "Result of daily Data, every 23.59";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // ambil data waktu dulu
        date_default_timezone_set("Asia/Jakarta");
        $dateNow    = date('Y-m-d');

        $datamax    = Data::where('tanggal',$dateNow)
                        ->max('ketinggian');

        $datamin    = Data::where('tanggal',$dateNow)
                        ->min('ketinggian');
        
        $dataFailed     = Jarkom::where('tanggal',$dateNow)
                            ->count();
        
        $dataSuccess    = Data::where('tanggal',$dateNow)
                            ->count();
        
        $dataBanjir     = Data::where('tanggal',$dateNow)
                            ->where('status','=',1)
                            ->count();
            
        $dataAwas       = Data::where('tanggal',$dateNow)
                            ->where('status','=',2)
                            ->count();

        $dataWaspada    = Data::where('tanggal',$dateNow)
                            ->where('status','=',3)
                            ->count();

        Result::create([
            'tanggal'       => $dateNow,
            'max'           => $datamax,
            'min'           => $datamin,
            'data_success'  => $dataSuccess,
            'banjir'        => $dataBanjir,
            'awas'          => $dataAwas,
            'waspada'       => $dataWaspada,
            'data_failed'   => $dataFailed,
        ]);
    }
}
