<?php
namespace app\common\controller;

include_once __DIR__ . "/../aes/wxBizDataCrypt.php";
use think\Db;

class UserInfo
{
  public static function register(){
    $code = input('post.code');
    $rawData = input('post.rawData');
    $iv = input('post.iv');
    $appId = "wxdbca556797541964";
    $appSecret = "36359ba0e02b0a389451b12c8def0397";
    $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code";
    $resJson = file_get_contents($url);
    $resArr = json_decode($resJson, true);
    if (array_key_exists('errcode', $resArr)) {
      printf ("login failed: %s\n", $resArr['errmsg']);
      exit();
    }
    $pc = new \WXBizDataCrypt($appId, $resArr['session_key']);
    $errCode = $pc->decryptData($rawData, $iv, $data);
    if ($errCode !== 0) {
      return $errCode;
    } 
    $data = json_decode($data, true);
    $unionid = $data['openId'];
    $uid = db('user')->where('unionid', $unionid)->value('uid');
    if (!$uid) {
      $data = array(
        'unionid' => $unionid,
        'avatar' => $data['avatarUrl'],
        'nick_name' => $data['nickName'],
      );
      $uid = db('user')->insertGetId($data);
    }
    return $uid;
  }

  public static function getUserInfo (array $uids) {
    $result = db('user')->field('uid, avatar, nick_name as nickName')->where('uid', 'in', $uids)->select();
    return $result;
  }
}
