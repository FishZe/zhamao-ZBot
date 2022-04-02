<?php

namespace Module\Bili;

/**
 * Class Config
 * @package Module\Bili
 * @since 2.0
 */
class Config{
    
    public $danmuHost = "http://zbot_tookit:20002";
    public $toolKitHost = "http://zbot_danmu:20003";
    
    # 用于计算状态压缩
    public $dynamicType = array('转发' => 1, '图片' => 2, '文字' => 3, '视频' => 4, '小视频' => 5,
                                '戏剧' => 6, '专栏' => 7, '音频' => 8, '番剧' => 9, '其它' => 10);
    
    # 获取动态类型
    public $dynamicMap = array(
        1    => ['动态', '转发了动态'],          2    => ['图片', "发布了图片动态"],
        4    => ['文字', "发布了文字动态"],      8    => ['视频', "发布了视频"],
        16   => ['小视频', "发布了小视频"],      64   => ['专栏', "发布了新专栏"],
        256  => ['音频', "发布了音频"],          512  => ['番剧', "发布了番剧"],
        2048 => ['活动', "分享了H5活动动态"],    2049 => ['漫画', "分享了漫画"],
        4097 => ['PGC番剧', "发布了PGC番剧"],    4098 => ['电影', "发布了电影"],
        4099 => ['电视剧', "发布了电视剧"],      4100 => ['国漫', "发布了国创动漫"],
        4101 => ['纪录片', "发布了纪录片"],      4200 => ['直播', "直播了"],
        4201 => ['直播', "直播了"],              4300 => ['收藏夹', "发布了收藏夹"],
        4302 => ['付费课程', "发布了付费课程"],  4303 => ['付费课程', "发布了付费课程"],
        4308 => ['直播', "直播了"],              4310 => ['合集', "发布了合集"]);
    
    
    public $sqlNeed = array("bili_ups", "bili_push", "bili_live_history");
    
    public $sqlFields = array(
        'bili_ups' => array(
            array('name' => 'id',                       'type' => 'int' ),
            array('name' => 'mid',                      'type' => 'int',         'default' => 'NULL'),
            array('name' => 'name',                     'type' => 'text',        'default' => 'NULL'),
            array('name' => 'room_id',                  'type' => 'int',         'default' => 'NULL'),
            array('name' => 'live_status',              'type' => 'tinyint(1)',  'default' => '0'),
            array('name' => 'danmu_record',             'type' => 'tinyint(1)',  'default' => '0'),
            array('name' => 'dynamic_check_timestamp',  'type' => 'int',         'default' => '0')),
        "bili_push" => array(
            array('name' => 'id',            'type' => 'int' ),
            array('name' => 'bot_id',        'type' => 'bigint',      'default' => 'NULL'),
            array('name' => 'mid',           'type' => 'int',         'default' => 'NULL'),
            array('name' => 'dynamic_type',  'type' => 'bigint',      'default' => 'NULL'),
            array('name' => 'live_push',     'type' => 'tinyint(1)',  'default' => '0'),
            array('name' => 'danmu',         'type' => 'tinyint(1)',  'default' => '0'),
            array('name' => 'qq_type',       'type' => 'tinytext',    'default' => 'NULL'),
            array('name' => 'qq_id',         'type' => 'bigint',      'default' => 'NULL')),
        "bili_live_history" => array(
            array('name' => 'id',               'type' => 'int' ),
            array('name' => 'mid',              'type' => 'int',  'default' => 'NULL'),
            array('name' => 'start_timestamp',  'type' => 'int',  'default' => '0'),
            array('name' => 'stop_timestamp',   'type' => 'int',  'default' => '0')));


 
}

