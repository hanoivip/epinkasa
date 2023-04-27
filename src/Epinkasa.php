<?php

namespace Hanoivip\Epinkasa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mervick\CurlHelper;
use Exception;
use Hanoivip\Epinkasa\EpinkasaLog;

trait Epinkasa
{   
    /**
     * Phải chọn được nhân vật trước
     * @deprecated
     */
    public function startGameFlow1(Request $request)
    {
        return redirect()->route('wizard.role', ['next' => 'epinkasa.game.do']);
    }
    public function startGameFlow(Request $request)
    {
        return view('hanoivip::epinkasa');
    }
    /**
     * Vào nạp ngay, không cần chọn nhân vật
     */
    public function startWebFlow(Request $request)
    {
        return redirect()->route('epinkasa.web.do');
    }
    
    public function doGameFlow(Request $request)
    {
        $sv=$request->get('svname');
        $role=$request->get('role');
        $username=$this->getUsername();//name,email,ID
        $userId=Auth::user()->id;
        $apiKey=config('epinkasa.key');
        $apiSecret=config('epinkasa.secret');
        $reportEmail = $this->getReportEmail();
        $params=[
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'username'   => $username,
            'user_id'	 => $role,
            'user_mail'  => $reportEmail,
            'user_phone'	 => ""
        ];
        $message = 'Please try again and contact customer support!';
        try {
            $url = "https://www.epinkasa.com/dealer/api/create";
            $response = CurlHelper::factory($url)->setPostParams($params)->exec();
            if ($response['status'] == 200 && !empty($response['data']))
            {
                $key="ITEMTR_" . $username . "_" . $role;
                Cache::put($key, ['sv'=>$sv, 'uid'=>$userId], 86400);
                $message = $response['data']['message'];
                if ($request->expectsJson())
                {
                    return ['error' => 0, 'message' => 'success', 'data' => ['url' => $message]];
                }
                else 
                {
                    return redirect()->away($message);
                }
            }
            else 
            {
                Log::error("Request epinkasa fail: " . $response['content']);
            }
        } catch (Exception $ex) {
            Log::error("Epinkasa exception: " . $ex->getMessage());
        }
        return view('hanoivip::epinkasa-failure', ['error' => $message ]);
    }
    
    public function doWebFlow(Request $request)
    {
        $username=$this->getUsername();
        if (empty($username))
            abort(500, 'Usename must be defined!');
        $userId=Auth::user()->id;
        $apiKey=config('epinkasa.key');
        $apiSecret=config('epinkasa.secret');
        $reportEmail=$this->getReportEmail();
        $params=[
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'username'   => $username,
            'user_id'	 => $userId,
            'user_mail'  => $reportEmail,
            'user_phone'	 => ""
        ];
        try {
            $url = "https://www.epinkasa.com/dealer/api/create";
            $response = CurlHelper::factory($url)->setPostParams($params)->exec();
            $message = 'Please try again and contact customer support!';
            if ($response['status'] == 200 && !empty($response['data']))
            {
                $key="ITEMTR_" . $username . "_" . $userId;
                Cache::put($key, ['sv'=>'web','uid'=>$userId], 86400);
                $message = $response['data']['message'];
                return redirect()->away($message);
            }
        } catch (Exception $ex) {
            Log::error("Epinkasa exception: " . $ex->getMessage());
        }
        return view('hanoivip::epinkasa-failure', ['message' => $message ]);
    }
    /*
     (
     [siparisID] => 152
     [userEmail] => game.oh.vn@gmail.com
     [userID] => 280527
     [userName] => hasim123
     [returnData] => 750
     [status] => 1
     [odemeKanali] => kart_odeme
     [odemeTutari] => 1.00
     [netKazanc] => 0.95
     [hash] => Gqpe3MGCkpDzH1c96W2XCsROj98lcD9h60GJBUdB4Es=
     )
     */
    public function callback(Request $request)
    {
        //Log::debug("Epinkasa callback data: " . print_r($request->all(), true));
        $SiparisID   = $request->input("siparisID");//mapping id
        $UserEmail   = $request->input("userEmail");
        $roleId      = $request->input("userID");//~role id
        $userName    = $request->input("userName");
        $ReturnData  = $request->input("returnData");
        $Status      = $request->input("status");
        $OdemeKanali = $request->input("odemeKanali");
        $OdemeTutari = $request->input("odemeTutari");
        $NetKazanc   = $request->input("netKazanc");
        $Hash = $request->input("hash");
        $apiKey=config('epinkasa.key');
        $apiSecret=config('epinkasa.secret');
        
        $key='ITEMTR_' . $userName . '_' . $roleId;
        if (!Cache::has($key))
        {
            Log::debug("Epinkasa callback itemout: " . $SiparisID);
            return response("NOK1");
        }
        
        $hashKontrol = base64_encode(hash_hmac('sha256',$SiparisID."|".$roleId."|".$ReturnData."|".$Status."|".$OdemeKanali."|".$OdemeTutari."|".$NetKazanc."|".$apiKey, $apiSecret, true));
        if ($hashKontrol != $Hash)
        {
            //Log::debug("NOK2");
            return response("NOK2");
        }
        
        // save log
        $obj = Cache::get($key);
        $serverName=$obj['sv'];
        $userId=$obj['uid'];
        $log = new EpinkasaLog();
        $log->user_id = $userId;
        $log->server = $serverName;
        $log->role_id = $roleId;
        $log->mapping = $SiparisID;
        $log->status = $Status;
        $log->recharge_status = 0;
        $log->package = $ReturnData;
        $log->save();
        
        if ($Status == 1)
        {
            $this->onPaymentSuccess($log);
        }
        // cleanup
        Cache::forget($key);
        return response("OK");
    }
}