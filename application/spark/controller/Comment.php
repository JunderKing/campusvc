<?php
namespace app\spark\controller;

use think\Db;

class Comment 
{
  public function addCmnt(){
    $dataArr = array(
      'projid' => input('post.projid/d'),
      'cmntorid' => input('post.uid/d'),
      'content' => input('post.content/s'),
    );
    $cmntid = db('sf_proj_cmnt')->insertGetId($dataArr);
    return $cmntid;
  }

  public function addMentorCmnt(){
    $dataArr = array(
      'projid' => input('post.projid/d'),
      'cmntorid' => input('post.uid/d'),
      'content' => input('post.content/s'),
      'tscore' => input('post.tscore/d'),
      'ascore' => input('post.ascore/d'),
      'bscore' => input('post.bscore/d'),
      'cscore' => input('post.cscore/d'),
    );
    $cmntid = db('sf_proj_cmnt')->insertGetId($dataArr); 
    return $cmntid;
  }

  public static function getProjCmnt($projid){
    if (!$projid) {
      $projid = input('post.projid/d');
    }
    $sql = "SELECT * FROM cp_sf_proj_cmnt AS A, cp_user AS B WHERE A.projid=$projid AND A.cmntorid=B.uid";
    $cmnts = Db::query($sql);
    $sql = "SELECT A.*, B.nick_name as nickName FROM cp_sf_proj_reply AS A, cp_user AS B WHERE A.replierid=B.uid AND A.cmntid IN (SELECT cmntid FROM cp_sf_proj_cmnt WHERE projid=$projid)";
    $replies = Db::query($sql);
    for ($index= 0; $index < count($cmnts); $index++) {
      $cmnts[$index]['replies'] = array();
      foreach($replies as $item) {
        if ($cmnts[$index]['cmntid']===$item['cmntid']) {
          $cmnts[$index]['replies'][] = $item;
        }
      }
    }
    return $cmnts;
  }

  public function addReply(){
    $dataArr = array(
      'cmntid' => input('post.cmntid/d'),
      'replierid' => input('post.uid/d'),
      'content' => input('post.content/s'),
    );
    $replyid = db('sf_proj_reply')->insertGetId($dataArr);
    return $replyid;
  }
}
