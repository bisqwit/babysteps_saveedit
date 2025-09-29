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
| C0, C2, C3 | Single byte values of unknown meaning. Possibly flags of some kind.
| CA | What follows is 4 bytes: A 32-bit float encoded in big-endian format.
| CB | What follows is 8 bytes: A 64-bit float encoded in big-endian format. (Supported, but NOT USED by the game. All coordinate / angle data is 32-bit by default in the game. If you rewrite the save using 64-bit floats, the game will keep on using 64-bit coordinates and will continue to write them as 64-bit in subsequential saves.)
| CC | What follows is 1 byte: An unsigned 8-bit integer. For values $$<128$$, 00..7F is used instead.
| CD | What follows is 2 bytes: An unsigned 16-bit integer encoded in big-endian format. For values $$<256$$, CC is used instead.
| CE | What follows is 4 bytes: An unsigned 32-bit integer encoded in big-endian format. For values $$<65536$$, CD is used instead. Fun fact: The step counter is a 32-bit integer. It overflows back to $$0$$ after $$4294967295$$ steps.
| DC | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. After that, a tuple with $$n$$ items. See encoding of tuple above. For $$n<16$$, 90..9F is used instead.
| DE | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. After that, a dictionary with $$n$$ items. See encoding of dictionary above. For $$n<16$$, 80..8F is used instead.
| other | Other values have not been observed so far.
