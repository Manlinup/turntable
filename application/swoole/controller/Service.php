<?php
namespace app\swoole\controller;
use think\db;

class Service {

    protected $serv;
    protected $redis_service = '0.0.0.0';
    protected $redis_port = '9501';
    protected $name = 'turntable_game';
    protected $turntable_log = 'turntable_game_log';
    protected $turntable_prize = 'turntable_game_prize';

    public function __construct()
    {
        $this->serv = new \swoole_websocket_server($this->redis_service,$this->redis_port,SWOOLE_BASE);
        $this->serv->set([
            'worker_numer' => 1,
            'heartbeat_check_interval' => 10,
            'heartbeat_idle_time' => 20,
            'task_worker_num' => 1,
        ]);
        $this->serv->on('Open',[$this,'onOpen']);
        $this->serv->on('Message',[$this,'onMessage']);
        $this->serv->on('Task',[$this,'onTask']);
        $this->serv->on('Finish',[$this,'onFinish']);
        $this->serv->on('Close',[$this,'onClose']);
        $this->serv->start();
    }

    public function onOpen($serv,$request) {
        $serv->push($request->fd,json_encode('我们可以建立连接了，开始玩吧'));
    }

    public function onTask($serv,$task_id,$from_id,$data) {
        $serv->finish($data);
    }

    public function onFinish($serv,$task_id,$data) {
        $serv->push($serv->fd,$data);
        echo $data;
    }

    public function onClose($serv,$fd) {
        echo "connect close,id:{$fd}";
    }

    public function onMessage(\swoole_websocket_server $serv,\swoole_websocket_frame $frame)
    {

        var_dump($frame->fd);
        var_dump($frame->data);

        $data = json_decode($frame->data, true);
        if (empty($data['turntable_id']) || empty($data['shop_id']) || empty($data['user_id'])) {
            $serv->push($frame -> fd, json_encode('参数不完整'));
        }
        $shopId = $data['shop_id'];
        $turntableId = $data['turntable_id'];
        $userId = $data['user_id'];

        $game = $this->get_turntable_list($shopId,$turntableId);

        if (empty($game)) {
            $serv->push($frame->fd, json_encode('该转盘不存在'));
        }
        //检验活动有效期
        if ($game['start_date'] > time()) {
            $serv->push($frame->fd, json_encode('活动还没开始'));//活动还没开始
        }
        if ($game['end_date'] < time()) {
            $serv->push($frame->fd, json_encode('活动已经结束'));//活动已经结束
        }

        if ($game['frequncy'] == 1) {//校验当天的抽奖情况
            $userLogs = Db::name($this->turntable_log)
                ->field('a.*,b.prize_name,b.img_url,b.id as p_id')
                ->alias('a')
                ->join($this->turntable_prize . ' b','a.prize_id = b.id')
                ->where('a.turntable_id',$turntableId)
                ->where('a.userid',$userId)
                ->where('a.created','>=',strtotime(date("Y-m-d ")))
                ->where('a.created','<',strtotime('tomorrow'))
                ->order('a.id asc')
                ->select();
        } elseif ($game['frequncy'] == 2) {//校验活动期内的抽奖情况
            $userLogs = Db::name($this->turntable_log)
                ->field('a.*,b.prize_name,b.img_url,b.id as p_id')
                ->alias('a')
                ->join($this->turntable_prize . ' b','a.prize_id = b.id')
                ->where('a.turntable_id',$turntableId)
                ->where('a.userid',$userId)
                ->where('a.created','>=',$game['start_date'])
                ->where('a.created','<',$game['end_date'])
                ->order('a.id asc')
                ->select();
        }
        $resultPlay = $this->check_user_times($turntableId,$userId,$game['num_by_one'],$game['start_date'],$game['end_date'],$game['frequncy']);
        foreach ($userLogs as $k => $v) {
            if ($v['type'] == 1) {
                $resultPlay['status'] = 2;
                $resultPlay['id'] = $v['p_id'];
                $serv->push($frame->fd, json_encode($resultPlay));//返回结果
            }
        }
        if (count($userLogs) >= $game['num_by_one']) {
            $serv->push($frame->fd, json_encode('已经抽满次数'));//已经抽满次数
        } else {
            //随机抽
            $lottryArr = [];
            $prizeArr = [];
            foreach ($game['list'] as $k => $v) {
                if ($v['num'] > 0) { //奖品数量大于0
                    $lottryArr[$v['id']] = $v['probability'];
                    $prizeArr[$v['id']] = $v['prize_name'];
                    $prizeImgArr[$v['id']] = $v['img_url'];
                }
            }
            if (empty($lottryArr)) {
                $serv->push($frame->fd, json_encode('没有奖品了')); //没有奖品了
            }
            $prizeId = $this->get_rand($lottryArr);
            $prizeName = $prizeArr[$prizeId];

            $type = 0;
            foreach ($game['list'] as $k => $v) {
                if ($v['id'] == $prizeId) {
                    $type = $v['type'];
                    break;
                }
            }

            if ($prizeName) {
                // 启动事务
                Db::startTrans();
                try{

                    $data = [
                        'prize_id' => $prizeId,
                        'userid' => $userId,
                        'turntable_id' => $turntableId,
                        'shop_id' => $shopId,
                        'type' => $type,
                        'status' => 1,
                        'created' => time()
                    ];
                    Db::name($this->turntable_log)->insert($data);

                    //奖品数量相应减1
                    Db::name($this->turntable_prize)->where('id', $prizeId)->setDec('num');
                    $resultPlay['status'] = 3;
                    $resultPlay['id'] = $prizeId;
                    // 提交事务
                    Db::commit();
                    $serv->push($frame->fd, json_encode($resultPlay));
                    return $resultPlay;
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $serv->push($frame->fd, json_encode('系统繁忙'));//系统繁忙
                }
            }
        }
    }


