<?php

use App\Data;
use App\Jarkom;
use App\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

function sendNotif($tinggi='',$peringatan='',$jam='',$tempat='')
{
    $auth   = 'key=AAAA-jQJgk8:APA91bGtRzyyKpumauSIbt3WjXvXIdv8DwUNhBpZZvnB-6Ls-APRikAAacH7SBkabwqpRSRp_iJv53sbG-Q2TixrCUQ6ZVkz4DLaUSx_sGQNEN34KnZWfa3Z9cenx-ZkClmfhaQMKuX3';
    $url    = 'https://fcm.googleapis.com/fcm/send';

    $data   = array(
        'notification' =>array(
            'title' => 'Peringatan Dini Banjir',
            'body' => "Peringatan : ".$peringatan."! \nStatus Pintu air ".$tempat." saat ini ".$peringatan.", ketinggian aktual pada pukul ".$jam." adalah ".$tinggi." m!\n
            pantau ketinggian pintu air ".$tempat." secara real-time pada aplikasi InaFEWS",
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ),
        'to' => '/topics/peringatan'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: '.$auth,
        "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
}

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/last', function (){
    // get data terbaru time
    date_default_timezone_set("Asia/Jakarta");
    $timeNow    = date('Y-m-d H:i:s');
    $parseNow   = date_parse($timeNow);
    $daynow     = $parseNow['day'];
    $jamnow     = $parseNow['hour'];
    $mntnow     = $parseNow['minute'];
    $totalnow   = $daynow * 24 * 60 + $jamnow * 60 + $mntnow;

    $dateNow    = date('Y-m-d');

    // mengambil data terakhir dari 
    $lastcr     = Data::max('created_at');
    $datadb     = date_create_from_format('Y-m-d H:i:s',$lastcr);
    $dbday      = date_format($datadb,'d');
    $dbjam      = date_format($datadb,'H');
    $dbmnt      = date_format($datadb,'i');
    $totaldb    = $dbday * 24 * 60 + $dbjam * 60 + $dbmnt; 

    $range      = $totalnow - $totaldb;

    $getdata    = DB::select('select ketinggian, status, tanggal, jam from data order by id desc limit 1');
    $datadb     = $getdata[0];
    
    $dateNow        = date('Y-m-d');

    $datamaxa        = Data::where('tanggal',$dateNow)
                            ->max('ketinggian');
    if($datamaxa == null){
        $datamaxb = "0";
    } else{
        $datamaxb = $datamaxa;
    }

    $datamina        = Data::where('tanggal',$dateNow)
                            ->min('ketinggian');
    if($datamina == null){
        $dataminb = "0";
    } else{
        $dataminb = $datamina;
    }
    
    $dataBanjir     = Data::where('tanggal',$dateNow)
                            ->where('status','=',1)
                            ->count();
        
    $dataAwas       = Data::where('tanggal',$dateNow)
                            ->where('status','=',2)
                            ->count();

    $dataWaspada    = Data::where('tanggal',$dateNow)
                            ->where('status','=',3)
                            ->count();

    if($range>=3){
        $jarkom = "Innactive";
        return response()->json(['site_status'=>$jarkom,
            'data'=>$datadb,
            'today'=>[
                'max'=>$datamaxb,
                'min'=>$dataminb,
                'banjir'=>$dataBanjir,
                'awas'=>$dataAwas,
                'waspada'=>$dataWaspada,
                ]]);
    } else {
        $jarkom = "Active";
        return response()->json(['site_status'=>$jarkom,
            'data'=>$datadb,
                'today'=>[
            'max'=>$datamaxb,
            'min'=>$dataminb,
            'banjir'=>$dataBanjir,
            'awas'=>$dataAwas,
            'waspada'=>$dataWaspada,
            ]]);
    }
    // return response()->json($lastdata);
});

$router->get('/today',function(){
    date_default_timezone_set("Asia/Jakarta");
    $dateNow        = date('Y-m-d');

    $datamax        = Data::where('tanggal',$dateNow)
                            ->max('ketinggian');

    $datamin        = Data::where('tanggal',$dateNow)
                            ->min('ketinggian');
    
    $dataBanjir     = Data::where('tanggal',$dateNow)
                            ->where('status','=',1)
                            ->count();
        
    $dataAwas       = Data::where('tanggal',$dateNow)
                            ->where('status','=',2)
                            ->count();

    $dataWaspada    = Data::where('tanggal',$dateNow)
                            ->where('status','=',3)
                            ->count();

    return response()->json([
        'max'       => $datamax,
        'min'       => $datamin,
        'banjir'    => $dataBanjir,
        'awas'      => $dataAwas,
        'waspada'   => $dataWaspada
    ]);
});

