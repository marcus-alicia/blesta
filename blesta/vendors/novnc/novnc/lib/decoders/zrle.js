"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;
var _inflator = _interopRequireDefault(require("../inflator.js"));
function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
var ZRLE_TILE_WIDTH = 64;
var ZRLE_TILE_HEIGHT = 64;
var ZRLEDecoder = /*#__PURE__*/function () {
  function ZRLEDecoder() {
    _classCallCheck(this, ZRLEDecoder);
    this._length = 0;
    this._inflator = new _inflator["default"]();
    this._pixelBuffer = new Uint8Array(ZRLE_TILE_WIDTH * ZRLE_TILE_HEIGHT * 4);
    this._tileBuffer = new Uint8Array(ZRLE_TILE_WIDTH * ZRLE_TILE_HEIGHT * 4);
  }
  _createClass(ZRLEDecoder, [{
    key: "decodeRect",
    value: function decodeRect(x, y, width, height, sock, display, depth) {
      if (this._length === 0) {
        if (sock.rQwait("ZLib data length", 4)) {
          return false;
        }
        this._length = sock.rQshift32();
      }
      if (sock.rQwait("Zlib data", this._length)) {
        return false;
      }
      var data = sock.rQshiftBytes(this._length);
      this._inflator.setInput(data);
      for (var ty = y; ty < y + height; ty += ZRLE_TILE_HEIGHT) {
        var th = Math.min(ZRLE_TILE_HEIGHT, y + height - ty);
        for (var tx = x; tx < x + width; tx += ZRLE_TILE_WIDTH) {
          var tw = Math.min(ZRLE_TILE_WIDTH, x + width - tx);
          var tileSize = tw * th;
          var subencoding = this._inflator.inflate(1)[0];
          if (subencoding === 0) {
            // raw data
            var _data = this._readPixels(tileSize);
            display.blitImage(tx, ty, tw, th, _data, 0, false);
          } else if (subencoding === 1) {
            // solid
            var background = this._readPixels(1);
            display.fillRect(tx, ty, tw, th, [background[0], background[1], background[2]]);
          } else if (subencoding >= 2 && subencoding <= 16) {
            var _data2 = this._decodePaletteTile(subencoding, tileSize, tw, th);
            display.blitImage(tx, ty, tw, th, _data2, 0, false);
          } else if (subencoding === 128) {
            var _data3 = this._decodeRLETile(tileSize);
            display.blitImage(tx, ty, tw, th, _data3, 0, false);
          } else if (subencoding >= 130 && subencoding <= 255) {
            var _data4 = this._decodeRLEPaletteTile(subencoding - 128, tileSize);
            display.blitImage(tx, ty, tw, th, _data4, 0, false);
          } else {
            throw new Error('Unknown subencoding: ' + subencoding);
          }
        }
      }
      this._length = 0;
      return true;
    }
  }, {
    key: "_getBitsPerPixelInPalette",
    value: function _getBitsPerPixelInPalette(paletteSize) {
      if (paletteSize <= 2) {
        return 1;
      } else if (paletteSize <= 4) {
        return 2;
      } else if (paletteSize <= 16) {
        return 4;
      }
    }
  }, {
    key: "_readPixels",
    value: function _readPixels(pixels) {
      var data = this._pixelBuffer;
      var buffer = this._inflator.inflate(3 * pixels);
      for (var i = 0, j = 0; i < pixels * 4; i += 4, j += 3) {
        data[i] = buffer[j];
        data[i + 1] = buffer[j + 1];
        data[i + 2] = buffer[j + 2];
        data[i + 3] = 255; // Add the Alpha
      }

      return data;
    }
  }, {
    key: "_decodePaletteTile",
    value: function _decodePaletteTile(paletteSize, tileSize, tilew, tileh) {
      var data = this._tileBuffer;
      var palette = this._readPixels(paletteSize);
      var bitsPerPixel = this._getBitsPerPixelInPalette(paletteSize);
      var mask = (1 << bitsPerPixel) - 1;
      var offset = 0;
      var encoded = this._inflator.inflate(1)[0];
      for (var y = 0; y < tileh; y++) {
        var shift = 8 - bitsPerPixel;
        for (var x = 0; x < tilew; x++) {
          if (shift < 0) {
            shift = 8 - bitsPerPixel;
            encoded = this._inflator.inflate(1)[0];
          }
          var indexInPalette = encoded >> shift & mask;
          data[offset] = palette[indexInPalette * 4];
          data[offset + 1] = palette[indexInPalette * 4 + 1];
          data[offset + 2] = palette[indexInPalette * 4 + 2];
          data[offset + 3] = palette[indexInPalette * 4 + 3];
          offset += 4;
          shift -= bitsPerPixel;
        }
        if (shift < 8 - bitsPerPixel && y < tileh - 1) {
          encoded = this._inflator.inflate(1)[0];
        }
      }
      return data;
    }
  }, {
    key: "_decodeRLETile",
    value: function _decodeRLETile(tileSize) {
      var data = this._tileBuffer;
      var i = 0;
      while (i < tileSize) {
        var pixel = this._readPixels(1);
        var length = this._readRLELength();
        for (var j = 0; j < length; j++) {
          data[i * 4] = pixel[0];
          data[i * 4 + 1] = pixel[1];
          data[i * 4 + 2] = pixel[2];
          data[i * 4 + 3] = pixel[3];
          i++;
        }
      }
      return data;
    }
  }, {
    key: "_decodeRLEPaletteTile",
    value: function _decodeRLEPaletteTile(paletteSize, tileSize) {
      var data = this._tileBuffer;

      // palette
      var palette = this._readPixels(paletteSize);
      var offset = 0;
      while (offset < tileSize) {
        var indexInPalette = this._inflator.inflate(1)[0];
        var length = 1;
        if (indexInPalette >= 128) {
          indexInPalette -= 128;
          length = this._readRLELength();
        }
        if (indexInPalette > paletteSize) {
          throw new Error('Too big index in palette: ' + indexInPalette + ', palette size: ' + paletteSize);
        }
        if (offset + length > tileSize) {
          throw new Error('Too big rle length in palette mode: ' + length + ', allowed length is: ' + (tileSize - offset));
        }
        for (var j = 0; j < length; j++) {
          data[offset * 4] = palette[indexInPalette * 4];
          data[offset * 4 + 1] = palette[indexInPalette * 4 + 1];
          data[offset * 4 + 2] = palette[indexInPalette * 4 + 2];
          data[offset * 4 + 3] = palette[indexInPalette * 4 + 3];
          offset++;
        }
      }
      return data;
    }
  }, {
    key: "_readRLELength",
    value: function _readRLELength() {
      var length = 0;
      var current = 0;
      do {
        current = this._inflator.inflate(1)[0];
        length += current;
      } while (current === 255);
      return length + 1;
    }
  }]);
  return ZRLEDecoder;
}();
exports["default"] = ZRLEDecoder;