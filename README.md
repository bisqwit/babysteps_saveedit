# Baby Steps savestate manipulation tools

Baby Steps is a game by 
Gabe Cuzzillo, Maxi Boch, and Bennett Foddy, published by Devolved Digital.

This repository contains tools for reading and writing Baby Steps save files.

## Requirements

**For running with Python instead,** see the bottom of this document.

You need the PHP commandline version. For example, `apt install php-cli` in Linux.

## Running

To dump the save contents (creates an editable text file that can be used to inspect and modify the save data):

`php dumpsave.php save0.sav > dump.json`

To inspect and edit the save contents:

Edit `dump.json` 

To write back the changed contents:

`php writesave.php [options] save0.sav < dump.json`

Options: `-f` = force overwrite, `-n` = dry-run, `-i` = don't overwrite

## Where to find your savestates

On Linux, these files are located in:

`$HOME/.steam/steamapps/compatdata/1281040/pfx/drive_c/users/steamuser/AppData/LocalLow/DefaultCompany/Babysteps/#/save/`

where `#` is some number, and in Windows in:

`C:\Users\\%USERNAME%\AppData\LocalLow\DefaultCompany\BabySteps\#\save` .

## The type of modifications you can make

You can edit, including, but not limited to:

* List of unique flags you have activated
    * Such as “JimCaveMeeting” if you talked to Jim in the first cave, “stetson” if you took the stetson hat, or “FruitPapaya” if you ate the papaya fruit.
* Current chapter number (0–9)
    * Chapters in order are: Poison Swamp, Donkey Farm, Poison {Forest, Slopes, Desert, Castle, Hills, Mines, Icefields}, Complete.
