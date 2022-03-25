import time
import base64
import pymysql

from fastapi import FastAPI
from blive import BLiver,Events
from blive.msg import DanMuMsg
from apscheduler.schedulers.asyncio import AsyncIOScheduler

app = FastAPI()

BLIVER_POOL = {}

danmus = []

db = None

def testConnect():
    try:
        db.ping()
    except:
        with open('../db.json') as f:
            data = json.load(f)
        db = pymysql.connect(
            host = data['host'],
            port = data['port'],
            user = data['user'],
            password = data['password'],
            db = data['db'],
            use_unicode = data['use_unicode'],
            charset = data['charset'])
    return db

# 定义弹幕事件handler
def create_bliver(roomid):
    # 定义弹幕事件handler
    async def listen(ctx):
        danmu = DanMuMsg(ctx.body)
        global danmus
        danmus.append({"danmu": danmu, 'roomid': roomid, 'time': time.time()})

    blive = BLiver(roomid)
    blive.register_handler(Events.DANMU_MSG, listen)
    return blive



@app.get("/create")
async def create_new_bliver(roomid:int):
    room = BLIVER_POOL.get(roomid,None)
    if not room:
        b = create_bliver(roomid)
        BLIVER_POOL[roomid] = b.run_as_task() # 启动监听
    return {"msg":"创建一个新直播间弹幕监听成功"}


@app.get("/del")
async def rm_bliver(roomid:int):
    room = BLIVER_POOL.get(roomid,None)
    if room:
        room.cancel()
    return {"msg":"移除直播间弹幕监听成功"}


@app.get("/show")
async def show():
    return list(BLIVER_POOL.keys())
    
def pushDB():
    global danmus
    nowdanmus = list(danmus)
    danmus = []
    db = testConnect()
    cur = db.cursor(pymysql.cursors.DictCursor)
    for i in nowdanmus:
        try:
            sql = "INSERT INTO `%s` (`mid`, `time`, `name`, `content`) VALUES (%d, %d, '%s', '%s')" % ('bili_danmu_' + str(i['roomid']),  i['danmu'].sender.id, i['time'], str(base64.b64encode(i['danmu'].sender.name.encode('utf-8')))[2:-1],  str(base64.b64encode(i['danmu'].content.encode('utf-8')))[2:-1])
            cur.execute(sql)
        except :
            print("error occured!")
    db.commit()

scheduler = AsyncIOScheduler({'apscheduler.timezone': 'UTC'})
scheduler.add_job(pushDB, 'interval', seconds = 5)
scheduler.start()