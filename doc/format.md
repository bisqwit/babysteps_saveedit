# Save file format

The save format is a serialized representation of a single expression.
According to current understanding, it has the following interpretation:

Expression is:
| Byte value $$b_0$$ (hex) | Meaning |
| -- | -- |
| 00..7F | Integer in range $$0–127$$.
| 80..8F | Dictionary with $$n = b_0-\text{0x}80$$ items. What follows is $$n$$ items, each of which is a pair of expressions: The first is key, the second is the value corresponding to that key.
| 90..9F | Tuple with $$n = b_0-\text{0x}90$$ items. What follows is a list of $$n$$ expressions.
| A0..BF | An ASCII string with $$n = b_0-\text{0xA}0$$ characters. What follows is $$n$$ bytes of string data.
| C0 | Could be NULL
| C2 | Boolean: false
| C3 | Boolean: true
| CA | What follows is 4 bytes: A 32-bit float encoded in big-endian format.
| CB | What follows is 8 bytes: A 64-bit float encoded in big-endian format. (Supported, but NOT USED by the game. All coordinate / angle data is 32-bit by default in the game. If you rewrite the save using 64-bit floats, the game will keep on using 64-bit coordinates and will continue to write them as 64-bit in subsequential saves.)
| CC | What follows is 1 byte: An unsigned 8-bit integer. For values $$<128$$, 00..7F is used instead.
| CD | What follows is 2 bytes: An unsigned 16-bit integer encoded in big-endian format. For values $$<256$$, CC is used instead.
| CE | What follows is 4 bytes: An unsigned 32-bit integer encoded in big-endian format. For values $$<65536$$, CD is used instead. Fun fact: The step counter is a 32-bit integer. It overflows back to $$0$$ after $$4294967295$$ steps.
| DC | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. After that, a tuple with $$n$$ items. See encoding of tuple above. For $$n<16$$, 90..9F is used instead.
| DE | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. After that, a dictionary with $$n$$ items. See encoding of dictionary above. For $$n<16$$, 80..8F is used instead.
| other | Other values have not been observed so far.

The actual savestate currently has the following format:
* 25-element list of:
    1. List of flags, each is a string
    1. Integer: chapter number
    1. byte 0xC0 of unknown meaning — the game ignores this field even if you write here some other type value such as float or string
    1. Three-element tuple: Player coordinates (three floats)
    1. Float: XZ angle of Nate’s pelvis
    1. Float: Something related to Nate’s angle
    1. Unknown 5-element tuple, possibly lantern-related, containing:
        * Two instances of unknown boolean values
        * An integer of unknown meaning
        * Two 3-element tuples containing coordinates
    1. Integer: Number of skipped cutscenes
    1. Boolean of unknown meaning
    1. Float: Number of seconds played
    1. Two instances of byte 0xC0 of unknown meaning — the game does NOT ignore them
    1. An integer of unknown meaning
    1. A dictionary containing:
        * For every movable item, key = item’s name and value is a two-element tuple, containing:
            * Three-element tuple: Item’s coordinates
            * Four-element tuple: Item’s rotation angle (quaternion)
    1. A dictionary containing:
        * For every carriable item, key = item’s name and value is integer 0 or 1, indicating whether the item is being carried presently. For the sunglasses, the value appears to be 3 instead of 1.
    1. A dictionary of unknown meaning
    1. byte 0xC0 of unknown meaning — the game ignores this field even if you write here some other type value such as float or string
    1. A dictionary containing item-specific data
    1. Another dictionary containing item-specific data
    1. Boolean value: false if Nate has balance, true if he is falling
    1. Three-element tuple of floats, of unknown purpose (possibly Nate’s velocity vector)
    1. An integer of unknown meaning, appears to correlate with the chapter number
    1. Dictionary: Key = audiokey_timesplayed, value = integer
    1. Dictionary: Key = audiokey_lastplay, value = string datetime
    1. Integer: The number of steps taken
    
It is not rare for a game to use a straightforward serialization of game
data for its savestates. That’s what many Unreal Engine games do too, for
example The Talos Principle 2 and Mirror’s Edge.
