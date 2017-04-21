<?php
namespace app\spark\controller;

use app\common\controller\UserInfo;
use think\Db;

class User 
{
  public function login(){
    $uid = UserInfo::register();
    return $this->getUserInfo($uid);
  }

  public function getUserInfo($uid) {
    if (!$uid) {
      $uid = input('post.uid/d');
    }
    $userInfo = db('user')->field('uid, avatar, nick_name as nickName, cp_role as cpRole')
      ->where('uid', $uid)->find();
    $sfUser = db('sf_user')->field('sf_role, cur_festid')->where('uid', $uid)->find();
    if ($sfUser) {
      $userInfo['sfRole'] = $sfUser['sf_role'];
      $userInfo['curFestid'] = $sfUser['cur_festid'];
    } else {
      $maxFestid = db('sf_fest')->max('festid');
      if (!$maxFestid) {
        $maxFestid = 0;
      }
      db('sf_user')->insert(['uid' => $uid, 'cur_festid' => $maxFestid]);
      $userInfo['sfRole'] = 0;
      $userInfo['curFestid'] = $maxFestid;
    }
    if ($userInfo['curFestid'] === 0) {
      $userInfo['festRole'] = $userInfo['curProjid'] = 0;
      return $userInfo;
    }
    $result =$this->getFestRole($uid, $userInfo['curFestid']);
    $userInfo['festRole'] = $result['festRole'];
    $userInfo['curProjid'] = $result['curProjid'];
    return $userInfo;
  }

  public function getFestRole($uid, $festid){
    if ($festid === 0) {
      $festid = db('sf_fest')->max('festid');
    }
    $map = array(
      'uid' => $uid,
      'festid' => $festid
    );
    $festRole = db('sf_fest_member')->where($map)->value('fest_role');
    if (!$festRole) {
      $festRole = 0;
    }
    if ($festRole === 1) {
      $projid = db('sf_proj')->where(['festid'=>$festid, 'captainid'=>$uid])->value('projid');
    } else {
      $projids = db('sf_proj')->where('festid', $festid)->column('projid');
      $map = array(
        'projid' => array('in', $projids),
        'uid' => $uid
      );
      $projid = db('sf_proj_member')->where($map)->value('projid');
    }
    if (!$projid) {
      $projid = 0;
    }
    $result = array(
      'festRole' => $festRole,
      'curProjid' => $projid
    );
    return $result;
  }

  public function getQrcode(){
    $path = input('post.path/s');
    $name = input('post.name/s');
    $appId = "wxdbca556797541964";
    $appSecret = "36359ba0e02b0a389451b12c8def0397";
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
    $resData = file_get_contents($url);
    $resArr = json_decode($resData, true);
    $accessToken = $resArr['access_token'];
    if ($accessToken){
      $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$accessToken";
      $data = json_encode(array('path'=>$path, 'width'=>200));
      $opts = array(
        'http'=> array(
          'method'=>'POST',
          'header'=>"Content-type: application/x-www-form-urlencoded",
          'content'=>$data
        )
      );
      $context = stream_context_create($opts);
      $resData = file_get_contents($url, false, $context);
      $path = __DIR__ . "/../../../public/static/qrcode/$name.png";
      $result = file_put_contents($path, $resData);
      return $result;
    }
  }

  public function addOrganizer(){
    $uid = input('post.uid/d');
    $result = db('sf_user')->where('uid', $uid)->update(['sf_role' => 1]);
    return $result;
  }

  public function changeCurFest(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $result = db('sf_user')->where('uid', $uid)->update(['cur_festid' => $festid]);
    return $result;
  }

  public function addMentor(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $map = array(
      'uid' => $uid,
      'festid' => $festid,
    );
    $festRole = db('sf_fest_member')->where($map)->value('fest_role');
    if ($festRole ===0 ||$festRole === 1) {
        $result = db('sf_fest_member')->where($map)->update(['fest_role' => 2]);
    } else if (!$festRole){
      $result = db('sf_fest_member')->insert(['uid'=>$uid, 'festid'=>$festid, 'fest_role'=>2]);
    }
    db('sf_user')->where('uid', $uid)->update(['cur_festid' => $festid]);
    return $result;
  }

