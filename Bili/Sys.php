<?php

namespace Module\Bili;

use ZM\Utils\ZMUtil;
use ZM\Config\ZMConfig;
use \ZM\Console\Console;
use ZM\Store\LightCache;
use ZM\Store\Lock\SpinLock;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\Swoole\OnStart;

/**
 * Class Sys
 * @package Module\Bili
 * @since 2.0
 */
class Sys{
    
    /**
     * @OnStart()
     */
    public function checkSql(){
        LightCache::set("nowDynamicCheck", 0);
        LightCache::set("BILI_BUILD_TIME", date("Y-m-d H:i:s"));
        LightCache::set("sqlOpen", 0);
        
        zm_sleep(5); # 等待一下数据库连接
        
        $config = ZMConfig::get("global", "mysql_config");
        if($config['host'] == ""){
            Console::error("BiLiPush模块需要数据库!!!!! 快连接一个!!!! 听到没有!!!");
            Console::error("框架停止运行了，直到你连接一个数据库他才能开启!!!!!");
            ZMUtil::stop();
        }
        
        $sql = new Sql;
        $config = new Config;
        $tables = $sql -> getAllTables();
        foreach ($config -> sqlNeed as $t){
            if(!in_array($t, $tables)){
                Console::warning(sprintf("缺少名为 %s 的数据库!", $t));
                $sql -> creatNewTable($t, $config -> sqlFields[$t], 'id');
                Console::info(sprintf("刚刚建立了名为 %s 的数据库!", $t));
            }
        }
        LightCache::set("sqlOpen", 1);
    }
    
    /**
	 * @CQBefore("message")
	 */
	public function filter(){
        $msg = ctx() -> getMessage();
        if(in_array(substr($msg, 0, 1), array('$'))){
            $superList = LightCache::get("SUPER_QQ");
            if($superList == null){
                ctx() -> reply("您还没有设置SuperQQ!"); 
                return false;
            }
            if(!in_array(ctx() -> getUserId(), $superList)){
                ctx() -> reply("您没有使用权限!");
                return false;
            }
        }
        return true;
	}
	
	/**
     * @CQCommand("getSuper")
     */
    public function getSuper(){
        if(ctx() -> getMessageType() == "group"){
            return "请私聊获得权限!"; 
        }
        $qqId = ctx() -> getUserId();
        $uniqId = md5(uniqid(microtime(true).$qqId, true));
        zm_dump($qqId."的独立验证码为: ".$uniqId);
        $r = ctx() -> waitMessage("请输入控制台显示的独立验证码，五分钟内有效", 300, "超时了! 请重新获得权限");
        if($r != $uniqId){ 
            return "验证码错误！请重新验证！"; 
        }
        SpinLock::lock("SUPER_QQ");
        $superList = LightCache::get("SUPER_QQ");
        if($superList != null){ 
            array_push($superList, $qqId); 
        }
        else {
            $superList = array($qqId);
        }
        LightCache::set("SUPER_QQ", $superList);
        if(count($superList) == 1){ 
            LightCache::addPersistence("SUPER_QQ");
        }
        SpinLock::unlock("SUPER_QQ");
        return "添加成功!";
    }
    
}