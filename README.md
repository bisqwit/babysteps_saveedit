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