$router->get('/yester',function()
{
    $data_yester    = DB::select('select * from result_harian order by id desc limit 1');
    $datashow       = $data_yester[0];

    return response()->json($datashow);
});

$router->get('/fromAntares',function(){
    // KOMUNIKASI DARI ANTARES AJA
        // ambil data dari antares
        $request = Http::withHeaders([
            'X-M2M-Origin'   =>  'access-id:1e2d478edeb4f485:8a609ec56865b1dd',
            'Content-Type'   =>  'application/json;ty=4',
            'Accept'         =>  'application/json',
        ])->get('https://platform.antares.id:8443/~/antares-cse/antares-id/PDBanjir/pd0001/la');

        // mendecode json dan mengambil data con dan ct
        $res    = json_decode($request,true);
        $con    = $res['m2m:cin']['con']; // perlu di decode lagi 
        $ct     = $res['m2m:cin']['ct'];

        $datect = date_create_from_format('Ymd\THis',$ct);
        $ctAsli = date_format($datect,'Y-m-d H:i:s');
        $tglct  = date_format($datect,'Y-m-d');

        // mendecode data json dari con
        $conde  = json_decode($con,true);

        $tinggi = $conde['ketinggian'];
        $status = $conde['status'];

        $fromdb = Data::max('def_ct');

        // mengambil waktu terbaru dan mengambil data jam, menit, detik untuk jaringan
        date_default_timezone_set("Asia/Jakarta");
        $timeNow    = date('Y-m-d H:i:s');
        $tglNow     = date('Y-m-d');
        $wkt        = date('H:i');
        $tempat     = 'Katulampa';

        if($status == "1"){
            $peringatan = 'SIAGA I';
            //ambil data status = 2 dalam 5 data terakhir
            $banjir5    = Data::orderBy('id','desc')
                        ->limit(60)
                        ->where('status','=','1')
                        ->count();
            
            if ($banjir5 == 0 || $banjir5 == 60) {
                sendNotif($tinggi,$peringatan,$wkt,$tempat);
            }
        } else if($status == "2"){
            $peringatan = 'SIAGA II';
            //ambil data status = 3 dalam 5 data terakhir
            $banjir5    = Data::orderBy('id','desc')
                        ->limit(60)
                        ->where('status','=','2')
                        ->count();
            
            if ($banjir5 == 0 || $banjir5 == 60) {
                sendNotif($tinggi,$peringatan,$wkt,$tempat);
            }
        } else if($status == "3"){
            $peringatan = 'SIAGA III';
            //ambil data status = 1 dalam 5 data terakhir
            $banjir5    = Data::orderBy('id','desc')
                        ->limit(60)
                        ->where('status','=','3')
                        ->count();
            
            if ($banjir5 == 0|| $banjir5 == 60) {
                sendNotif($tinggi,$peringatan,$wkt,$tempat);
            }
        } else if($status == "4"){
            $peringatan = 'SIAGA IV';
            //ambil data status = 1 dalam 5 data terakhir
            $banjir5    = Data::orderBy('id','desc')
                        ->limit(60)
                        ->where('status','=','5')
                        ->count();
            
            if ($banjir5 == 0|| $banjir5 == 60) {
                sendNotif($tinggi,$peringatan,$wkt,$tempat);
            }
        }

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
            

            if ($jangka==3) {
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

        if ($wkt == '23:58') {
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

            $successRate    = $dataSuccess * 100 / (60*24-1);

            Result::create([
                'tanggal'       => $dateNow,
                'max'           => $datamax,
                'min'           => $datamin,
                'data_success'  => $dataSuccess,
                'success_rate'  => $successRate,
                'banjir'        => $dataBanjir,
                'awas'          => $dataAwas,
                'waspada'       => $dataWaspada,
                'data_failed'   => $dataFailed,
            ]);
        }

});
