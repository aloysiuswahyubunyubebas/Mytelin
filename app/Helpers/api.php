<?php

use App\Jobs\SendEmailOtp;
use App\Models\OtpLog;
use App\Models\User;
use App\Models\Media;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client as RestClient;
use Twilio\Exceptions\TwilioException;

function apiGet($url)
{
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        'defaults' => [
            'exceptions' => false
        ]
    ]);
    $response = $client->request('GET', $url);

    return json_decode($response->getBody()->getContents());
}
//upload Aws
function uploadAws($file ,$folder='user', $user= null)
{
    try {
        $fileName = $file->getClientOriginalName();
        $extinsion = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        $media = new Media();
        if($user!=''){
            $media->user_id = $user->id;
            $mitra = $user->mitras->first();
            $media->mitra_id = $mitra->id ?? null;
        }
        $media->extension = $extinsion;
        $media->folder    = $folder;
        $media->name      = $fileName ;
        $media->size      = $fileSize;
        $media->filename  = $fileName;
        $media->save();
        
        $filePath = 'uploads/'.$folder.'/' . $media->slug;

 
        $path = Storage::disk('s3')->put($filePath, file_get_contents($file));
        $path = Storage::disk('s3')->url('0');
        // $url = Storage::disk('s3')->url($filePath);
        
        Cache::tags(['medias'])->flush();
        $media->path      = $filePath;
        $media->url       = str_replace('0','',$path);
        $media->update();
       
       

        $return['status'] = true;
        $return['data'] =  $media;
        $return['message'] = 'succses';
        return $return;
    } catch (S3Exception $e) {
        
        $return['status'] = false;
        $return['data'] =  [];
        $return['message'] = $e->getMessage();
        return $return;
    }
   
}
//send sms by Twilio
function sendSMSTwilio($message, $recipients)
{
    try {
        $message       = stripslashes(utf8_encode($message));
        $account_sid   = env('TWILIO_SID');
        $auth_token    = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_NUMBER');
        $phone_number  = '';
        //fillter / replace nomor hp
        $nohp          = str_replace(" ","",$recipients);
        $nohp          = str_replace("(","",$nohp);
        $nohp          = str_replace(")","",$nohp);
        $nohp          = str_replace(".","",$nohp);

        if(!preg_match('/[^+0-9]/',trim($nohp))){
            // cek apakah no hp karakter 1-3 adalah +62
            if(substr(trim($nohp), 0, 3)=='+62'){
                    $phone_number = trim($nohp);
                }
                // cek apakah no hp karakter 1 adalah 0
            elseif(substr(trim($nohp), 0, 1)=='0'){
                    $phone_number = '+62'.substr(trim($nohp), 1);
            }
        }

        $client = new RestClient($account_sid, $auth_token);
        $client->messages->create($phone_number, 
                [
                    'from' => $twilio_number, 
                    'body' => $message
                ]
        );
        
        $return['status'] = true;
        $return['message'] = 'succses';
        return $return;
    } catch (TwilioException $e) {
        $return['status'] = false;
        $return['message'] = $e->getMessage();
        return $return;
    }
   
}
function sendOTPTwilio($message, $recipients)
{
    try {
        $message       = stripslashes(utf8_encode($message));
        $account_sid   = env('TWILIO_SID');
        $auth_token    = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_NUMBER');
        $verify_sid    = env('TWILIO_VERIFY_SID');
        $phone_number  = '';
        //fillter / replace nomor hp
        $nohp          = str_replace(" ","",$recipients);
        $nohp          = str_replace("(","",$nohp);
        $nohp          = str_replace(")","",$nohp);
        $nohp          = str_replace(".","",$nohp);

        if(!preg_match('/[^+0-9]/',trim($nohp))){
            // cek apakah no hp karakter 1-3 adalah +62
            if(substr(trim($nohp), 0, 3)=='+62'){
                    $phone_number = trim($nohp);
                }
                // cek apakah no hp karakter 1 adalah 0
            elseif(substr(trim($nohp), 0, 1)=='0'){
                    $phone_number = '+62'.substr(trim($nohp), 1);
            }
        }

        $client = new RestClient($account_sid, $auth_token);
        
        $client = $client->verify->v2->services($verify_sid)
        ->verifications
        ->create($phone_number, "sms");
        $return['status'] = true;
        $return['message'] = 'succses';
        return $return;
    } catch (TwilioException $e) {
        $return['status'] = false;
        $return['message'] = $e->getMessage();
        return $return;
    }
   
}
function verifyOTPTwilio($message, $recipients, $otp)
{
    try {
        $message       = stripslashes(utf8_encode($message));
        $account_sid   = env('TWILIO_SID');
        $auth_token    = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_NUMBER');
        $verify_sid    = env('TWILIO_VERIFY_SID');
        $phone_number  = '';
        //fillter / replace nomor hp
        $nohp          = str_replace(" ","",$recipients);
        $nohp          = str_replace("(","",$nohp);
        $nohp          = str_replace(")","",$nohp);
        $nohp          = str_replace(".","",$nohp);

        if(!preg_match('/[^+0-9]/',trim($nohp))){
            // cek apakah no hp karakter 1-3 adalah +62
            if(substr(trim($nohp), 0, 3)=='+62'){
                    $phone_number = trim($nohp);
                }
                // cek apakah no hp karakter 1 adalah 0
            elseif(substr(trim($nohp), 0, 1)=='0'){
                    $phone_number = '+62'.substr(trim($nohp), 1);
            }
        }

        $twilio = new RestClient($account_sid, $auth_token);
        
        $verification = $twilio->verify->v2->services($verify_sid)
            ->verificationChecks
            ->create($otp, array('to' =>  $phone_number));

        if ($verification->valid) {
            $return['status'] = true;
            $return['message'] = 'succses';
        }else{
            $return['status'] = false;
            $return['message'] = 'otp Not Valid';
        }

        
        return $return;
    } catch (TwilioException $e) {
        $return['status'] = false;
        $return['message'] = $e->getMessage();
        return $return;
    }
   
}
function sendSMS($message, $reciver)
{
    $message = urlencode(stripslashes(utf8_encode($message)));
    $endpoint = env('SMS_ENDPOINT');
    $username = env('SMS_USERNAME');
    $key = env('SMS_KEY');
    $url = "{$endpoint}?username={$username}&key={$key}&number={$reciver}&message={$message}";
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        'defaults' => [
            'exceptions' => false
        ]
    ]);
    $response = $client->request('GET', $url);
    return $response->getBody()->getContents();
}


