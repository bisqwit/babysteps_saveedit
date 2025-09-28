# Baby Steps  savestate manipulation tools

Tools for writing and reading Baby Steps save files.

These files are located on Linux in:

`.steam/steamapps/compatdata/1281040/pfx/drive_c/users/steamuser/AppData/LocalLow/DefaultCompany/Babysteps/#/save`

where `#` is some number, and in Windows in:

`C:\users\&\AppData\LocalLow\DefaultCompany\Babysteps\#\save`

where `&` is your Windows username.

## Requirements

You need PHP commandline interpreter.

To dump save contents:

`php dumpsave.php save0.sav > dump.json`

To inspect and edit save contents:

Edit `dump.json` 

To write back the changed contents:

`php writesave.php [options] save0.sav < dump.json`

Options: `-f` = force overwrite, `-n` = dry-run, `-i` = don't overwrite

## License stuff

`/* Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit */`
`/* License: BSD */`

## TIPS

### Did you lose your chicken bird?
### Did your \<item\> fall through the floor or through the entire world mesh?
### Did you forget where you placed your irreplaceable item?

Dump the save, find the item (such as KeyringTrophy) or the bird (Huey and/or Dewie),
and change their coordinates (the first 3-float tuple)
into the same values as the player character's coordinates.
