"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Buf8 = exports.Buf32 = exports.Buf16 = void 0;
exports.arraySet = arraySet;
exports.flattenChunks = flattenChunks;
exports.shrinkBuf = shrinkBuf;
// reduce buffer size, avoiding mem copy
function shrinkBuf(buf, size) {
  if (buf.length === size) {
    return buf;
  }
  if (buf.subarray) {
    return buf.subarray(0, size);
  }
  buf.length = size;
  return buf;
}
;
function arraySet(dest, src, src_offs, len, dest_offs) {
  if (src.subarray && dest.subarray) {
    dest.set(src.subarray(src_offs, src_offs + len), dest_offs);
    return;
  }
  // Fallback to ordinary array
  for (var i = 0; i < len; i++) {
    dest[dest_offs + i] = src[src_offs + i];
  }
}

// Join array of chunks to single array.
function flattenChunks(chunks) {
  var i, l, len, pos, chunk, result;

  // calculate data length
  len = 0;
  for (i = 0, l = chunks.length; i < l; i++) {
    len += chunks[i].length;
  }

  // join chunks
  result = new Uint8Array(len);
  pos = 0;
  for (i = 0, l = chunks.length; i < l; i++) {
    chunk = chunks[i];
    result.set(chunk, pos);
    pos += chunk.length;
  }
  return result;
}
var Buf8 = Uint8Array;
exports.Buf8 = Buf8;
var Buf16 = Uint16Array;
exports.Buf16 = Buf16;
var Buf32 = Int32Array;
exports.Buf32 = Buf32;