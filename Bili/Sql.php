<?php

namespace Module\Bili;

use ZM\Config\ZMConfig;

/**
 * Class Sql
 * @package Module\Bili
 * @since 2.0
 */
class Sql{
    
    /*
     * 获取所有数据表
     * Author: FishZe
     * Date: 2022/03/13 22:33
     */
    public function getAllTables(){
        $config = ZMConfig::get("global", "mysql_config");
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $rawTables = $wrapper -> fetchAllAssociative('select table_name from information_schema.tables where table_schema= ?;', array($config['dbname']));
        $tables = array();
        foreach ($rawTables as $t){
            array_push($tables, $t['TABLE_NAME']);
        }
        return $tables;
    }
    
    /* 
     * 创建新数据表
     * Author: FishZe
     * Args: $name(string) -> 数据表名  $fields(array) -> 每个参数的信息$i  $mainKey(string) -> 主键名
    *        $i(array) -> $name(string) -> 键名  $type(string) -> 类型名  $default($type) -> 默认值, 主键无效
     * Date: 2022/03/14 23:48
     */
    public function creatNewTable($name, $fields, $mainKey) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $sql = sprintf("CREATE TABLE `%s` (", $name);
        foreach ($fields as $i){
            $nowSql = sprintf("`%s` %s ", $i['name'], $i['type']);
            $nowSql = $i['name'] == $mainKey ? $nowSql."NOT NULL AUTO_INCREMENT, " : $nowSql." DEFAULT ".$i['default'].", ";
            $sql = $sql.$nowSql;
        }
        $sql = $sql.sprintf(" PRIMARY KEY (`%s`)) ENGINE = innodb", $mainKey);
        $r = $wrapper -> executeStatement($sql);
        return $r;
    }
    
    /* 
     * 删除数据表
     * Author: FishZe
     * Args: $name(string) -> 数据表名 
     * Date: 2022/03/25 02:24
     */
    public function dropTable($name){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $tables = $this -> getAllTables();
        if(!in_array($name, $tables)){ 
            return false; 
        }
        $r = $wrapper -> executeStatement('DROP TABLE '.$name);
        return $r;
    }
    
    /* 
     * 获取所有Up主的原始信息
     * Author: FishZe
     * Date: 2022/03/13 21:00
     */
    public function getAllUps(){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $ups = $wrapper -> fetchAllAssociative('SELECT * FROM bili_ups');
        return $ups;
    }
    
    /* 
     * 获取一位Up主的原始信息
     * Author: FishZe
     * Date: 2022/03/13 22:35
     */
    public function getUp($mid){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $up = $wrapper -> fetchAssociative('SELECT * FROM bili_ups where mid = ?', array($mid));
        if($up == false){
            return false;
        }
        return $up;
    }
    
    /* 
     * 获取所有Up主的Mid
     * Author: FishZe
     * Date: 2022/03/13 21:01
     */
    public function getAllUpsMids(){
        $ups = $this -> getAllUps();
        $mids = array_column($ups, 'mid');
        return $mids;
    }
    
    /* 
     * 通过名字获取Up主
     * Author: FishZe
     * Date: 2022/03/25 22:21
     */
    public function getUpByName($name) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $up = $wrapper -> fetchAssociative('SELECT * FROM bili_ups where name = ?', array($name));
        if($up == false){
            return false;
        }
        return $up;
    }
    
    /* 
     * 获取所有Up主的Mid与信息的映射关系
     * Author: FishZe
     * Date: 2022/03/13 21:02
     */
    public function getAllUpsByMid() {
        $allUps = $this -> getAllUps();
        $ups = array();
        foreach ($allUps as $up){
            $ups[$up['mid']] = $up;
        }
        return $ups;
    }
    
    /* 
     * 更新Up主的动态发布时间
     * Author: FishZe
     * Date: 2022/03/25 21:01
     */
    public function updateUpDynamicTime($mid, $time) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> update('bili_ups', array('dynamic_check_timestamp' => $time), array('mid' => $mid));
    }
    
    /* 
     * 新增Up主
     * Args: $mid(int) -> Up主Mid  $name(string) -> Up主名称  $roomId(int) -> Up主直播间  danmu_record(bool) -> 是否记录弹幕
     * Author: FishZe
     * Date: 2022/03/13 21:17
     */
    public function addNewUp($mid, $name, $roomId, $danmuRecord) {
        if($this -> getUp($mid) != false){return true;}
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> insert('bili_ups', array('mid' => $mid, 'name' => $name, 'room_id' => $roomId, 'live_status' => 0, 'danmu_record' => $danmuRecord, 'dynamic_check_timestamp' => time()));
        if($danmuRecord){
            
        }
        return true;
    }
    
    /* 
     * 删除Up主
     * Author: FishZe
     * Date: 2022/03/14 23:18
     */
    public function deleteUp($mid, $force = true) {
        $nowUp = $this -> getUp($mid);
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> delete('bili_ups', array('mid' => $mid));
        if(!$force){return true;}
        $wrapper -> delete('bili_push', array('mid' => $mid));
        $wrapper -> delete('bili_live_history', array('mid' => $mid));
        if($nowUp['danmu_record']){ 
            $this -> dropDanmuTable($nowUp['room_id'], $mid); 
        }
        return true;
    }
    
    /* 
     * 获取Up主的提醒列表
     * Arg: $mid(int) -> Up主Mid
     * Author: FishZe
     * Date: 2022/03/13 21:04
     */
    public function getUpPush($mid) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $push = $wrapper -> fetchAllAssociative('SELECT * FROM bili_push where mid = ?', array($mid));
        return $push;
    }
    
    /* 
     * 获取Up主的特定类型提醒列表
     * Arg: $mid(int) -> Up主Mid  $type(string) -> 消息类型 'Private' || 'group'
     * Author: FishZe
     * Date: 2022/03/13 21:07
     */
    public function getUpPushByType($mid, $type) {
        $rawList = $this -> getUpPush($mid);
        if($type == "all"){return $rawList;}
        $list = array();
        foreach ($rawList as $qq){ 
            if($qq['qq_type'] == $type){ 
                array_push($list, $qq); 
                }
        }
        return $list;
    }
    
    /* 
     * 获取Up主的特定类型提醒列表的QQ号，我也不知道为什么要做一个封装，可能是疯了吧
     * Arg: $mid(int) -> Up主Mid  $type(string) -> 消息类型 'Private' || 'group'
     * Author: FishZe
     * Date: 2022/03/13 21:07
     */
    public function getUpPushByTypeQQs($mid, $type){
        $pushs = $this -> getUpPushByType($mid, $type);
        $ids = array_column($pushs, 'qq_id');
        return $ids;
    }
    
    /* 
     * 增加一个Up主提醒
     * Arg: $mid(int) -> Up主Mid  $botId(int) -> bot的qq号  $dynamicType(int) -> 动态提醒列表 $livePush(bool) -> 直播提醒  $danmuPush(bool) -> 弹幕统计提醒  
            $qqType(string) -> 类型  $qqId(int) -> qq号
     * Author: FishZe
     * Date: 2022/03/25 00:54
     */
    public function addPush($mid, $botId, $dynamicType, $livePush, $danmuPush, $qqType, $qqId){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> insert('bili_push', array('bot_id' => $botId, 'mid' =>  $mid, 'dynamic_type' => $dynamicType, 'live_push' => $livePush, 'danmu' => $danmuPush, 'qq_type' => $qqType, 'qq_id' => $qqId));
    }
    
    /* 
     * 按QQ号和Up主mid获得提醒
     * Arg: $mid(int) -> Up主Mid  $qqType(string) -> 类型  $qqId(int) -> qq号
     * Author: FishZe
     * Date: 2022/03/25 02:17
     */
    public function getQQPush($mid, $qqType, $qqId){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $r = $wrapper -> fetchAssociative('SELECT * FROM bili_push where mid = ? and qq_type = ? and qq_id = ?', array($mid, $qqType, $qqId));
        return $r;
    }
    
    /* 
     * 删除一个Up主提醒
     * Arg: $mid(int) -> Up主Mid  $qqType(string) -> 类型  $qqId(int) -> qq号
     * Author: FishZe
     * Date: 2022/03/25 00:54
     */
    public function cancelPush($mid, $qqType, $qqId){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> delete('bili_push', array('mid' => $mid, 'qq_type' => $qqType, 'qq_id' => $qqId));
    }
    
    /* 
     * 获取Up主的直播历史
     * Arg: $mid(int) -> Up主Mid 
     * Author: FishZe
     * Date: 2022/03/13 21:10
     */
    public function getUpLiveHistory($mid) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $history = $wrapper -> fetchAllAssociative('SELECT * FROM bili_live_history where mid = ?', array($mid));
        return $history;
    }
    
    /* 
     * 获取Up主的最新直播
     * Arg: $mid(int) -> Up主Mid 
     * Author: FishZe
     * Date: 2022/03/13 21:12
     */
    public function getUpLiveHistoryTop($mid) {
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $history = $wrapper -> fetchAllAssociative('SELECT * from bili_live_history where id = (SELECT MAX(id) FROM (SELECT * from bili_live_history where mid = ?) tmp_table)', array($mid));
        if($history == NULL){
            return NULL;
        }
        return $history[0];
    }
    
    /* 
     * 获取Up主距离上次直播结束的时间间隔(秒)
     * Arg: $mid(int) -> Up主Mid 
     * Author: FishZe
     * Date: 2022/03/19 20:24
     */
    public function getLiveStopInterval($mid) {
        $lastLive = $this -> getUpLiveHistoryTop($mid);
        if($lastLive == NULL) {
            return -1;
        }
        return time() - $lastLive['stop_timestamp'];
    }
    
    /* 
     * 获取Up主距离上次直播开始的时间间隔(秒)
     * Arg: $mid(int) -> Up主Mid 
     * Author: FishZe
     * Date: 2022/03/19 20:25
     */
    public function getLiveStartInterval($mid) {
        $lastLive = $this -> getUpLiveHistoryTop($mid);
        if($lastLive == NULL) {
            return -1;
        }
        return time() - $lastLive['start_timestamp'];
    }
    
    /* 
     * 开始直播，更新直播历史数据库和Up主数据库
     * Arg: $mid(int) -> Up主Mid
     * Author: FishZe
     * Date: 2022/03/19 20:20
     */
    public function startLive($mid){
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> update('bili_ups', array('live_status' => 1), array('mid' => $mid));
        $wrapper -> insert('bili_live_history', array('mid' => $mid, 'start_timestamp' => time()));
    }
    
    /* 
     * 结束直播，更新直播历史数据库和Up主数据库
     * Arg: $mid(int) -> Up主Mid 
     * Author: FishZe
     * Date: 2022/03/19 20:23
     */
    public function stopLive($mid){
        $nowLive = $this -> getUpLiveHistoryTop($mid);
        $wrapper = \ZM\MySQL\MySQLManager::getWrapper();
        $wrapper -> update('bili_live_history', array('stop_timestamp' => time()), array('id' => $nowLive['id']));
        $wrapper -> update('bili_ups', array('live_status' => 0), array('mid' => $mid));
    }
    
    /* 
     * 创建弹幕数据表
     * Arg: $roommId(int) -> Up主roomId  $mid(int) -> Up主mid  推荐使用roomid 除非你真的没有
     * Author: FishZe
     * Data: 2022/03/14 23:53
     */
    public function createDanmuTable($roomId = NULL, $mid = NULL) {
        if($roomId == NULL && $mid == NULL){
            return false;
        }
        if($roomId == NULL){
            $up = $this -> getUp($mid);
            $roomId = $up['room_id'];
        }
        $fields = array(
            array('name' => 'id',       'type' => 'int' ),
            array('name' => 'mid',      'type' => 'int',   'default' => 'NULL'),
            array('name' => 'time',     'type' => 'int',   'default' => 'NULL'),
            array('name' => 'name',     'type' => 'text',  'default' => 'NULL'),
            array('name' => 'content',  'type' => 'text',  'default' => 'NULL'));
        return $this -> creatNewTable('bili_danmu_'.$roomId, $fields, 'id');
    }
    
    /* 
     * 删除弹幕数据表
     * Arg: $roomm_id(int) -> Up主roomId  $mid(int) -> Up主mid  推荐使用roomid 除非你真的没有
     * Author: FishZe
     * Data: 2022/03/25 02:30
     */
    public function dropDanmuTable($roomId = NULL, $mid = NULL) {
        if($roomId == NULL && $mid == NULL){
            return false;
        }
        if($roomId == NULL){
            $up = $this -> getUp($mid);
            $roomId = $up['room_id'];
        }
        return $this -> dropTable('bili_danmu_'.$roomId);
    }

}