    public function get_turntable_list($shopId, $turntableId) {
        $game = Db::name($this->name)
            ->where('shop_id',$shopId)
            ->where('turntable_id',$turntableId)
            ->where('status','1')
            ->find();

        $prize_list = Db::name($this->turntable_prize)
            ->where('turntable_id',$turntableId)
            ->where('status','1')
            ->order('listorder desc')
            ->select();

        if (empty($prize_list)) {
            return false;
        }

        foreach ($prize_list as $k => $v) {
            if ($game['turntable_id'] == $v['turntable_id']) {
                $game['list'][] = $v;
            }
        }
        $game['created'] = date("Y-m-d H:i:s",$game['created']);


        if (!empty($game)) {
            return $game;
        }
        return false;
    }

    /**
     * 用户的抽奖次数
     * @param  $turntableId
     * @param  $userId
     * @param  $times 规定的单人抽奖次数
     * @return array status 0 没有用户id，1没有中奖，2已经中奖了
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_user_times($turntableId,$userId,$times,$startTime,$endTime,$frequncy) {
        $data = array(
            'status' => 0,
            'prize_name' => '',
            'times' => $times
        );
        if (empty($userId)) {
            return $data;
        }
        if ($frequncy == 1) { //每天抽奖次数
            $userLogs = Db::name($this->turntable_log)
                ->where('turntable_id',$turntableId)
                ->where('userid',$userId)
                ->where('created','>=',strtotime(date("Y-m-d ")))
                ->where('created','<',strtotime('tomorrow'))
                ->order('id desc')
                ->select();
        } elseif ($frequncy == 2) { //活动期内抽奖次数
            $userLogs = Db::name($this->turntable_log)
                ->where('turntable_id',$turntableId)
                ->where('userid',$userId)
                ->where('created','>=',$startTime)
                ->where('created','<',$endTime)
                ->order('id desc')
                ->select();
        }


        $isWin = 0;
        $prizeId = '';
        foreach ($userLogs as $k => $v) {
            if ($v['type'] == 1) {
                if ($v['status'] == 0) {
                    continue;
                }
                $isWin ++;
                $prizeId = $v['prize_id'];
                break;
            }
        }

        if ($isWin > 0) { //当天已经中过奖了
            $prize = Db::name($this->turntable_prize)
                ->field('prize_name,img_url')
                ->where('id',$prizeId)
                ->find();
            $data['status'] = 2;
            $data['prize_name'] = $prize['prize_name'];
            $data['img_url'] = $prize['img_url'];
            $data['times'] = 0;
        } else { //中的都是谢谢惠顾或者没抽
            if (empty($userLogs)) {
                $data['status'] = 1;
                $data['prize_name'] = '';
                $data['img_url'] = '';
                $data['times'] = $times - count($userLogs);
                return $data;
            }
            $userLogs = array_reverse($userLogs);
            $prize = Db::name($this->turntable_prize)
                ->where('id',$userLogs[0]['prize_id'])
                ->find();
            $data['status'] = 1;
            $data['prize_name'] = $prize['prize_name'];
            $data['img_url'] = $prize['img_url'];
            $data['times'] = $times - count($userLogs);
        }

        return $data;
    }

    /**
     * 抽奖概率算法
     * @param $proArr
     * @return int|string
     */
    private function get_rand($proArr) {
        $result = '';

        //概率数组的总概率精度
        $proSum = array_sum($proArr);

        //概率数组循环
        foreach ($proArr as $k => $proCur) {
            $randNum = mt_rand(1,$proSum);
            if ($randNum <= $proCur) {
                $result = $k;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }
}