<?php
namespace app\spark\controller;

class File
{
  public function uploadImage (){
    $projid = input('post.projid/d');
    $stepid = input('post.stepid/d');
    $tmpName = $_FILES['file']['tmp_name'];
    $data = file_get_contents($tmpName);
    $fileName = "$projid" . "_$stepid.jpg";
    $path = __DIR__ . "/../../../public/static/$fileName";
    $result = file_put_contents($path, $data);
    return $result;
  }
}