function sendSMSOtp($message, $reciver)
{
    $message = urlencode(stripslashes(utf8_encode($message)));
    $endpoint = env('SMS_OTP_ENDPOINT');
    $key = env('SMS_KEY');
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $endpoint,[
        'verify' => false,
        'defaults' => [
            'exceptions' => false
        ],
        'headers' => [
            'Accept'     => 'application/json',
        ],
        'body' => json_encode([
            'apikey' => $key,  
            'callbackurl' => '', 
            'datapacket'=>[
                ['number' => trim($reciver),
                'message' => $message]
            ]
        ])
    ]);
    return $response->getBody()->getContents();
}

function createOtpCode($phone,$user = null){

    $start = Carbon::now()->startOfDay();
    $end = Carbon::now()->endOfDay();
    $ip = get_client_ip();
    $total =  OtpLog::where('phone', $phone)->orWhere('ip', $ip)->whereBetween('created_at', [$start, $end])->count();
    if ($total >=  env('OTP_LIMIT',5)) {
        $return['errors']  = ['phone' => ['Anda sudah mencoba 5 kali hari ini.']];
        $return['code']  = 422;
        $return['data']  = null;
        return $return;
    }
    try{
        $minute = env('EXPIRED_OTP_PHONE',10);
        $num_str = random_int(100000, 999999);
        $otp = new OtpLog;
        $otp->user_id = $user->id ?? null;
        $otp->number = (int)$num_str;
        $otp->email = $user->email ?? null;
        $otp->phone = $phone;
        $otp->expired_at = Carbon::now()->addMinutes($minute);
        $otp->ip = $ip;
        $otp->actived_at = null;
        $otp->save();
        // SendEmailOtp::dispatch($otp)->delay(now()->addSeconds(5));
        $message = 'Kode verifikasi bersifat RAHASIA. Jangan bagikan ke siapa pun. Gunakan kode ' . $otp->number . ' untuk konfirmasi. Berlaku hingga '.$minute.' menit. ';
    
        $res = sendSMSTwilio($message, $phone);
        $otp->status = $res;
        $otp->update();
        if($res['status']){
            $return['code']  = 200;
            $return['data']  = $otp;
            return $return;
        }
        

        $return['errors']  = ['phone' => ['SMS OTP Gagal terkirim']];
        $return['code']  = 422;
        $return['data']  = null;
        return $return;

      } catch (\Throwable $th) {
        $return['errors']  = ['phone' => ['SMS OTP Gagal terkirim']];
        $return['code']  = 422;
        $return['data']  = $th->getMessage();
        return $return;
    }

}


