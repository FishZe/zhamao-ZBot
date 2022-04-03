<?php

namespace Module\Bili;

use ZM\API\CQ;
use ZM\API\OneBotV11;
use ZM\Store\LightCache;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Swoole\OnTick;

/**
 * Class Push
 * @package Module\Bili
 * @since 2.0
 */
class Push{
    
    /**
     * @CQCommand("test")
     */
     public function test1(){
         return CQ::image("http://zbot_toolkit:20003/bilicard?id=644634235699724296");
     }

    /**
     * 格式化时间
     * Arg: $t(int) -> 间隔时间
     * Author: FishZe
     * Data: 2022/03/22 22:00
     */
    public function getFormatTime($t){
        $msg = "";
        $r = [[86400, "天"], [3600, "小时"], [60, "分钟"], [0, "秒"]];
        foreach ($r as $i){
            $msg = $t >= $i[0] ? $msg.intval($t / ($i[0] == 0 ? 1 : $i[0])).$i[1] : $msg;
            $t %= ($i[0] == 0 ? 1 : $i[0]);
        }
        return $msg;
    }
    
    /**
     * 推送消息
     * Args: $mid(int) -> Up主Mid  $msg(string) -> 消息内容  $msgType(int) -> 消息类型(详见下文)  
     *       $at(bool) -> 是否at全体(仅群组)  $type(string) -> 推送类型(all || private || group)
     *       $msgType: -1为直播相关提醒  -2为直播弹幕相关统计  为正值时是动态推送，值为该类型对应值的二进制左移值
     * Author: FishZe
     * data: 2022/03/15 23:23
     */
    public function pushMsg($mid, $msg, $msgType = -1, $at = false, $type = "all"){
        if(!in_array($type, array("all", "private", "group"))){
            return false;
        }
        $msgType =  $msgType >= (1 << 10) ? (1 <<10) : $msgType;
        $sql = new Sql;
        $pushList = $sql -> getUpPushByType($mid, $type);
        foreach ($pushList as $i){
            if(($msgType == -2 && $i['danmu']) || ($msgType == -1 && $i['live_push']) || ($msgType >= 0 && ($msgType & $i['dynamic_type']))){
                $bot = OneBotV11::get($i['bot_id']);
                if($i['qq_type'] == 'private') {
                    $bot -> sendPrivateMsg($i['qq_id'], $msg);
                }
                else if($i['qq_type'] == 'group') {
                    $sendMsg = $at ? CQ::at("all").$msg : $msg;
                    $bot -> sendGroupMsg($i['qq_id'], $sendMsg);
                }
            }
        }
    }

    /**
     * @OnTick(1000)
     * 推送直播提醒
     */
    public function liveNotice() {
        $sql = new Sql;
        $api = new Api;
        $upMids = $sql -> getAllUpsMids();
        $upInfo = $sql -> getAllUpsByMid();
        $liveStatus = $api ->  getLiveRoomsStatus($upMids);
        if($liveStatus == NULL){
            return;
        }
        
        foreach($upMids as $up){
            
            if(!in_array($up, array_keys($liveStatus))){
                continue;
            }
            $state = $liveStatus[$up];
            $info = $upInfo[$up];

            if($state['live_status'] == 1 && !$info['live_status']){
                $liveInterval = $sql -> getLiveStopInterval($up);
                $sql -> startLive($up);
                if($info['danmu_record']){ 
                    $api -> startDanmu($info['room_id']); 
                }
                $msg = "{$info['name']}直播了！\n 【{$state['title']}】\n".CQ::image($state['cover_from_user'])."\n传送门 →https://live.bilibili.com/".$state['room_id']."\n"."距离上次直播: ".$this -> getFormatTime($liveInterval)."\n<==================> \n ZBot V0.0.1";
                echo $msg;
                $this -> pushMsg($up, $msg, -1);
                
            } else if($state['live_status'] != 1 && $info['live_status']){
                $sql -> stopLive($up);
                $liveInterval = $sql -> getLiveStartInterval($up);
                $msg = "{$info['name']}直播结束了！\n 【{$state['title']}】\n".CQ::image($state['cover_from_user'])."直播时长: ".$this -> getFormatTime($liveInterval)."\n<==================> \n ZBot V0.0.1";;
                $this -> pushMsg($up, $msg, -1);
                if($info['danmu_record']){
                    $api -> delDanmu($info['room_id']);
                    $live = $sql -> getUpLiveHistoryTop($up);
                    $msg = $api -> getWordCloud($info['room_id'], $live['start_timestamp'], $live['stop_timestamp']);
                    $this -> pushMsg($up, "弹幕词云".$msg, -2);
                    zm_sleep(10);
                    $msg = $api -> getDanmuPic($info['room_id'], $live['start_timestamp'], $live['stop_timestamp']);
                    $this -> pushMsg($up, "弹幕峰值图".$msg, -2);
                }
            }
        }
    }
    
    /**
     * @OnTick(1500)
     * 推送直播提醒
     */
    public function dynamicNotice() {
        $sql = new Sql;
        $api = new Api;
        $config = new Config;
        $nowCheck = LightCache::get("nowDynamicCheck");
        $mids = $sql -> getAllUpsMids();
        if(count($mids) == 0){
            return;
        }
        if($nowCheck >= count($mids)) {
            $nowCheck = 0;
        }
        $mid = $mids[$nowCheck];
        $upInfo = $sql -> getUp($mid);
        $dynamicList = $api -> getDynamic($mid);
        if($dynamicList == NULL) {
            return;
        }
        for($i = count($dynamicList) - 1; $i >= 0; $i --){
            $dynamic = $dynamicList[$i];
            if($dynamic['desc']['timestamp'] > $upInfo['dynamic_check_timestamp']){
                $id = $dynamic['desc']["dynamic_id"];
                $type = $dynamic['desc']['type'];
                $name = $dynamic['desc']['user_profile']['info']['uname'];
                $pic = $api -> getDynamicPic($id);
                $msg = $name." ".$config -> dynamicMap[$type][1]."\n传送门 → "."https://t.bilibili.com/".$id."\n".$pic."\n<==================> \n ZBot V0.0.1";
                $sql -> updateUpDynamicTime($mid, $dynamic['desc']['timestamp']);
                $this -> pushMsg($mid, $msg, $type);
            }
        }
        LightCache::set("nowDynamicCheck", $nowCheck == count($mids) - 1 ? 0 : $nowCheck + 1);
    }
    
    
}