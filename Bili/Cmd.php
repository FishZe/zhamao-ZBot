<?php

namespace Module\Bili;

use ZM\Utils\ZMUtil;
use ZM\Config\ZMConfig;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQBefore;
use ZM\Store\LightCache;

/**
 * Class Cmd
 * @package Module\Bili
 * @since 2.0
 */
class Cmd{
    
    /**
     * 添加一个推送，没有Up时会自动添加相关行
     * Args:  $botId(int) -> bot的qq号  $dynamicType(int) -> 动态提醒列表 $livePush(bool) -> 直播提醒  $qqType(string) -> 类型  
     *        $qqId(int) -> qq号  $mid(int) -> Up主Mid  $danmuPush(bool) -> 弹幕统计提醒  $name(string) -> 名字  $roomId(int) -> 直播房间号
     * Author: FishZe
     * Data; 2022/03/25 01:39
     */
    public function addBiliPush($botId, $dynamicType, $livePush, $qqType, $qqId, $mid, $danmuRecord = 0, $name = NULL, $roomId = NULL){
        $sql = new Sql;
        $api = new Api;
        if(in_array($qqId, $sql -> getUpPushByTypeQQs($mid, $qqType))){
            return "the subscription has exist!";
        }
        $upInfo = array();
        if(!in_array($mid, $sql -> getAllUpsMids($mid))){
            if($name == NULL){
                $data = $api -> getUpInfo($mid);
                if($data == NULL){ 
                    return "name error!"; 
                }
                $name = $data['name'];
                $roomId = $data['live_room']['roomid'];
            }
            $sql -> addNewUp($mid, $name, $roomId, $danmuRecord);
        } else { $upInfo = $sql -> getUp($mid); }
        if((empty($upInfo) && $danmuRecord) || (!empty($upInfo) && !$upInfo['danmu_record'] && $danmuRecord)){
            $sql -> createDanmuTable(empty($upInfo) ? $roomId : $upInfo['room_id']);
        }
        $sql ->  addPush($mid, $botId, $dynamicType, $livePush, $danmuRecord, $qqType, $qqId);
        return "subscribe successfully!";
    }
    
    /* 
     * 取消一个提醒
     * Arg: $mid(int) -> Up主Mid  $qqType(string) -> 类型  $qqId(int) -> qq号
     * Author: FishZe
     * Date: 2022/03/25 02:41
     */
    public function cancelBiliPush($mid, $qqType, $qqId){
        $sql = new Sql;
        $pushInfo = $sql -> getQQPush($mid, $qqType, $qqId);
        $sql -> cancelPush($mid, $qqType, $qqId);
        $pushList = $sql -> getUpPush($mid);
        if(count($pushList) == 0){ 
            $sql -> deleteUp($mid); 
            return "cancel successfully!"; 
        }
        if($pushInfo['danmu']){
            $danmuRecord = false;
            foreach ($pushList as $i){ 
                if($i['danmu']){ 
                    $danmuRecord = true; 
                    break; 
                }
            }
            if(!$danmuRecord){ 
                $sql -> dropDanmuTable(NULL, $mid); 
            }
        }
        return "cancel successfully!";
    }
    
    /**
     * @CQCommand("$push")
     * Example: $push {who} {which} 
     *                {who}: $name || --uid(-u) $mid
     *                {which} {$type};{$type}....{$type} || --binary(-b) {$num}
     *                        {$num}: (1 << {class(config) -> dynamicType[$type]) & ...  + $live + $danmu
     */
    public function getPushMsg(){
        $r = $this -> push(ctx() -> getMessage());
        if($r == "lack of args!"){
            $msg = "可能缺少必要参数，请检查语句后重试!";
        } else if($r == 'error mid!'){
            $msg = "你的mid可能存在问题，请检查是否正确后重试!";
        } else if($r == 'error name!'){
            $msg = "无法通过名字找到该up主，或许是该up主等级太低，你可以使用mid订阅!";
        } else if($r == "the subscription has exist!"){
            $msg = "该订阅已经存在，请勿重复订阅!";
        } else if($r == "subscribe successfully!"){
            $msg = "订阅成功啦!";
        }
        $msg = $msg."\n <==================> \n ZBot V0.0.1 \n 开源地址: https://github.com/FishZe/zhamao-ZBot \n build: ".LightCache::get("BILI_BUILD_TIME");
        return $msg;
    }
    
