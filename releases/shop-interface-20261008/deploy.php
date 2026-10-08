<?php
if (PHP_SAPI !== 'cli') exit('CLI only');
$root='/home/nextgenpng/public_html/shop.nextgenpng.net/b2c-preview';
$manifest=json_decode(file_get_contents(__DIR__.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$baselines=json_decode(file_get_contents(__DIR__.'/baselines.json'),true,512,JSON_THROW_ON_ERROR);
foreach ($manifest as $item) {
 $file=$item['file']; $source=__DIR__.'/payload/'.$file; $target=$root.'/'.$file;
 if(hash_file('sha256',$source)!==$item['sha256']) throw new Exception('Invalid payload: '.$file);
 if(is_link($target)) throw new Exception('Symlink target: '.$file);
 if(is_file($target)) {
  $hash=hash('sha256',str_replace("\r\n","\n",file_get_contents($target)));
  if($hash!==($baselines[$file]??'') && hash_file('sha256',$target)!==$item['sha256']) throw new Exception('Live file differs; stopped: '.$file);
 } elseif(isset($baselines[$file])) throw new Exception('Missing live file: '.$file);
}
$backup='/home/nextgenpng/nextgen-b2c-private/shop-interface-backup-'.date('Ymd-His');
if(!mkdir($backup,0700,true)) throw new Exception('Backup failed');
foreach($manifest as $item){
 $file=$item['file'];$target=$root.'/'.$file;
 if(is_file($target)){if(!is_dir(dirname($backup.'/'.$file)))mkdir(dirname($backup.'/'.$file),0700,true);if(!copy($target,$backup.'/'.$file))throw new Exception('Backup copy failed');}
}
foreach($manifest as $item){$target=$root.'/'.$item['file'];$tmp=tempnam(dirname($target),'.shop-ui-');if(!copy(__DIR__.'/payload/'.$item['file'],$tmp))throw new Exception('Copy failed');chmod($tmp,0644);if(!rename($tmp,$target))throw new Exception('Install failed');}
echo "Shop interface deployed. Backup: $backup\n";
