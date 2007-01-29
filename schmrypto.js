// Returns an array containing "howMany" random bytes. 

function getRandomBytes(howMany) {
  var i;
  var bytes = new Array();
  for (i=0; i<howMany; i++)
    bytes[i] = Math.round(Math.random()*255);
  return bytes;
}

/**
 *
 *  UTF-8 data encode / decode
 *  http://www.webtoolkit.info/
 *
 **/
var Utf8 = {
  // public method for url encoding
  encode : function (string) {
    string = string.replace(/\r\n/g,"\n");
    var bytes = [];

    for (var n = 0; n < string.length; n++) {
      var c = string.charCodeAt(n);

      if (c < 128) {
	bytes.push(c);
      }
      else if((c > 127) && (c < 2048)) {
	bytes.push((c >> 6) | 192);
	bytes.push((c & 63) | 128);
      }
      else {
	bytes.push((c >> 12) | 224);
	bytes.push(((c >> 6) & 63) | 128);
	bytes.push((c & 63) | 128);
      }
    }

    return bytes;
  },

  // public method for url decoding
  decode : function (bytes) {
    var string = "";
    var i = 0;
    var c = c1 = c2 = 0;

    while ( i < bytes.length ) {
      c = bytes[i];

      if (c < 128) {
	string += String.fromCharCode(c);
	i++;
      }
      else if((c > 191) && (c < 224)) {
	c2 = bytes.charCodeAt(i+1);
	string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
	i += 2;
      }
      else {
	c2 = bytes.charCodeAt(i+1);
	c3 = bytes.charCodeAt(i+2);
	string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
	i += 3;
      }
    }

    return string;
  }
}


/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

var Base64 = {

  // private property
  _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

  // public method for encoding
  encode : function (input) {
    var output = "";
    var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
    var i = 0;

    while (i < input.length) {
      chr1 = input[i++];
      chr2 = input[i++];
      chr3 = input[i++];

      enc1 = chr1 >> 2;
      enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
      enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
      enc4 = chr3 & 63;

      if (isNaN(chr2)) {
	enc3 = enc4 = 64;
      } else if (isNaN(chr3)) {
	enc4 = 64;
      }

      output = output +
	this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
	this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
    }

    return output;
  },

  // public method for decoding
  decode : function (input) {
    var output = [];
    var chr1, chr2, chr3;
    var enc1, enc2, enc3, enc4;
    var i = 0;

    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

    while (i < input.length) {
      enc1 = this._keyStr.indexOf(input.charAt(i++));
      enc2 = this._keyStr.indexOf(input.charAt(i++));
      enc3 = this._keyStr.indexOf(input.charAt(i++));
      enc4 = this._keyStr.indexOf(input.charAt(i++));

      chr1 = (enc1 << 2) | (enc2 >> 4);
      chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
      chr3 = ((enc3 & 3) << 6) | enc4;

      output.push(chr1);

      if (enc3 != 64) {
	output.push(chr2);
      }
      if (enc4 != 64) {
	output.push(chr3);
      }

    }

    return output;
  }
}

/*
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS PUB 180-1
 * Version 2.1a Copyright Paul Johnston 2000 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function sha1(data){
  return words2bytes(core_sha1(bytes2words(data), data.length * 8)); 
}

function hmac(key, data) {
  return words2bytes(core_hmac_sha1(bytes2words(key), bytes2words(data)));
}

/*
 * Perform a simple self-test to see if the VM is working
 */
function sha1_vm_test()
{
  return sha1([97,98,99]).join(",") == 
    [169,153,62,54,71,6,129,106,186,62,37,113,120,80,194,108,156,208,216,157].join(",");
}

/*
 * Calculate the SHA-1 of an array of big-endian words, and a bit length
 */
function core_sha1(x, len)
{
  /* append padding */
  x[len >> 5] |= 0x80 << (24 - len % 32);
  x[((len + 64 >> 9) << 4) + 15] = len;

  var w = Array(80);
  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;
  var e = -1009589776;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;
    var olde = e;

    for(var j = 0; j < 80; j++)
    {
      if(j < 16) w[j] = x[i + j];
      else w[j] = rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
      var t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)),
                       safe_add(safe_add(e, w[j]), sha1_kt(j)));
      e = d;
      d = c;
      c = rol(b, 30);
      b = a;
      a = t;
    }

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
    e = safe_add(e, olde);
  }
  return Array(a, b, c, d, e);

}

/*
 * Perform the appropriate triplet combination function for the current
 * iteration
 */
function sha1_ft(t, b, c, d)
{
  if(t < 20) return (b & c) | ((~b) & d);
  if(t < 40) return b ^ c ^ d;
  if(t < 60) return (b & c) | (b & d) | (c & d);
  return b ^ c ^ d;
}

/*
 * Determine the appropriate additive constant for the current iteration
 */
function sha1_kt(t)
{
  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
         (t < 60) ? -1894007588 : -899497514;
}

/*
 * Calculate the HMAC-SHA1 of a key and some data
 */
function core_hmac_sha1(bkey, data)
{
  if(bkey.length > 16) bkey = core_sha1(bkey, bkey.length * 8);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = core_sha1(ipad.concat(data), 512 + data.length * 8);
  return core_sha1(opad.concat(hash), 512 + 160);
}

