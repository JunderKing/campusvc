<?php
namespace app\spark\controller;

use think\Db;
use app\spark\controller\Comment;


class Project 
{
  public function addProj(){
    $festid = input('post.festid/d');
    $data = array(
      'captainid' => input('post.uid/d'),
      'festid' => $festid,
      'title' => input('post.title/s'),
      'intro' => input('post.intro/s'),
    );
    $projid = db('sf_proj')->insertGetId($data);
    $data = [
      ['projid'=>$projid, 'stepid'=>1],
      ['projid'=>$projid, 'stepid'=>2],
      ['projid'=>$projid, 'stepid'=>3],
      ['projid'=>$projid, 'stepid'=>4],
      ['projid'=>$projid, 'stepid'=>5],
      ['projid'=>$projid, 'stepid'=>6],
      ['projid'=>$projid, 'stepid'=>7],
    ];
    db('sf_proj_prog')->insertAll($data);
    return $projid;
  }

  public function getMyProjInfo(){
    $uid = input('post.uid/d');
    $festid = input('post.festid/d');
    $map = array(
      'festid' => $festid,
      'captainid' => $uid
    );
    $projid = db('sf_proj')->where($map)->value('projid');
    if (!$projid) {
      $projids = db('sf_proj')->where('festid', $festid)->column('projid');
      $map = array(
        'projid' => array('in', $projids),
        'uid' => $uid
      );
      $projid = db('sf_proj_member')->where('uid', $uid)->value('projid');
      if (!$projid) {
        return ['errcode'=>1, 'errmsg'=>'No Project!'];
      }
    }
    $projInfo = db('sf_proj')->field('projid, title, intro, captainid')->where('projid', $projid)->find();
    $memberids = db('sf_proj_member')->where('projid', $projid)->column('uid');
    $projMembers = db('user')->field('uid, avatar, nick_name as nickName')->where('uid', 'in', $memberids)->select();
    $captainInfo = db('user')->field('uid, avatar, nick_name as nickName')->where('uid', $projInfo['captainid'])->find();
    array_unshift($projMembers, $captainInfo);
    $projInfo['members'] = $projMembers;
    $projCmnts = Comment::getProjCmnt($projid);
    $projInfo['comments'] = $projCmnts;
    return json($projInfo);
  }

  public function getProjInfo(){
    $projid = input('post.projid/d');
    $projInfo = db('sf_proj')->field('projid, title, intro, captainid')->where('projid', $projid)->find();
    $memberids = db('sf_proj_member')->where('projid', $projid)->column('uid');
    $projMembers = db('user')->field('uid, avatar, nick_name as nickName')->where('uid', 'in', $memberids)->select();
    $captainInfo = db('user')->field('uid, avatar, nick_name as nickName')->where('uid', $projInfo['captainid'])->find();
    array_unshift($projMembers, $captainInfo);
    $projInfo['members'] = $projMembers;
    $projCmnts = Comment::getProjCmnt($projid);
    $projInfo['comments'] = $projCmnts;
    return json($projInfo);
  }

  public function getProgInfo(){
    $projid = input('post.projid/d');
    $projProg = db('sf_proj_prog')->field('stepid, url, content')->where('projid', $projid)->order('stepid')->select();
    return json($projProg);
  }

  public function updateProjInfo(){
    $projid = input('post.projid/d');
    $projInfoArr = input('post.pojInfoArr/a');
    $result = db('sf_proj')->where('projid', $projid)->update($projInfoArr);
    return $result;
  }

  public function updateProgContent(){
    $projid = input('post.projid/d');
    $stepid = input('post.stepid/d');
    $content = input('post.content/s');
    $map = array(
      'projid' => $projid,
      'stepid' => $stepid
    );
    $result = db('sf_proj_prog')->where($map)->update(['content' => $content]);
    return $result;
  }
  
  public function uploadProgImage (){
    $projid = input('post.projid/d');
    $stepid = input('post.stepid/d');
    $tmpName = $_FILES['file']['tmp_name'];
    $data = file_get_contents($tmpName);
    $fileName = "proj$projid"."step$stepid.png";
    $path = __DIR__ . "/../../../public/static/progress/$fileName";
    $result = file_put_contents($path, $data);
    $url = "https://www.kingco.tech/static/progress/$fileName";
    $map = array(
      'projid' => $projid,
      'stepid' => $stepid
    );
    db('sf_proj_prog')->where($map)->update(['url'=>$url]);
    return $result;
  }
}
