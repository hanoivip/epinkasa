<?php

namespace Hanoivip\Epinkasa;

use Hanoivip\Game\Facades\GameHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Hanoivip\Events\Game\UserBuyItem;

class EpinkasaSendItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private $log;
    
    public function __construct($log)
    {
        $this->log = $log;
    }
    
    public function handle()
    {
        Redis::funnel('EpinkasaSendItem@' . $this->log->user_id)->limit(1)->then(function () {
            if (empty($this->log->recharge_status))
            {
                $result = GameHelper::recharge($this->log->user_id,
                    $this->log->server,
                    $this->log->package,
                    $this->log->role_id);
                if ($result === true)
                {
                    event(new UserBuyItem(
                        $this->log->user_id,
                        $this->log->server,
                        $this->log->package,
                        $this->log->role_id));
                    $this->log->recharge_status = 1;
                    $this->log->save();
                }
                else
                {
                    $this->log->recharge_status = 2;
                    $this->log->save();
                    $this->release(60);
                }
            }
        }, function () {
            // Could not obtain lock...
            return $this->release(60);
        });
    }
}
