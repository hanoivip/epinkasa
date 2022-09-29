<?php

namespace Hanoivip\Epinkasa;

use Hanoivip\Events\Gate\UserTopup;
use Hanoivip\Game\Recharge;
use Hanoivip\Payment\Facades\BalanceFacade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class EpinkasaModBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private $log;
    
    public function __construct($log)
    {
        $this->log = $log;
    }
    
    public function handle()
    {
        Redis::funnel('EpinkasaModBalance@' . $this->log->user_id)->limit(1)->then(function () {
            if (empty($this->log->recharge_status))
            {
                $recharge = Recharge::where('code', $this->log->package)->first();
                if (empty($recharge))
                {
                    Log::error("EpinkasaModBalance package not exist!");
                    return;
                }
                $result = BalanceFacade::add($this->log->user_id, $recharge->coin, "Epinkasa");
                if ($result === true)
                {
                    event(new UserTopup(
                        $this->log->user_id,
                        $recharge->coin_type,
                        $recharge->coin,
                        $this->log->mapping));
                    $this->log->recharge_status = 1;
                    $this->log->save();
                }
                else
                {
                    $this->release(60);
                }
            }
        }, function () {
            // Could not obtain lock...
            return $this->release(60);
        });
    }
}
