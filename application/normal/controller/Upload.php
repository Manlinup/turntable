<?php

namespace app\normal\controller;

use think\Controller;
use think\Image;

class Upload extends Controller{


    /**
     * 图片上传
     * @return \think\response\Json
     */
    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('images');
        if (empty($file)) {
            return resultArray(['error' =>'没有上传图片']);
        }
        // 移动到根目录/public/uploads/ 目录下
        $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info){
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $filePath = $http_type.$_SERVER['HTTP_HOST'].'/public' . DS . 'uploads'.DS.$info->getSaveName();
            $getInfo = $info->getInfo();
            $imgInfo = Image::open($info);
            $savePath = $info->getSaveName();
            $pathThumb = explode('.',$savePath);
            $pathThumb[0] = $pathThumb[0].'_thumb';
            $pathName = implode('.',$pathThumb);
            $pathName = 'uploads'.DS.$pathName;
            // 另存一份压缩后的图片
            $info = @$imgInfo->thumb(100, 50)->save($pathName);
            //获取图片的原名称
            $name = $getInfo['name'];
            $data = [
                'path' => $filePath,
                'path_thumb' => $http_type.$_SERVER['HTTP_HOST'].'/public/'.$pathName,
                'name' => $name,
                'created' => date('Y-m-d H:i:s'),
                'msg' => '图片上传成功'
            ];
            return resultArray(['data' => $data]);
        }else{
            // 上传失败获取错误信息
            return resultArray(['error' => $file->getError()]);
        }
    }


}