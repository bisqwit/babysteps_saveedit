# Save file format

The save format is a serialized representation of a single variable.
According to current understanding, it has the following interpretation:

Expression is:
| Byte value $$b_0$$ (hex) | Meaning |
| -- | -- |
| 00..7F | Integer in range 0-127
| 80..8F | Dictionary with $$n = b_0-\text{0x}80$$ items. What follows is $$n$$ items, each of which is a pair of expressions: The first is key, the second is the value corresponding to that key.
| 90..9F | Tuple with $$n = b_0-\text{0x}90$$ items. What follows is a list of $$n$$ expressions.
| A0..BF | An ASCII string with $$n = b_0-\text{0x}A0$$ characters. What follows is $$n$$ bytes of string data.
| C0 | Unknown meaning.
| C2 | Unknown meaning.
| C3 | Unknown meaning.
| CA | What follows is 4 bytes: A 32-bit float encoded in big-endian format.
| CC | What follows is 1 byte: An unsigned 8-bit integer.
| CD | What follows is 2 bytes: An unsigned 16-bit integer encoded in big-endian format.
| DC | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. What follows after that is a tuple with $$n$$ items. See encoding of tuple above.
| DE | What follows is 2 bytes: An unsigned integer $$n$$ encoded in big-endian format. What follows after that is a dictionary with $$n$$ items. See encoding of dictionary above.