* Player’s world coordinates (XYZ) at 32-bit floatingpoint precision
    * See [the coordinate system](#the-coordinate-system) for details
* Locations (XYZ) and orientations (quaternion) of every movable item
* For every carriable item, flag indicating whether Nate is carrying it currently
* Some item-specific data, such as melt-percent for the icecream
* Number of seconds played
* Number of steps taken

See [the savestate format documentation](doc/format.md) for more details on what’s in there.

## TIPS

### To quicksave (create a checkpoint):

1. Exit to main menu
2. Switch to another save slot (e.g. if you are running alpha, switch to beta)
3. Backup the savefile by copying the savefile to safety (save0.sav for alpha, save1.sav for beta, save2.sav for gamma)
    * Could do e.g. `zip -9 safety.zip *.sav`
4. In the game, switch back to the original save slot
5. Resume game

You can probably skip steps 2 and 4.
You should not skip steps 1 and 5,
because then you risk backing up a broken file
that is half-way being updated,
as the game continuously updates the savefile
in non-atomic fashion.

### To load a quicksave (quickload? / restore a checkpoint):

1. Exit to main menu
2. Switch to another save slot (e.g. if you are running alpha, switch to beta)
3. Overwrite the savefile with the file you copied to safety earlier
    * Could do e.g. `unzip safety.zip save0.sav`
4. In the game, switch back to the original save slot
5. Resume game

### To make particular modifications to the state of the currently running game:

1. Exit to main menu
2. Switch to another save slot (e.g. if you are running alpha, switch to beta)
3. Modify the save while the game is not looking:
    * Dump the save contents using `php dumpsave.php filename > savedump.json` (save0.sav for alpha, save1.sav for beta, save2.sav for gamma)
    * Edit the savedump contents `$EDITOR savedump.json` or `notepad savedump.json`, save the changes
    * Write back the save contents using `php writesave.php filename < savedump.json`
        * Or `php writesave.php -f filename < savedump.json` if you know you want to overwrite the file
        * Or `php writesave.php -n filename < savedump.json` if you just want a hexdump of what *would* be written without actually performing any changes
4. In the game, switch back to the original save slot
5. Resume game

### Did you lose your chicken pet bird? \\ Did your keys fall through the floor? \\ Did you lose an item and don’t know where it is, or it’s too far away?

1. Dump the save (run dumpsave as explained in the [running](#running) chapter).
2. Find the item (such as `KeyringTrophy`) or the bird (such as `Huey`) in the list of movable items, and change their coordinates (the first 3-float tuple) into the same values as the player character's coordinates (optionally $$±0.1$$).
    * If the item is a carriable item and you want Nate to be carrying it, also change the corresponding `0` to `1` in the is-being-carried section.
      Note that it’s not enough to change this flag: The item also has to be at reach of Nate.
3. Write the save (run writesave as explained in the [running](#running) chapter).

### Broke the vase, and want to mend it (unbreak)?

Remove `VaseBroken` from the first list of flags. Just delete that line (from the dump), and
update (write) the save.

### Ice cream is almost gone and there’s still so much distance to cover, help?

Find `MeltPercent` and changed the value into `0.0`. Brand new ice cream!

### I want to cheat and attain the two hardest achievements.

* Change Nate’s coordinates to: $$218.5,\    972.3,\    2804$$
* If you don’t yet have the pedometer:
    * Add `Pedometer` to the list of flags.
    * Add `Pedometer` to movable items (copy an existing item, rename it), and change its coordinates to the same as Nate’s.
    * Add `Pedometer` also the list of carriables (is-being-carried), and set it to 1.
* If you don’t yet have the alarm clock:
    * Same instructions as above, but for `AlarmClock` rather than `Pedometer`.
* Change the number of steps to less than $$10000$$.
* Change the number of seconds played to less than $$5400$$.

### Move Nate on the roof of whatever you're currently standing inside.

If the roof is directly above Nate,
increment Nate’s Y coordinate by an applicable amount such as 10, 30, or 100.
If Nate ends up inside a rock or other solid surface, the game will automatically place him atop said rock, so it’s pretty safe to guess an adjustment value.
However, if Nate ends up high in air, he will fall, and likely hurl his carriable items far away like he always does for no reason.

### I loaded the save, but Nate was flung at astronomical speeds in a random direction. Why?

This happens sometimes when carrying large items such as the ice cream,
even with legitimate saves with no modifications. The physics engine seems to
glitch, thinking that these two physics items (Nate and the item) collide.
In an attempt to mend the situation, the physics engine creates
a force that pushes the two colliding objects apart.

Just rewrite the same save using the same dump, but when loading the save,
try lifting a leg or leaning in some direction. This usually seems to help.

## The coordinate system

* The coordinate unit is approximately such that a change of $$±1$$ equals $$1$$ meter of distance.
* X coordinates range from $$0 ≤ X < 512$$.
  The game automatically wraps the coordinates in this range.
* Y coordinates range approximately $$100–1200$$ in a normal gameplay,
  and the Z coordinates approximately $$70–3300$$.
  The coordinates actually
  wrap such that a full loop of the game increases $$Y$$ by $$1300$$ and $$Z$$ by $$3328$$.
  That is, $$(X,\   Y,\   Z)$$ and $$(X,\   Y+1300n,\   Z+3328n)$$
  correspond the same location for any $$n ∈ ℤ, (X,Y,Z) ∈ ℝ^3$$.
  Movable items do *not* follow this mirroring.
* If $$|Z| ≥ 10000$$ (exact threshold unknown, probably $$8192$$),
  then there will be glitches with collisions.
* The starting coordinates are approximately $$(473,\   119,\   72)$$.
* The ending coordinates are approximately $$(218,\   971,\   2820)$$.

## Python version

Alternatives that depend on Python instead of PHP are provided.

Running:

`python3 dumpsave.py save0.sav > dump.json`

and

`python3 writesave.py [options] save0.sav < dump.json`

### AI-generated

The Python code is automatically translated
from the PHP code by a locally hosted AI.
<details>
<summary>See details…
</summary>

Particularly,
[`qwen3:30b-a3b-thinking-2507-q8_0`](https://ollama.com/library/qwen3),
using the following two prompts for the dump program
and for the write program respectively
(with some subtle prodding to guide towards desired behavior):

> Translate this PHP code into Python please. Caveats: 1. In python, s[a] where s is string and a is integer does not result in integer, it results in a substring. 2. The json.dumps in Python does not accept "bytes" type data fields. Therefore, you need to pay special attention that the key types A0..BF generate strings, not bytes. 3. The PHP program reads the file contents as a string and interprets it byte by byte. In Python, if you do the same, you will get UTF8 decoding errors; you must convert the file data into bytes rather than a string. 4. Do not attempt to run the python code you generate, because you will be confused by its output and get sidetracked from the actual task. I repeat, DO NOT RUN python tool. DO NOT. DO NOT USE TOOLS. REMEMBER, DO NOT USE TOOLS. DO NOT USE TOOLS!! \`\`\`[php code is here]\`\`\`

> Translate this PHP code into Python please. Caveats: 1. Note that the encode() function creates a raw byte stream. 2. Do not attempt to run the python code you generate, because you will be confused by its output and get sidetracked from the actual task. I repeat, DO NOT RUN python tool. DO NOT. DO NOT USE TOOLS. REMEMBER, DO NOT USE TOOLS. DO NOT USE TOOLS!! \`\`\`[php code is here]\`\`\`

</details>
I have verified that the AI-generated Python versions produce equivalent outputs
compared to the PHP versions, although I have not verified subtle behaviors like file
clobbering.

## License stuff

`/* Copyright (C) 2025 Joel Yliluoma - https://iki.fi/bisqwit */`
`/* License: BSD */`

If you want to support my work, some options are listed at:
[https://bisqwit.iki.fi/donate.html](https://bisqwit.iki.fi/donate.html)
