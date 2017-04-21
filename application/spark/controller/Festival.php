<?php
namespace app\spark\controller;

use think\Db;

class Festival 
{
  public function addFest(){
    $uid = input('post.uid/d');
    $data = array(
      'orgerid' => $uid,
      'title' => input('post.title/s'),
      'intro' => input('post.intro/s'),
      'addr' => input('post.addr/s'),
      'stime' => input('post.stime/d'),
      'etime' => input('post.etime/d'),
    );
    $festid = db('sf_fest')->insertGetId($data);
    $result = db('sf_user')->where('uid', $uid)->update(['cur_festid' => $festid]);
    $tmpName = $_FILES['file']['tmp_name'];
    $data = file_get_contents($tmpName);
    $fileName = "fest$festid"."logo1.png";
    $path = __DIR__ . "/../../../public/static/logo/$fileName";
    $result = file_put_contents($path, $data);
    if ($result) {
      $url = "https://www.kingco.tech/static/logo/$fileName";
      $data = [
        'festid' => $festid,
        'logoid' => 1,
        'url' => $url
      ];
      db('sf_fest_logo')->insert($data);
    }
    return $festid;
  }

  public function getFestInfo(){
    $festid = input('post.festid/d');
    $uid = input('post.uid/d');
    if ($festid === 0) {
      $festid = db('sf_fest')->max('festid');
    } 
    $festInfo = db('sf_fest')->where('festid', $festid)->find();
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
    $logos = db('sf_fest_logo')->where('festid', $festid)->order('logoid')->column('url');
    $festInfo['logos'] = $logos;
    $result = array(
      'festInfo' => $festInfo,
      'festRole' => $festRole,
      'curProjid' => $projid
    );
    return $result;
  }

  public function getAllFestInfo(){
    $allFestInfo = db('sf_fest')->field('festid, title')->order('ctime', 'desc')->select();
    return json($allFestInfo);
  }
  
  public function updateFestInfo(){
    $festid = input('post.festid/d');
    $festInfoArr = input('post.festInfo/a');
    $result = db('sf_fest')->where('festid', $festid)->update($festInfoArr);
    return $result;
  }

  public function getFestProjInfo(){
    $festid = input('post.festid/d');
    $sql = "SELECT a.uid, a.avatar, a.nick_name as nickName, b.title, b.projid FROM cp_user as a, cp_sf_proj as b WHERE b.festid=$festid AND a.uid=b.captainid";
    $result = Db::query($sql);
    return json($result);
  }

}
