<?php
/* Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit */
/* License: BSD */
global $counters;

function DumpCounters()
{
  global $counters;
  $s = json_encode($counters);
  switch($s)
  {
    case '[0,0]':  return "-- Event or item unlocking flags, in order of activation";
    case '[1]':    return "-- Chapter number (0=poison swamp, 9=complete)";
    case '[2]':    return "-- Unknown byte";
    case '[3]':    return "-- Player coordinates (X,Y,Z). Unit ~ meters";
    case '[3,0]':  return "-- X coordinate (+ = east. Range: 0 <= x < 512. Wraps at either side.)";
    case '[3,1]':  return "-- Y coordinate (+ = up)";
    case '[3,2]':  return "-- Z coordinate (+ = north)";
    case '[4]':    return "-- Nate looking direction (XZ angle)";
    case '[5]':    return "-- Nate looking direction related? Not sure";
    case '[6]':    return "-- Unknown structure";
    case '[6,2]':  return "-- Unknown counter (seems to be zero if the coordinates are too)";
    case '[6,3]':  return "-- Unknown coordinates";
    case '[6,4]':  return "-- Unknown coordinates";
    case '[6]':    return "-- Unknown structure";
    case '[7]':    return "-- Unknown integer";
    case '[8]':    return "-- Unknown byte";
    case '[9]':    return "-- Number of seconds played";
    case '[10]':   return "-- Unknown byte";
    case '[11]':   return "-- Unknown byte";
    case '[12]':   return "-- Unknown integer";
    case '[13]':   return "-- Known movable items. itemname => coordinates (X,Y,Z) and rotation (quaternion).";
    case '[14]':   return "-- Known movable items, is-being-carried flag for each";
    case '[15]':   return "-- Unknown dict";
    case '[16]':   return "-- Unknown byte";
    case '[17]':   return "-- Item-specific data, such as MeltPercent for IceTrophy";
    case '[18]':   return "-- Item-specific data2, such as IceTrophiesGotten (# of ice creams received)";
    case '[19]':   return "-- Unknown byte";
    case '[20]':   return "-- Nate's XYZ velocity vector";
    case '[21]':   return "-- Unknown integer";
    case '[22]':   return "-- Audio-related counters";
    case '[23]':   return "-- Audio-related timestamps";
    case '[24]':   return "-- Number of steps taken";
  }
  return  '';
  return "-- $s";
}

