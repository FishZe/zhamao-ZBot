import os
import sys
import time
import json
import jieba
import base64
import pymysql
import cv2 as cv
import numpy as np
import matplotlib.pyplot as plt
from collections import Counter
from PIL import Image, ImageDraw, ImageFont
from flask import Flask, send_file
from flask import request
from wordcloud import WordCloud, STOPWORDS


app = Flask(__name__)

def getDb():
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

@app.route("/wordcloud")
def wordCloud():
    roomId = int(request.args.get("roomid"))
    fromTime = int(request.args.get("from"))
    toTime = int(request.args.get("to"))
    db = getDb()
    cur = db.cursor(pymysql.cursors.DictCursor)
    sql = "SELECT * FROM `bili_danmu_%d` WHERE `time` > %d and `time` < %d" % (roomId, fromTime, toTime)
    cur.execute(sql)
    result = cur.fetchall()
    db.commit()
    data = []
    for i in result:
        words = jieba.lcut(base64.b64decode(i['content']).decode("utf-8"), cut_all = False) 
        for j in words:
            data.append(j)
    wordslist = ' '.join(data)
    fontPath = "./font/msyh.ttf"
    stopwords = set(STOPWORDS)
    wordPic = WordCloud(background_color = "white", max_words = 2000, scale = 32, font_path = fontPath)
    wordPic.generate(wordslist)
    name = "./pic/wordCloud/" + str(roomId)
    wordPic.to_file(name + '.png')
    img = cv.imread(name + '.png')
    cv.imwrite(name + '.jpg', img, [cv.IMWRITE_JPEG_QUALITY, 100])
    os.remove(name + '.png')
    return send_file(name + '.jpg')

@app.route('/bilicard', methods=["GET"])
def biliCard():
    cardId = request.args.get("id")
    url = "https://t.bilibili.com/" + str(cardId)
    path = sys.path[0] + '/'
    name = "./pic/bilicard/" + str(cardId)
    os.system(path + "phantomjs " + path + "screenShot/biliCard.js " + url + " " + name + '.png')
    img = cv.imread(name + '.png')
    cv.imwrite(name + '.jpg', img, [cv.IMWRITE_JPEG_QUALITY, 100])
    os.remove(name + '.png')
    return send_file(name + '.jpg')

@app.after_request
def delImg(environ):
    if request.path == '/bilicard':
        cardId = request.args.get("id")
        name = "./pic/bilicard/" + str(cardId)
        os.remove(name + '.jpg')
    elif request.path == 'wordcloud':
        roomId = request.args.get("roomid")
        name = "./pic/wordCloud/" + str(cardId)
        os.remove(name + '.jpg')
    return environ

if __name__ == '__main__':
    app.run(host = '0.0.0.0', port = 20003, debug = True)   