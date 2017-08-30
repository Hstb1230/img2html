<?php

if(!isset($_FILES['img']) || $_FILES['img']['size']<=16) exit; //未上传文件

$path = $_FILES['img']['tmp_name']; //添加图片路径，支持url，但也需要设置某些参数
$mime = mime_content_type($path); //获取文件类型，但并不是所有文件类型都可以获取到

$fun = 'imagecreatefrom';

if (preg_match_all('/^image\/(jpe?g|png|gif)/m', $mime)) {
  $fun .= 'string';
} else {
  //读取文件的前16字节数据
  $file = fopen($path, 'rb');
  $bin = fread($file, 16);
  fclose($file);
  //转16进制
  $hexs = unpack('c16', $bin);
  if(count($hexs)!=16) exit; //文件不正常
  $hex = '';
  foreach($hexs as $i) $hex .= bin2hex(chr($i)).' ';
  //判断文件类型
  if($hex=='23 64 65 66 69 6e 65 20 73 72 63 5f 77 69 64 74 '){
    $fun .= 'xbm';
  }elseif(substr($hex,0,8)=='67 64 32' || substr($hex,0,11)=='00 00 18 18') {
    //[67 64 32] - gd2
    //[00 00 18 18] - wbmp
    $fun .= 'string';
  }elseif(substr($hex,24,11)=='57 45 42 50'){
    //52 49 46 46 0e 31 00 00 [57 45 42 50] 56 50 38 20 - webp
    $fun .= 'webp';//需要PHP ≥ 5.5
  }elseif(substr($hex,0,5)=='ff ff' && substr($hex,27,11)=='ff ff ff ff'){
    //[ff ff] 02 80 02 80 [00] 01 00 [ff ff ff ff] 04 02 04 - gd
    $fun .= 'gd';
  }else{
    //因制作的时候使用5.6，所以未支持bmp图片(需PHP 7.2以上)
    exit; //不支持的格式
  }
}
if(!function_exists($fun)) exit; //当前PHP版本不支持处理此格式图片

?>
<html><style>body{margin:0px;padding:0px;text-align:center;line-height:6px;letter-spacing:1px;font-size:0.1%;background-color: #000000;font-family: monospace;}</style>
<!--
                                 ___      __       __                  ___
 __                            /'___`\   /\ \     /\ \__              /\_ \
/\_\     ___ ___       __     /\_\ /\ \  \ \ \___ \ \ ,_\    ___ ___  \//\ \
\/\ \  /' __` __`\   /'_ `\   \/_/// /__  \ \  _ `\\ \ \/  /' __` __`\  \ \ \
 \ \ \ /\ \/\ \/\ \ /\ \L\ \     // /_\ \  \ \ \ \ \\ \ \_ /\ \/\ \/\ \  \_\ \_
  \ \_\\ \_\ \_\ \_\\ \____ \   /\______/   \ \_\ \_\\ \__\\ \_\ \_\ \_\ /\____\
   \/_/ \/_/\/_/\/_/ \/___L\ \  \/_____/     \/_/\/_/ \/__/ \/_/\/_/\/_/ \/____/
                       /\____/
                       \_/__/
--->
<body>
<?php
/* 保留的HTML代码
<meta charset="utf-8">
line-height: 10px;//letter-spacing: 1px;//font-size: 1px;
*/

if($fun=='imagecreatefromstring') $data = file_get_contents($path);

$src = (isset($data)) ? $fun($data) : $fun($path);

//按比例缩放图片
//取得源图片的宽度和高度 
$s = getimagesize($path); 
$w = $s['0']; 
$h = $s['1']; 
//指定缩放出来的最大的宽度（也有可能是高度） 
$max = 200;
//根据最大值，算出另一个边的长度，得到缩放后的图片宽度和高度 
if($w > $h){
  $h = $h * ($max / $w);
  $w = $max;
}else{
  $w = $w * ($max / $h);
  $h = $max;
}

//声明一个$w宽，$h高的真彩图片资源 
$i = imagecreatetruecolor($w, $h); 
//关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h） 
imagecopyresampled($i, $src, 0, 0, 0, 0, $w, $h, $s['0'], $s['1']); 
//释放原有资源
imagedestroy($src);
$Y = imagesy($i);
$X = imagesx($i);
for ($y=0;$y<$Y;$y++) {
  $last_rgb = 0;
  for ($x=0;$x<$X;$x++) {
    $rgb = imagecolorat($i,$x,$y);
    if($last_rgb != $rgb) {
      if($x) echo '</a>';
      ?><a<?php
      if($rgb){ ?> style="color:#<?php echo dechex($rgb); ?>"<?php } ?>>0<?php
    }else{ 
  ?>0<?php  
    }
    $last_rgb = $rgb;
  }
  ?></a><br \>
<?php
}
imagedestroy($i);
?>
</body></html>