/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

/*
 * Bitwise rotate a 32-bit number to the left.
 */
function rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}

/*
 * Convert an array of bytes into to an array of big-endian words
 */
function bytes2words(bytes)
{
  var words = Array();
  var mask = (1 << 8) - 1;
  for(var i = 0; i < bytes.length * 8; i += 8)
    words[i>>5] |= (bytes[i / 8] & mask) << (32 - 8 - i%32);
  return words;
}

/*
 * Convert an array of big-endian words into an array of bytes
 */
function words2bytes(words)
{
  var bytes = [];
  var mask = (1 << 8) - 1;
  for(var i = 0; i < words.length * 32; i += 8)
    bytes[bytes.length] = (words[i>>5] >>> (32 - 8 - i%32)) & mask;
  return bytes;
}

function getKey() {
  if (typeof globalStorage != "undefined") {
    var bytes = globalStorage[location.host].getItem("key");

    if (!bytes) {
      return;
    }

    bytes = bytes.value;
  } else {
    localStore.load("foo");
    var bytes = localStore.getAttribute("key");
  }

  if (!bytes) {
    return;
  }

  bytes = bytes.split(" ");

  for (var i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(bytes[i]);
  }

  return bytes;
}

function ARC4(key) {
  this.sbox_ = [];
  this.key_ = [];
  this.i_ = 0;
  this.j_ = 0;

  this.setKey(key);
}

ARC4.prototype.setKey = function(key) {
  var t, a, b;
  var len = key.length;

  for (a = 0; a < 256; a++) {
    this.key_[a] = key[a % len];
    this.sbox_[a] = a;
  }

  b = 0;
  for (a = 0; a < 256; a++) {
    b = (b + this.sbox_[a] + this.key_[a]) % 256;
    t = this.sbox_[a];
    this.sbox_[a] = this.sbox_[b];
    this.sbox_[b] = t;
  }

  // discard a bunch of the bytes from the front of the keystream as they are sucky.
  this.crypt(new Array(1536));
};

ARC4.prototype.crypt = function(pt) {
  var out = [];
  var t, a, k;

  for (var a = 0; a < pt.length; a++) {
    this.i_ = (this.i_ + 1) % 256;
    this.j_ = (this.j_ + this.sbox_[this.i_]) % 256;
    t = this.sbox_[this.i_];
    this.sbox_[this.i_] = this.sbox_[this.j_];
    this.sbox_[this.j_] = t;

    k = this.sbox_[(this.sbox_[this.i_] + this.sbox_[this.j_]) % 256];
    out.push(pt[a] ^ k);
  }

  return out;
};

ARC4.test = function() {
  var testData = ["Key", "Plaintext", [187,243,22,232,217,64,175,10,211],
		  "Wiki", "pedia", [16,33,191,4,32],
		  "Secret", "Attack at dawn", [69,160,31,100,95,195,91,56,53,82,84,75,155,245]];

  for (var i = 0; i < testData.length; i += 3) {
    var arc4 = new ARC4();

    arc4.setKey(Utf8.encode(testData[i]));
    var result = arc4.crypt(Utf8.encode(testData[i+1]));

    if (result.join(" ") != testData[i+2].join(" ")) {
      alert("Could not encrypt " + testData[i] + "/" + testData[i+1]);
    }

    document.writeln("<br>" + testData[i] + "/" + testData[i+1] + " OK");
  }
};

function isEncrypted(s) {
  if (s == "") {
    return false;
  }

  var parts = s.split("$");

  if (parts.length != 3 || parts[0] != "1") {
    return false;
  }

  return true;
}

function masterEncrypt(s) {
  var keyBytes = getKey();
  var inputBytes = Utf8.encode(s);
  var randBytes = getRandomBytes(8);
  var hmacBytes = hmac(inputBytes, keyBytes.concat(1));

  var saltBytes = sha1(hmacBytes.concat(userId, id, randBytes));
  var rc4KeyBytes = hmac(saltBytes, keyBytes.concat(2));
  var ctBytes = new ARC4(rc4KeyBytes).crypt(hmacBytes.concat(inputBytes));

  var saltStr = Base64.encode(saltBytes);
  var ctStr = Base64.encode(ctBytes);

  return "1$" + saltStr + "$" + ctStr;
}

function masterDecrypt(s) {
  if (s == "") {
    return s;
  }

  if (!isEncrypted(s)) {
    alert("ERROR: Unexpected format in encrypted text.");
    return s;
  }

  var parts = s.split("$");
  var saltStr = parts[1];
  var ctStr = parts[2];

  var keyBytes = getKey();
  var ctBytes = Base64.decode(ctStr);
  var saltBytes = Base64.decode(saltStr);

  var rc4KeyBytes = hmac(saltBytes, keyBytes.concat(2));
  var ptBytes = new ARC4(rc4KeyBytes).crypt(ctBytes);
  var hmacBytes = ptBytes.splice(0, 20);

  if (hmac(ptBytes, keyBytes.concat(1)).join(",") != hmacBytes.join(",")) {
    alert("ERROR: Invalid encrypted text.");
    return s;
  }

  return Utf8.decode(ptBytes);
}
