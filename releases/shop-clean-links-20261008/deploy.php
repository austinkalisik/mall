<?php
if(PHP_SAPI!=='cli')exit('CLI only');
$root='/home/nextgenpng/public_html/shop.nextgenpng.net';
$m=json_decode(file_get_contents(__DIR__.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$guards=json_decode(file_get_contents(__DIR__.'/baselines.json'),true,512,JSON_THROW_ON_ERROR);
foreach($m as $x){$f=$x['file'];$target=$root.'/'.$f;if(is_link($target)||!is_file($target))throw new Exception('Unexpected live target: '.$f);if(hash_file('sha256',__DIR__.'/payload/'.$f)!==$x['sha256'])throw new Exception('Invalid payload: '.$f);$h=hash('sha256',str_replace("\r\n","\n",file_get_contents($target)));if($h!==$guards[$f]&&hash_file('sha256',$target)!==$x['sha256'])throw new Exception('Live file differs; stopped: '.$f);}
$ht=$root.'/.htaccess';if(is_link($ht))throw new Exception('Unexpected .htaccess symlink');$old=is_file($ht)?file_get_contents($ht):'';$block=file_get_contents(__DIR__.'/routes.htaccess');if(str_contains($old,'# BEGIN NEXTGEN B2C ROUTES')){if(!str_contains(str_replace("\r\n","\n",$old),$block))throw new Exception('Routing block differs; stopped');$new=$old;}else{$new=$block."\n".$old;}
$backup='/home/nextgenpng/nextgen-b2c-private/shop-links-backup-'.date('Ymd-His');if(!mkdir($backup,0700,true))throw new Exception('Backup failed');
foreach($m as $x){$f=$x['file'];$b=$backup.'/'.$f;if(!is_dir(dirname($b)))mkdir(dirname($b),0700,true);if(!copy($root.'/'.$f,$b))throw new Exception('Backup failed: '.$f);}if(is_file($ht)&&!copy($ht,$backup.'/.htaccess'))throw new Exception('Routing backup failed');
usort($m,fn($a,$b)=>($a['file']==='index.php'?1:0)<=>($b['file']==='index.php'?1:0));
foreach($m as $x){$target=$root.'/'.$x['file'];$tmp=tempnam(dirname($target),'.shop-links-');if(!copy(__DIR__.'/payload/'.$x['file'],$tmp))throw new Exception('Copy failed');chmod($tmp,0644);if(!rename($tmp,$target))throw new Exception('Install failed');}
$tmp=tempnam($root,'.shop-routes-');if(file_put_contents($tmp,$new)!==strlen($new))throw new Exception('Routing write failed');chmod($tmp,0644);if(!rename($tmp,$ht))throw new Exception('Routing install failed');echo "B2C clean links deployed. Backup: $backup\n";