function ReadItem()
{
  global $a,$b,$s, $counters;
  static $level = -1;
  try {
    ++$level;
    
    $key = ord($s[$a]);
    if($key >= 0x00 && $key <= 0x7F) // Integer (7-bit)
    {
      return ord($s[$a++]);
    }
    if($key >= 0x80 && $key <= 0x8F) // Dict, with N items
    {
      ++$a;
      $nitems = $key - 0x80;
      $res = Array();
      for($n = 0; $n < $nitems; ++$n)
      {
        $counters[$level] = $n;
        $q = DumpCounters(); if(strlen($q)) $res[0] = $q;

        $key = ReadItem();
        $value = ReadItem();
        $res[$key] = $value;
      }
      if(!count($res)) $res = new stdClass;
      return $res;
    }
    if($key >= 0x90 && $key <= 0x9F) // Tuple, with N items
    {
      ++$a;
      $nitems = $key-0x90;
      $res = Array();
      for($n = 0; $n < $nitems; ++$n)
      {
        $counters[$level] = $n;

        $q = DumpCounters(); if(strlen($q)) $res[] = $q;

        $res[] = ReadItem();
      }
      return $res;
    }
    if($key >= 0xA0 && $key <= 0xBF) // String, first byte is length
    {
      $len = $key-0xA0;
      $k = substr($s, $a+1, $len);
      $a += 1+$len;
      return $k;
    }
    if($key == 0xCA) // Float (big-endian 4-byte)
    {
      $a += 5;
      $k = unpack('G', substr($s, $a-4, 4));
      return $k[1];
    }
    if($key == 0xCB) // Double (big-endian 8-byte)
    {
      $a += 9;
      $k = unpack('E', substr($s, $a-8, 8));
      return $k[1];
    }
    // Presumably, CB = double
    if($key == 0xCC) // Integer (8-bit)
    {
      ++$a;
      return ord($s[$a++]);
    }
    if($key == 0xCD) // Integer (16-bit) (big-endian unsigned)
    {
      $a += 3;
      $k = unpack('n', substr($s, $a-2, 2));
      return $k[1];
    }
    if($key == 0xCE) // Integer (32-bit) (big-endian unsigned)
    {
      $a += 5;
      $k = unpack('N', substr($s, $a-4, 4));
      return $k[1];
    }
    // Presumably, CF = 64-bit integer

    if($key == 0xDC) // Tuple, with N items (N = big-endian 16-bit int)
    {
      $a += 3;
      $k = unpack('n', substr($s, $a-2, 2));
      $nitems = $k[1];
      $res = Array();
      for($n = 0; $n < $nitems; ++$n)
      {
        $counters[$level] = $n;

        $q = DumpCounters(); if(strlen($q)) $res[] = $q;

        $res[] = ReadItem();
      }
      return $res;
    }
    if($key == 0xDE) // Dict, with N items (N = big-endian 16-bit int)
    {
      $a += 3;
      $k = unpack('n', substr($s, $a-2, 2));
      $nitems = $k[1];
      $res = Array();
      for($n = 0; $n < $nitems; ++$n)
      {
        $counters[$level] = $n;

        $q = DumpCounters(); if(strlen($q)) $res[0] = $q;

        $key = ReadItem();
        $value = ReadItem();
        $res[$key] = $value;
      }
      if(!count($res)) $res = new stdClass;
      return $res;
    }
    ++$a;
    return sprintf("byte 0x%02X", $key); // Unknown byte
    // Save1:                                            Save0:
    //  List of 25 somethings:                            List of 25 somethings:
    //  List of strings.                                  List of strings.
    //  00 C0                                             04 C0
    //  List of three floats.                             List of three floats.
    //  Two floats (no list)                              Two floats (no list)
    //  List of five somethings:                          List of five somethings:
    //  C2 C2 00                                          C2 C2 00
    //  List of three floats.                             List of three floats.
    //  List of three floats.                             List of three floats.
    //  00 C2                                             03 C2
    //  One float.                                        One float.
    //  C0 C0 00                                          C0 C0 00
    //  80                                                8A: List of ten somethings:
    //                                                      String constant.
    //                                                      List of two:
    //                                                        List of three floats.
    //                                                        List of four floats.
    //                                                    87: List of seven somethings:
    //                                                      String constant.
    //                                                      Byte-integer, or float
    //  80                                                81: List of one somethings:
    //                                                      String constant.
    //                                                      Byte-integer
    //  80
    //  C0
    //  80
    //  80
    //  C2                                                C2
    //  List of three floats.                             List of three floats.
    //  00                                                04
    //  Dict of TIMESPLAYED                               Dict of TIMESPLAYED
    //  Dict of LASTPLAY                                  Dict of LASTPLAY
    //  CC B7                                             CD 95 E0
  }
  finally{
    unset($counters[$level]);
    --$level;
  }
}

if(count($argv) < 2)
{
  print("Usage: php dumpsave.php savefilename.sav > savedata.json\n");
  exit;
}
$s = file_get_contents($argv[1]);
$a = 0;
$b = strlen($s);

$data = ReadItem();
$r = json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION)."\n";

$r = preg_replace('/^( *).*"-- (.*)",/m', '\1/* \2 */', $r);

$dumptime = date('Y-m-d H:i:s');
$savetime = date('Y-m-d H:i:s', filemtime($argv[1]));
$savefile = $argv[1];

$preamble = <<<EOF
/* This dump is JSON formatted with comments. */
/* You can add your own comments if you want; they are ignored by writesave.php. */
/* Dump datetime: $dumptime */
/* Save datetime: $savetime */
/* Savefile name: $savefile */
EOF;
print "$preamble\n$r";

/*
SPOILER: 230,971,2812 for going for walk
    Starting coordinates are around:
      472.9, 145.9 (or 119), 72
    Same at:
      472.9, 1445.8, 3400
    Same at:
      472.9, 2745.8, 6728
    Same at:
      472.9, 4045.8, 10046 -- however, most props lose collision
    Same at:
      472.9, -1154.1, -3256
    Same at:
      472.9, -2454.1, -6584
    Same at:
      472.9, -3754.1, -9912 -- however, most props lose collision
*/
