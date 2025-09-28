# Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit
# License: BSD
import sys
import re
import json
import struct
import os
import binascii

def encode(data):
    if isinstance(data, dict):
        n = len(data)
        if n <= 15:
            r = bytes([0x80 + n])
        else:
            r = bytes([0xDE, (n >> 8) & 0xFF, n & 0xFF])
        for k, v in data.items():
            r += encode(k)
            r += encode(v)
        return r
    elif isinstance(data, list):
        n = len(data)
        if n <= 15:
            r = bytes([0x90 + n])
        else:
            r = bytes([0xDC, (n >> 8) & 0xFF, n & 0xFF])
        for v in data:
            r += encode(v)
        return r
    elif isinstance(data, str):
        match = re.match(r'^byte\s*(?:0x)?([0-9A-Fa-f]+)$', data, re.IGNORECASE)
        if match:
            hex_val = match.group(1)
            try:
                byte_val = int(hex_val, 16)
                return bytes([byte_val])
            except ValueError:
                print("ERROR: Invalid hex in byte string\n")
                return b''
        utf8_data = data.encode('utf-8')
        if len(utf8_data) >= 32:
            print("ERROR: Don't know how to encode strings longer than 31 characters\n")
            return b''
        return bytes([0xA0 + len(utf8_data)]) + utf8_data
    elif isinstance(data, float):
        return bytes([0xCA]) + struct.pack('!f', data)
    elif isinstance(data, int):
        if 0 <= data <= 0x7F:
            return bytes([data])
        elif 0 <= data <= 0xFF:
            return bytes([0xCC, data])
        elif 0 <= data <= 0xFFFF:
            return bytes([0xCD, (data >> 8) & 0xFF, data & 0xFF])
        else:
            print(f"ERROR: For integers, I only know the encoding for 0..65535. Don't know how {data} works.\n")
            return bytes([0xCA]) + struct.pack('!f', data)
    else:
        print(f"ERROR: Unknown data type in {data}\n")
        return b''

def main():
    data = sys.stdin.read()
    
    data = re.sub(r'/\*.*?\*/', '', data, flags=re.DOTALL)
    data = re.sub(r',+\s*([\]\}])', r'\1', data)
    
    try:
        data = json.loads(data)
    except json.JSONDecodeError as e:
        print(f"JSON decode error: {e}")
        sys.exit(1)
    
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('-i', action='store_true', help='Prompt before overwriting')
    parser.add_argument('-f', action='store_true', help='Always overwrite')
    parser.add_argument('-n', action='store_true', help='Dry-run')
    parser.add_argument('output', nargs='?', help='Output filename')
    
    args = parser.parse_args()
    
    force_overwrite = -1 if args.i else (1 if args.f else 0)
    outfn = args.output
    
    if outfn == '-':
        outfn = 'php://stdout'
    
    s = encode(data)
    
    if args.n:
        print(f"Would write to {outfn}:")
        print(binascii.hexlify(s).decode('ascii'))
    else:
        if outfn == 'php://stdout':
            sys.stdout.buffer.write(s)
        else:
            if os.path.exists(outfn) and force_overwrite != 1:
                if force_overwrite == -1:
                    pass
                print("Output file already exists, cancelling")
                sys.exit(1)
            with open(outfn, 'wb') as f:
                f.write(s)

if __name__ == '__main__':
    main()