function verificationOtp($request){

    $user = User::where('phone',$request->phone)->where('phone_code',$request->phone_code)->first();
    if(!$user){
       $return['errors']  = ['phone' => ['No.Handphone belum terdaftar']];
       $return['code']    = 422;
       return $return;
    }
    $phone = $request->phone_code.$request->phone;
    $otp = OtpLog::latest('created_at')->where('phone', $phone)->whereNull('actived_at')->first();
    $error['buttons'] = [
        [
         'text'=>  'Kirim ulang kode OTP',
         'bg_color'=>'#575FCC',
         'color'=>  '#FFFFFF',
         'action'=> '/',
         'url'=> env('API_URL').'/register/otp',
        ],
     ];
    if ($otp) {
        if (Carbon::now() > $otp->expired_at) {
            $error['content'] = [
               'icon'  => env('ASSET_URL').'/images/icon-error.png',
               'title'=> 'Kode OTP Expired',
               'summary' => 'Kode OTP yang kamu masukan sudah expired.'
           ];
           $return['errors']  = ['otp' => ['Kode OTP Expired']];
           $return['success']  = false;
           $return['data']    = $error;
           $return['code']    = 422;
           return $return;
        }
        if ($otp->number != $request->otp_code) {
            $error['content'] = [
               'icon'  => env('ASSET_URL').'/images/icon-error.png',
               'title'=> 'Kode OTP Tidak Sesuai',
               'summary' => 'Kode OTP yang kamu masukan tidak sesuai.'
           ];
           $return['errors']  = ['otp' => ['Kode OTP Tidak Sesuai']];
           $return['success']  = false;
           $return['data']    = $error;
           $return['code']    = 422;
           return $return;
        }
        $data = [
            'content'=> [
                'icon'  => env('ASSET_URL').'/images/logo.png',
                'title'=> 'Verifikasi berhasil',
                'summary' => 'Verifikasi akun kamu berhasil.'
            ],
            'buttons'=>[
               [
                'text'=>  'Kembali ke halaman awal',
                'bg_color'=>'#575FCC',
                'color'=>  '#FFFFFF',
                'action'=> '/',
                'url'=> '/',
               ],
               [
                 'text'=>'Buka Aplikasi WhatsApp untuk chat dengan CS Jalin',
                 'bg_color'=>'#F06400',
                 'color'=>  '#FFFFFF',
                 'action'=> '08114519459',
                 'url'=> '#',
               ]
            ],
         ];
        $return['success']  = true;
        $return['data']    = $data;
        $return['otp']    = $otp;
        $return['code']    = 200;
        return $return;
    } else {
         $error['content'] = [
            'icon'  => env('ASSET_URL').'/images/icon-error.png',
            'title'=> 'Kode OTP tidak ditemukan',
            'summary' => 'Kode OTP yang kamu masukan tidak ditemukan.'
        ];
        $return['errors']  = ['otp' => ['Kode OTP tidak ditemukan']];
        $return['success']  = false;
        $return['data']    = $error;
        $return['code']    = 422;
        return $return;
    }

}


function send_notification($title, $body, $nameEvent ,$type, $user, $sender )
{
    $images = json_decode($sender->mitras[0]->image);
    $fields = [
            'to'  => $user->device_token,
            'notification' => [
                'title' => $title,
                'email' => $sender->email,
                'name' => $sender->mitras[0]->name,
                'image'=> $images->url,
                'body' => $body,
                'type'=> $type,
                'nameEvent' => $nameEvent
            ],
            'data' => [
                'title' => $title,
                'email' => $sender->email,
                'name' => $sender->mitras[0]->name,
                'image'=> $images->url,
                'body' => $body,
                'type'=> $type,
                'nameEvent' => $nameEvent
            ],
    ];
    
    $headers = [
            'Authorization: key='.env('FIREBASE_KEY'),
            'Content-Type: application/json'
    ];
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, true );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields) );
    $send = curl_exec( $ch );
    curl_close( $ch );

    return json_decode($send, true);
}

function push_notification($title, $body , $deviceToken ,$imagesUrl)
{
    $fields = [
            'to'  => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => [
                'title' => $title,
                'body'  => $body
            ],
    ];
    if ($imagesUrl!='') {
            $fields = [
                'to'  => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'images' => $imagesUrl
                ],
                'data' => [
                    'title' => $title,
                    'body'  => $body,
                    'images' => $imagesUrl
                ],
            ];
    }
    $headers = [
            'Authorization: key='.env('FIREBASE_KEY'),
            'Content-Type: application/json'
    ];
		
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, true );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields) );
    $send = curl_exec( $ch );
    curl_close( $ch );

    return json_decode($send, true);
}
