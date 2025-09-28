<?php
/* Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit */
/* License: BSD */

function Encode($d)
{
  $dict = is_object($d);
  if($dict)
  {
    // Change stdClass into Array
    $d = json_decode(json_encode($d), true, 512, JSON_OBJECT_AS_ARRAY);
  }
  elseif(is_array($d))
  {
    foreach($d as $k=>$v)
      if($k != (string)(int)$k)
      {
        $dict = true;
        break;
      }
  }
  if($dict)
  {
    // Dict
    if(count($d) <= 15)
      $r = chr(0x80 + count($d));
    else
      $r = chr(0xDE) . chr(count($d) >> 8) . chr(count($d) & 0xFF);
    foreach($d as $k=>$v)
    {
      $r .= Encode($k);
      $r .= Encode($v);
    }
    return $r;
  }
  if(is_string($d))
  {
    if(preg_match('@^byte (?:0x)?([0-9A-F]*)$@', $d, $mat))
      return chr(hexdec($mat[1]));
    if(strlen($d) >= 0x20)
      print("ERROR: Don't know how to encode strings longer than 31 characters\n");
    return chr(0xA0 + strlen($d)) . $d;
  }
  if(is_array($d))
  {
    if(count($d) <= 15)
      $r = chr(0x90 + count($d));
    else
      $r = chr(0xDC) . chr(count($d) >> 8) . chr(count($d) & 0xFF);
    foreach($d as $v)
      $r .= Encode($v);
    return $r;
  }
  if(is_float($d))
  {
    return chr(0xCA) . pack('G', $d);
  }
  if(is_int($d))
  {
    if($d >= 0 && $d <= 0x7F)
      return chr($d);
    elseif($d >= 0 && $d <= 0xFF)
      return chr(0xCC) . chr($d);
    elseif($d >= 0 && $d <= 0xFFFF)
      return chr(0xCD) . chr($d >> 8) . chr($d & 0xFF);
    else
    {
      print("ERROR: For integers, I only know the encoding for 0..65535. Don't know how $d works.\n");
      // Resort to encoding as a float
      return chr(0xCA) . pack('G', $d);
    }
  }
  print("ERROR: Unknown data type in {$d}\n");
}

if($argc < 2)
{
  $argv[] = '-h';
}
$outfn = '';
$force_overwrite = 0;
$accept_flags    = true;
$fake_write      = false;
foreach($argv as $a)
{
  if($a[0] == '-' && $accept_flags)
  {
    if($a == '-') $outfn = 'php://stdout';
    elseif($a == '--') { $accept_flags = false; }
    else for($n=1; $n<strlen($a); ++$n)
      switch($a[$n])
      {
        case 'f': $force_overwrite = 1; break;
        case 'i': $force_overwrite = -1; break;
        case 'n': $fake_write = true; break;
        default: print("Unknown option. ");
        case 'h':
          print("Usage: php writesave.php [-ifn] savefilename.sav < savedata.json\n");
          print("  -i Prompt before overwriting\n");
          print("  -f Always 'yes'\n");
          print("  -n Dry-run: Do not write any files\n");
          exit;
      }
  }
  else
    $outfn = $a;
}

$r = file_get_contents('php://stdin');
/* Strip comments */
$r = preg_replace('@/[*].*[*]/@m', '', $r);
/* Remove any occurrences of comma before ] or } */
$r = preg_replace('@,+\s*([\]\}])@', '\1', $r);

$data = json_decode($r);
$s = encode($data);
if($fake_write)
{
  print("Would write to $outfn:\n");
  $p = popen('hexdump -C', 'w');
  fwrite($p, $s);
  pclose($p);
}
else
{
  if(file_exists($outfn))
    switch($force_overwrite)
    {
      case -1:
      case 0:
        print("Output file already exists, cancelling\n");
        exit;
      case 1:
        break;
    }
  file_put_contents($outfn, $s);
}
