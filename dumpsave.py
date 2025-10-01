# Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit
# License: BSD
import sys
import json
import re
import struct
from datetime import datetime
import os

global counters
counters = []

def DumpCounters():
    global counters
    s = json.dumps(counters)
    if s == '[0, 0]':
        return "-- Event or item unlocking flags, in order of activation"
    if s == '[1]':
        return "-- Chapter number (0=poison swamp, 9=complete)"
    if s == '[2]':
        return "-- Unknown byte -- apparently ignored by the game"
    if s == '[3]':
        return "-- Player coordinates (X,Y,Z). Unit ~ meters"
    if s == '[3, 0]':
        return "-- X coordinate (+ = east. Range: 0 <= x < 512. Wraps at either side.)"
    if s == '[3, 1]':
        return "-- Y coordinate (+ = up)"
    if s == '[3, 2]':
        return "-- Z coordinate (+ = north)"
    if s == '[4]':
        return "-- Nate looking direction (XZ angle)"
    if s == '[5]':
        return "-- Daytime progress (0=morning, 1=night)"
    if s == '[6]':
        return "-- Unknown structure, possibly lantern-related"
    if s == '[6, 2]':
        return "-- Unknown counter (seems to be zero if the coordinates are too)"
    if s == '[6, 3]':
        return "-- Unknown coordinates"
    if s == '[6, 4]':
        return "-- Unknown coordinates"
    if s == '[7]':
        return "-- Number of cutscenes skipped"
    if s == '[8]':
        return "-- Unknown boolean"
    if s == '[9]':
        return "-- Number of seconds played"
    if s == '[10]':
        return "-- Unknown byte"
    if s == '[11]':
        return "-- Unknown byte"
    if s == '[12]':
        return "-- Unknown integer"
    if s == '[13]':
        return "-- Known movable items. itemname => coordinates (X,Y,Z) and rotation (quaternion)."
    if s == '[14]':
        return "-- Known movable items, is-being-carried flag for each"
    if s == '[15]':
        return "-- Unknown dict"
    if s == '[16]':
        return "-- Unknown byte -- apparently ignored by the game"
    if s == '[17]':
        return "-- Item-specific data, such as MeltPercent for IceTrophy"
    if s == '[18]':
        return "-- Item-specific data2, such as IceTrophiesGotten (# of ice creams received)"
    if s == '[19]':
        return "-- Nate lost his balance? True=falling, false=stable"
    if s == '[20]':
        return "-- Nate's XYZ velocity vector (maybe?) The game might normalize this first."
    if s == '[21]':
        return "-- Unknown integer, appears to correlate with chapter number"
    if s == '[22]':
        return "-- Audio-related counters"
    if s == '[23]':
        return "-- Audio-related timestamps"
    if s == '[24]':
        return "-- Number of steps taken"
    return ''

def ReadItem():
    global counters, a
    if not hasattr(ReadItem, 'level'):
        ReadItem.level = -1
    ReadItem.level += 1
    try:
        key = s[a[0]]
        a[0] += 1
        
        if 0x00 <= key <= 0x7F:
            return key
        
        if 0x80 <= key <= 0x8F:
            nitems = key - 0x80
            res = {}
            for n in range(nitems):
                if ReadItem.level >= len(counters):
                    counters.extend([None] * (ReadItem.level + 1 - len(counters)))
                counters[ReadItem.level] = n
                q = DumpCounters()
                if q:
                    res[0] = q
                key_val = ReadItem()
                value_val = ReadItem()
                res[key_val] = value_val
            if not res:
                res = {}
            return res
        
        if 0x90 <= key <= 0x9F:
            nitems = key - 0x90
            res = []
            for n in range(nitems):
                if ReadItem.level >= len(counters):
                    counters.extend([None] * (ReadItem.level + 1 - len(counters)))
                counters[ReadItem.level] = n
                q = DumpCounters()
                if q:
                    res.append(q)
                res.append(ReadItem())
            return res
        
        if 0xA0 <= key <= 0xBF:
            len_str = key - 0xA0
            k = s[a[0]:a[0] + len_str]
            a[0] += len_str
            return k.decode('utf-8')
        
        #if key == 0xC0:
        #    a[0] += 1
        #    return None
        
        if key == 0xC2:
            a[0] += 1
            return False
        
        if key == 0xC3:
            a[0] += 1
            return True
        
        if key == 0xCA:
            a[0] += 4
            float_bytes = s[a[0]-4:a[0]]
            return struct.unpack('>f', float_bytes)[0]
        
        if key == 0xCB:
            a[0] += 8
            float_bytes = s[a[0]-8:a[0]]
            return struct.unpack('>d', float_bytes)[0]
        
        if key == 0xCC:
            val = s[a[0]]
            a[0] += 1
            return val
        
        if key == 0xCD:
            a[0] += 2
            bytes_16 = s[a[0]-2:a[0]]
            return struct.unpack('>H', bytes_16)[0]
        
        if key == 0xCE:
            a[0] += 4
            bytes_32 = s[a[0]-4:a[0]]
            return struct.unpack('>I', bytes_32)[0]
        
        if key == 0xDC:
            a[0] += 2
            nitems = struct.unpack('>H', s[a[0]-2:a[0]])[0]
            res = []
            for n in range(nitems):
                if ReadItem.level >= len(counters):
                    counters.extend([None] * (ReadItem.level + 1 - len(counters)))
                counters[ReadItem.level] = n
                q = DumpCounters()
                if q:
                    res.append(q)
                res.append(ReadItem())
            return res
        
        if key == 0xDE:
            a[0] += 2
            nitems = struct.unpack('>H', s[a[0]-2:a[0]])[0]
            res = {}
            for n in range(nitems):
                if ReadItem.level >= len(counters):
                    counters.extend([None] * (ReadItem.level + 1 - len(counters)))
                counters[ReadItem.level] = n
                q = DumpCounters()
                if q:
                    res[0] = q
                key_val = ReadItem()
                value_val = ReadItem()
                res[key_val] = value_val
            if not res:
                res = {}
            return res
        
        return f"byte 0x{key:02X}"
    finally:
        if ReadItem.level < len(counters):
            del counters[ReadItem.level]
        ReadItem.level -= 1

if len(sys.argv) < 2:
    print("Usage: python dumpsave.py savefilename.sav > savedata.json")
    sys.exit(1)

with open(sys.argv[1], 'rb') as f:
    s = f.read()

a = [0]
data = ReadItem()
r = json.dumps(data, indent=2, ensure_ascii=False)
r = re.sub(r'^( *)".*-- (.*)",', r'\1/* \2 */', r, flags=re.MULTILINE)
dumptime = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
savetime = datetime.fromtimestamp(os.path.getmtime(sys.argv[1])).strftime('%Y-%m-%d %H:%M:%S')
savefile = sys.argv[1]

preamble = f'''/* This dump is JSON formatted with comments. */
/* You can add your own comments if you want; they are ignored by writesave.php. */
/* Dump datetime: {dumptime} */
/* Save datetime: {savetime} */
/* Savefile name: {savefile} */
'''

print(preamble + r)
