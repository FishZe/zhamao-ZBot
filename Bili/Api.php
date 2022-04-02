<?php

namespace Module\Bili;

use ZM\API\CQ;
use ZM\Requests\ZMRequest;

/**
 * Class Api
 * @package Module\Bili
 * @since 2.0
 */
class Api{
    
    # 如有需要可以修改Header  但是似乎没什么必要......
    private $USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36";
    
    /* 
     * 获取弹幕词云
     * Arg: $roomId(int) -> Up主直播间号  $startTime(int) -> 开始时间 $endTime(int) -> 结束时间
     * Author: FishZe
     * Date: 2022/03/19 23:01
     */
    public function getWordCloud($roomId, $startTime, $endTime){
        $config = new Config;
        if($config -> toolKitHost == ""){
            return "";
        }
        $URL = ($config -> toolKitHost)."/wordcloud?roomid={$roomId}&from={$startTime}&to={$endTime}";
        return CQ::image($URL);
    }
    
    /* 
     * 获取弹幕峰值图
     * Arg: $roomId(int) -> Up主直播间号  $startTime(int) -> 开始时间 $endTime(int) -> 结束时间
     * Author: FishZe
     * Date: 2022/03/29 18:40
     */
    public function getDanmuPic($roomId, $startTime, $endTime){
        $config = new Config;
        if($config -> toolKitHost == ""){
            return "";
        }
        $URL = ($config -> toolKitHost)."/getdanmupic?roomid={$roomId}&from={$startTime}&to={$endTime}";
        return CQ::image($URL);
    }
    
    /* 
     * 获取动态截图
     * Arg: $id(int) -> 动态编号
     * Author: FishZe
     * Date: 2022/03/19 23:01
     */
    public function getDynamicPic($id){
        $config = new Config;
        if($config -> toolKitHost == ""){
            return "";
        }
        $URL = ($config -> toolKitHost)."/bilicard?id=".$id;
        return CQ::image($URL);
    }
    
    /* 
     * 开启弹幕监控
     * Arg: $roomId(int) -> Up主直播间号
     * Author: FishZe
     * Date: 2022/03/19 22:24
     */
    public function startDanmu($roomId) {
        $config = new Config;
        if($config -> danmuHost == ""){
            return "";
        }
        $URL = ($config -> danmuHost)."/create?roomid=".strval($roomId);
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        return $res;
    }
    
    /* 
     * 关闭弹幕监控
     * Arg: $roomId(int) -> Up主直播间号
     * Author: FishZe
     * Date: 2022/03/19 22:30
     */
    public function delDanmu($roomId) {
        $config = new Config;
        if($config -> danmuHost == ""){
            return false;
        }
        $URL = ($config -> danmuHost)."/del?roomid=".strval($roomId);
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        return $res;
    }
    
    
    /* 
     * 获取Up主个人信息
     * Arg: $mid(int) -> 用户mid
     * Doc: https://github.com/SocialSisterYi/bilibili-API-collect/blob/master/user/info.md
     * Author: FishZe
     * Date: 2022/03/12 23:30
     */
    public function getUpInfo($mid) {
        $URL = "https://api.bilibili.com/x/space/acc/info?mid=".$mid;
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        if($res == NULL || $res['code'] != 0){
            return NULL; 
        }
        return $res['data'];
    }
    
    /* 获取多个直播间信息
     * Arg: $mids(array(int)) -> 用户mid数组 注意不是roomId
     * Doc: https://github.com/SocialSisterYi/bilibili-API-collect/blob/master/live/info.md
     * Author: FishZe
     * Date: 2022/03/12 23:40
     */
    public function getLiveRoomsStatus($mids){
        $URL = "http://api.live.bilibili.com/room/v1/Room/get_status_info_by_uids";
        $data = json_encode(["uids" => $mids]);
        $r = ZMRequest::post($URL, ["User-Agent" => $this -> USER_AGENT], $data);
        $res = json_decode($r, true);
        if($res == NULL || $res['code'] != 0){ 
            return NULL; 
        }
        return $res['data'];
    }
    
    
    /* 获取Bilibili用户动态
     * Arg: $mid(int) -> 用户mid  $needTop(bool) -> 是否需要置顶动态
     * Doc: 没找到，自己抓的包
     * Author: FishZe
     * Date: 2022/03/12 23:45
     */
    public function getDynamic($mid, $needTop = false){
        $URL = "https://api.vc.bilibili.com/dynamic_svr/v1/dynamic_svr/space_history?host_uid=".$mid;
        $URL = !$needTop ? $URL."&need_top=0" : $URL;
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        if($res == NULL || $res['code'] != 0){ 
            return NULL; 
        }
        return $res['data']['cards'];
    }
    
    /* 获取Bilibili用户动态  就一封装
     * Arg: $mid(int) -> 用户mid
     * Author: FishZe
     * Date: 2022/03/16 00:13
     */
    public function getDynamicLatest($mid){
        $r = $this -> getDynamic($mid, false)[0];
        return $r == NULL ? NULL : $r;
    }

    /* 搜索所有信息
     * Arg: $keyword(string) -> 关键词
     * Author: FishZe
     * Date: 2022/03/20 00:00
     */
    public function searchAll($keyword){
        $URL = "https://api.bilibili.com/x/web-interface/search/all/v2?keyword=".$keyword;
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        if($res == NULL || $res['code'] != 0){ 
            return NULL; 
        }
        return $res['data'];
    }
    
    /* 按类型搜索信息
     * Arg: $type(string) -> 类型  $keyword(string) -> 关键词
     * Author: FishZe
     * Date: 2022/03/20 00:03
     */
    public function searchByType($type, $keyword, $page = 1){
        $URL = "https://api.bilibili.com/x/web-interface/search/type?search_type={$type}&keyword={$keyword}&page={$page}";
        $r = ZMRequest::get($URL, ["User-Agent" => $this -> USER_AGENT]);
        $res = json_decode($r, true);
        if($res == NULL || $res['code'] != 0){ 
            return NULL; 
        }
        return $res['data'];
    }
    
    /* 按关键字获取所有的User
     * Arg: $keyword(string) -> 关键词
     * Author: FishZe
     * Date: 2022/03/20 00:24
     */
    public function searchAllUsers($keyword){
        $rawData = $this -> searchByType("bili_user", $keyword);
        $users = $rawData['result'];
        if($rawData == NULL){return NULL;}
        for($i = 2; $i <= $rawData['numPages']; $i ++){
            zm_sleep(0.1);
            $nowData = $this -> searchByType("bili_user", $keyword, $i);
            $users =  array_merge($users, $nowData['result']);
        }
        return $users;
    }
    
    /* 按关键字精确查找User
     * Arg: $name(string) -> 名字
     * Author: FishZe
     * Date: 2022/03/20 00:34
     */
    public function findUserAccurate($name){
        for($i = 1, $rawData = $this -> searchByType("bili_user", $name); $i <= $rawData !=NULL ? $rawData['numPages'] : 0; $i ++, zm_sleep(0.1)){
            foreach ($rawData['result'] as $u){ 
                if($u['uname'] == $name){ 
                    return $u; 
                } 
            }
            $nowData = $this -> searchByType("bili_user", $name, $i + 1);
        }
        return NULL;
    }
    
}