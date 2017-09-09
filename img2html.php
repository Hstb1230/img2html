<?php

if(!isset($_FILES['img']) || $_FILES['img']['size']<=16) exit; //No files uploaded

$path = $_FILES['img']['tmp_name']; //Add image path, support URL, but need to change some PHP settings
$mime = mime_content_type($path); //Get the file type (not all file types can be obtained through this function)

$fun = 'imagecreatefrom';

if (preg_match_all('/^image\/(jpe?g|png|gif)/m', $mime)) {
  $fun .= 'string';
} else {
  //Read the first 16 bytes of the file data
  $file = fopen($path, 'rb');
  $bin = fread($file, 16);
  fclose($file);
  //Turn hexadecimal
  $hexs = unpack('c16', $bin);
  if(count($hexs)!=16) exit; //The file is not normal
  $hex = '';
  foreach($hexs as $i) $hex .= bin2hex(chr($i)).' ';
  //Start determining the file type
  if($hex=='23 64 65 66 69 6e 65 20 73 72 63 5f 77 69 64 74 '){
    $fun .= 'xbm';
  }elseif(substr($hex,0,8)=='67 64 32' || substr($hex,0,11)=='00 00 18 18') {
    //[67 64 32] - gd2
    //[00 00 18 18] - wbmp
    $fun .= 'string';
  }elseif(substr($hex,24,11)=='57 45 42 50'){
    //52 49 46 46 0e 31 00 00 [57 45 42 50] 56 50 38 20 - webp
    $fun .= 'webp';//need PHP ≥ 5.5
  }elseif(substr($hex,0,5)=='ff ff' && substr($hex,27,11)=='ff ff ff ff'){
    //[ff ff] 02 80 02 80 [00] 01 00 [ff ff ff ff] 04 02 04 - gd
    $fun .= 'gd';
  }else{
    //Because the use of 5.6, so there is no judgment bmp picture (need PHP ≥ 7.2)
    exit; //Unsupported format
  }
}
if(!function_exists($fun)) exit; //The current version of PHP does not support handling this format image

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

if($fun=='imagecreatefromstring') $data = file_get_contents($path);

$src = (isset($data)) ? $fun($data) : $fun($path);

//Scale the image proportionally
//Get the width and height of the source image
$s = getimagesize($path); 
$w = $s['0']; 
$h = $s['1']; 
//Specify the maximum width of the zoom (also possible height)
$max = 200;
//According to the maximum value, calculate the length of the other side, get the picture after the zoom width and height
if($w > $h){
  $h = $h * ($max / $w);
  $w = $max;
}else{
  $w = $w * ($max / $h);
  $h = $max;
}

//Declare a $w wide, $h high true picture resource
$i = imagecreatetruecolor($w, $h); 
//Key functions, parameters (target resource, source, target resource starting coordinates x, y, source resource starting coordinates x, y, target resource width w, h, source resource width w, h)
imagecopyresampled($i, $src, 0, 0, 0, 0, $w, $h, $s['0'], $s['1']); 
//Release the original resources
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