  public function deleteMentor(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $map = array(
      'uid' => $uid,
      'festid' => $festid,
      'fest_role' => 2
    );
    $result = db('sf_fest_member')->where($map)->update(['fest_role'=>0]);
    return $result;
  }

  public function addCaptain(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $map = array(
      'uid' => $uid,
      'festid' => $festid,
    );
    $festRole = db('sf_fest_member')->where($map)->value('fest_role');
    print_r($festRole);
    if ($festRole === 0) {
      $result = db('sf_fest_member')->where($map)->update(['fest_role' => 1]);
    } else if (!$festRole) {
      $result = db('sf_fest_member')->insert(['uid'=>$uid, 'festid'=>$festid, 'fest_role'=>1]);
    }
    db('sf_user')->where('uid', $uid)->update(['cur_festid' => $festid]);
    return $result;
  }

  public function deleteCaptain(){
    $festid = input('post.festid/d');
    $uid = input('post.uid/d');
    $map = array(
      'festid' => $festid,
      'uid' => $uid,
      'fest_role' => 1
    );
    $result = db('sf_fest_member')->where($map)->update(['fest_role'=>0]);
    $map = array(
      'captainid' => $uid,
      'festid' => $festid
    );
    $projid = db('sf_proj')->where($map)->value('projid');
    if ($projid) {
      db('sf_proj')->where('projid', $projid)->delete();
      db('sf_proj_member')->where('projid', $projid)->delete();
      $cmntids = db('sf_proj_cmnt')->where('projid', $projid)->column('cmntid');
      db('sf_proj_cmnt')->where('projid', $projid)->delete();
      db('sf_proj_reply')->where('cmntid', 'in', $cmntids)->delete();
    }
    return $result;
  }

  public function addMember(){
    $uid = input('post.uid/d');
    $projid = input('post.projid/d');
    $festid = db('sf_proj')->where('projid', $projid)->value('festid');
    db('sf_user')->where('uid', $uid)->update(['cur_festid' => $festid]);
    $map = array(
      'uid' => $uid,
      'projid' => $projid
    );
    $isExist = db('sf_proj_member')->where($map)->find();
    $map = array(
      'captainid' => $uid,
      'projid' => $projid
    );
    $isCaptain = db('sf_proj')->where($map)->find();
    if ($isExist || $isCaptain) {
      return 'exist';
    }
    $result = db('sf_proj_member')->insert(['uid'=>$uid, 'projid'=>$projid]);
    return $result;
  }

  public function deleteMember(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $projids = db('sf_proj')->where('festid', $festid)->column('projid');
    $map = array(
      'uid' => $uid,
      'projid' => array('in', $projids)
    );
    $result = db('sf_proj_member')->where($map)->delete();
    return $result;
  }

  public function deleteMemberByProj(){
    $uid = input('post.uid/d');
    $projid = input('post.projid/d');
    $map = array(
      'uid' => $uid,
      'projid' => $projid
    );
    $result = db('sf_proj_member')->where($map)->delete();
    return $result;
  }

  public function getFestMemberInfo(){
    $festid = input('post.festid/d');
    $map = array(
      'festid' => $festid,
      'fest_role' => 2
    );
    $mentorids = db('sf_fest_member')->where($map)->column('uid');
    $map['fest_role'] = 1;
    $captainids = db('sf_fest_member')->where($map)->column('uid');
    $projids = db('sf_proj')->where('festid', $festid)->column('projid');
    $memberids = db('sf_proj_member')->where('projid', 'in', $projids)->column('uid');
    $result = array(
      'mentors' => UserInfo::getUserInfo($mentorids),
      'captains' => UserInfo::getUserInfo($captainids),
      'members' => UserInfo::getUserInfo($memberids)
    );
    return json($result);
  }
}
