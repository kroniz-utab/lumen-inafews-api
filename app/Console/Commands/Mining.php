<?php

namespace App\Console\Commands;

use App\Data;
use App\Jarkom;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class MiningCommands extends Command
{
    protected $signature    = "pdbanjir:data";

    protected $description  = "Get Latest Data from antares and post it to own db";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // KOMUNIKASI DARI ANTARES AJA
        // ambil data dari antares
        $request = Http::withHeaders([
            'X-M2M-Origin'   =>  'access-id:1e2d478edeb4f485:8a609ec56865b1dd',
            'Content-Type'   =>  'application/json;ty=4',
            'Accept'         =>  'application/json',
        ])->get('https://platform.antares.id:8443/~/antares-cse/antares-id/PDBanjir/sensor01/la');

        // mendecode json dan mengambil data con dan ct
        $res    = json_decode($request,true);
        $con    = $res['m2m:cin']['con']; // perlu di decode lagi 
        $ct     = $res['m2m:cin']['ct'];

        $datect = date_create_from_format('Ymd\THis',$ct);
        $ctAsli = date_format($datect,'Y-m-d H:i:s');
        $tglct  = date_format($datect,'Y-m-d');
        $wkt    = date_format($datect,'H:i');

        // mendecode data json dari con
        $conde  = json_decode($con,true);

        $tinggi = $conde['ketinggian'];
        $status = $conde['status'];

        $fromdb  = DB::select('select def_ct from data order by created_at desc limit 1');
        // $fromdb = Data::max('def_ct');

        // mengambil waktu terbaru dan mengambil data jam, menit, detik untuk jaringan
        date_default_timezone_set("Asia/Jakarta");
        $timeNow    = date('Y-m-d H:i:s');
        $tglNow     = date('Y-m-d');

        if ($ct == $fromdb) {
            // deklarasi jam, menit, dan second dari ct
            $jamct  = date_format($datect,'H');
            $mntct  = date_format($datect,'i');
            $ctTot  = $jamct * 60 + $mntct;

            $parseNow   = date_parse($timeNow);
            $jamnow     = $parseNow['hour'];
            $mntnow     = $parseNow['minute'];
            $nowTot     = $jamnow * 60 + $mntnow;

            // selang waktu antara jam saat ini dengan waktu pada ct
            $jangka     = $nowTot - $ctTot;
            echo($jangka);

            if ($jangka>2 && $jangka<4) {
                // sebelumnya ambil data created at terakhir dari db
                Jarkom::create([
                    'last_ct'   => $ctAsli,
                    'tanggal'   => $tglNow,
                ]);
            }
        } else {
            // memasukkan data ke dalam DB Data
            Data::create([
                'ketinggian'    => $tinggi,
                'status'        => $status,
                'def_ct'        => $ct,
                'tanggal'       => $tglct,
                'jam'           => $wkt,
            ]);
        }

        if ($wkt == '23:59') {
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
}