    /**
     * @CQCommand("$cancelPush")
     * Example: $push {who} 
     *                {who}: $name || --uid(-u) $mid
     */
    public function cancelPushMsg(){
        $r = $this -> cancelPush(ctx() -> getMessage());
        if($r == "lack of args!"){
            $msg = "可能缺少必要参数，请检查语句后重试!";
        }  else if($r == 'error name!'){
            $msg = "无法通过名字找到该up主，或许是该up主等级太低，你可以使用mid订阅!";
        } else if($r == "cancel successfully!"){
            $msg = "取消成功啦!";
        }
        $msg = $msg."\n <==================> \n ZBot V0.0.1 \n 开源地址: https://github.com/FishZe/zhamao-ZBot \n build: ".LightCache::get("BILI_BUILD_TIME");
        return $msg;
    }
    
    /* 
     * 解析推送列表
     * Arg: $content(string) 以分号隔开的类型列表
     * Author: FishZe
     * Date: 2022/03/25 18:49
     */
    public function parseTypes($content){
        $config = new Config;
        $type = explode(';', $content);
        $livePush = in_array("直播", $type) ? 1 : 0;
        $danmu = in_array("直播", $type) && in_array("直播弹幕", $type) ? 1 : 0;
        $dynamicType = 0;
        foreach (array_keys($config -> dynamicType) as $i){
            if(in_array($i, $type)){ 
                $dynamicType = $dynamicType | (1 << ($config -> dynamicType[$i] - 1)); 
            }
        }
        return $dynamicType.$livePush.$danmu;
    }
    
    /* 
     * 取消推送消息解析函数  当然你可以直接通过传参调用
     * Arg: $msg(string) -> 原始消息
     * Author: FishZe
     * Date: 2022/03/25 05:26
     */
    public function cancelPush($msg = NULL, $qqType = NULL, $qqId = NULL,$botId = NULL){
        $api = new Api;
        $sql = new Sql;
        $config = new Config;
        if($msg == NULL){ 
            $msg = ctx() -> getMessage(); 
        }
        $args = explode(' ', $msg);
        if(count($args) == 1) { 
            return "lack of args!"; }
        if($args[1] == '--uid' || $args[1] == '-m'){
            if(count($args) < 3) { 
                return "lack of args!"; 
            }
            $mid = $args[2];
        } else {
            $up = $sql -> getUpByName($args[1]);
            if($up == NULL) { 
                return 'error name!'; 
            }
            $mid = $up['mid'];
        }
        $qqType = $qqType == NULL? ctx() -> getMessageType() : $qqType;
        $qqId = $qqId == NULL ? ($qqType == "private" ? ctx() -> getUserId() : ctx() -> getGroupId()) : $qqId;
        $r = $this -> cancelBiliPush($mid, $qqType, $qqId);
        return $r;
    }
    
    
    /* 
     * 推送消息解析函数  当然你也可以直接通过传参调用
     * Arg: $msg(string) -> 原始消息
     * Author: FishZe
     * Date: 2022/03/25 05:26
     */
    public function push($msg = NULL, $qqType = NULL, $qqId = NULL, $botId = NULL){
        $api = new Api;
        $sql = new Sql;
        $config = new Config;
        if($msg == NULL){ 
            $msg = ctx() -> getMessage(); 
        }
        $offset = 1;
        $args = explode(' ', $msg);
        
        if(count($args) < 3){ return "lack of args!"; }
        if($args[$offset] == '--uid' || $args[$offset] == '-u'){
            $mid = $args[++ $offset];
            $offset ++;
            $data = $api -> getUpInfo($mid);
            if($data == NULL) { 
                return 'error mid!'; 
            }
            $name = $data['name'];
            $roomId = $data['live_room']['roomid'];
        } else {
            $name = $args[$offset ++];
            $data = $api -> findUserAccurate($name);
            if($data == NULL) { 
                return 'error name!'; 
            }
            $mid = $data['mid'];
            $roomId = $data['room_id'];
        }
        if(count($args) < $offset + 1){ return "lack of args!"; }
        
        if(!($args[$offset] == '--binary' || $args[$offset] == '-b')){
            $types = $this -> parseTypes($args[$offset]);
        } else {
            if(count($args) < $offset + 2) {
                return "lack of args!"; 
            }
            $types = $args[++ $offset];
        }
        $danmu = $types % 10;
        $livePush = (($types % 100) - $danmu) / 10;
        $dynamicType = intval($types / 100);

        $qqType = $qqType == NULL? ctx() -> getMessageType() : $qqType;
        $qqId = $qqId == NULL ? ($qqType == "private" ? ctx() -> getUserId() : ctx() -> getGroupId()) : $qqId;
        $botId = $botId == NULL? ctx() -> getRobotId() : $botId;
        $r = $this -> addBiliPush($botId, $dynamicType, $livePush, $qqType, $qqId, $mid, $danmu, $name, $roomId);
        return $r;
    }
    
    
    
}