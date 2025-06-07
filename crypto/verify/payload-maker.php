<?php
// Made by Dasho
// You can generate a good random salt value from the PHP CLI like this: php -r "echo bin2hex(random_bytes(16));"
// The resulting string will look like this: 7674ffcd9882e411415ea1ab7726642d

/*
This is a copy of the script that is used to generate the payload for the verification process on verify.dasho.dev. Use this script alongside with the payload-verifier.php script to make payloads that others can verify.
*/

define('SALT', sodium_hex2bin('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'));

$b91_enctab = array(
	'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
	'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
	'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
	'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
	'0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!', '#', '$',
	'%', '&', '(', ')', '*', '+', ',', '.', '/', ':', ';', '<', '=',
	'>', '?', '@', '[', ']', '^', '_', '`', '{', '|', '}', '~', '"'
);

$b91_dectab = array_flip($b91_enctab);

function base91_decode($d) {
	global $b91_dectab;
	$n = $b = $o = null;
	$l = strlen($d);
	$v = -1;
	for ($i = 0; $i < $l; ++$i) {
		$c = $b91_dectab[$d[$i]];
		if(!isset($c))
			continue;
		if($v < 0)
			$v = $c;
		else {
			$v += $c * 91;
			$b |= $v << $n;
			$n += ($v & 8191) > 88 ? 13 : 14;
			do {
				$o .= chr($b & 255);
				$b >>= 8;
				$n -= 8;
			} while ($n > 7);
			$v = -1;
		}
	}
	if($v + 1)
		$o .= chr(($b | $v << $n) & 255);
	return $o;
}

function base91_encode($d) {
	global $b91_enctab;
	$n = $b = $o = null;
	$l = strlen($d);
	for ($i = 0; $i < $l; ++$i) {
		$b |= ord($d[$i]) << $n;
		$n += 8;
		if($n > 13) {
			$v = $b & 8191;
			if($v > 88) {
				$b >>= 13;
				$n -= 13;
			} else {
				$v = $b & 16383;
				$b >>= 14;
				$n -= 14;
			}
			$o .= $b91_enctab[$v % 91] . $b91_enctab[$v / 91];
		}
	}
	if($n) {
		$o .= $b91_enctab[$b % 91];
		if($n > 7 || $b > 90)
			$o .= $b91_enctab[$b / 91];
	}
	return $o;
}

function saltify_encrypt($message, $key) {
	$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
	$cipher = base91_encode($nonce.sodium_crypto_secretbox($message, $nonce, $key));
	sodium_memzero($message);
	sodium_memzero($key);
	return $cipher;
}

function saltify_decrypt($encrypted, $key) {
	$decoded = base91_decode($encrypted);
	if($decoded === false) return false;
	if(mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) return false;
	$nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
	$ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
	$plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
	if($plain === false) return false;
	sodium_memzero($ciphertext);
	sodium_memzero($key);
	return $plain;
}

function saltify_key($key) {
	$key = sodium_crypto_pwhash(32, $key, SALT, SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);
	return $key;
}

if(isset($_REQUEST['payload'])) {
	$payload = $_REQUEST['payload'];
	$key = $_REQUEST['key'];
	$saltify_key = saltify_key($key);

	$pl = $payload;

	$pl = str_replace('-- BEGIN VERIFICATION PAYLOAD --', '', $pl);
	$pl = str_replace('-- END VERIFICATION PAYLOAD --', '', $pl);
	$pl = str_replace("\n", '', $pl);
	$pl = str_replace("\r", '', $pl);
	$pl = str_replace(' ', '', $pl);

	// is the resulting payload something that comes back as valid?
	$dec = saltify_decrypt($pl, $saltify_key);

	$result = "\n".'<section id="results"><h2>Results</h2>';

	if($dec === false) {
		$result .= "\n".'<p><span class="detected">🎯 Payload Created</span></p>';
		$enc = saltify_encrypt($payload, $saltify_key); //generates random encrypted string (Base64 related)
		$encrypted = str_split($enc, 2);
		$encrypted = '-- BEGIN VERIFICATION PAYLOAD --'."\n".implode(' ', $encrypted)."\n".'-- END VERIFICATION PAYLOAD --';
		$dec = saltify_decrypt($enc, $saltify_key); //generates random encrypted string (Base64 related)
		$result .= "\n".'<h3>🔐 Verification payload</h3>';
		$result .= "\n".'<pre>'.htmlentities($encrypted).'</pre>';
		$enc2 = saltify_encrypt($payload, $saltify_key); //generates random encrypted string (Base64 related)
		$result .= "\n".'<h3>🔑 Compressed version</h3>';
		$result .= "\n".'<pre>'.htmlentities($enc2).'</pre><p><small><span class="chars">'.strlen($enc2).' chars</span></small></p>';
	}
	else {
		if(preg_match('/Expires (\d\d\ (January|February|March|April|May|June|July|August|September|October|November|December) \d\d\d\d)/i', $dec, $date)) {
			$expiry_date = strtotime($date[1]);
			if(strtotime("now") > $expiry_date){
				$result .= "\n".'<p><span class="detected">⛔ Payload cannot verify - Payload expired ⛔</span></p>';
			}else{
				$result .= "\n".'<p><span class="detected">✅ Payload verifies correctly ✅</span></p>';
				$result .= "\n".'<pre>'.htmlentities($dec).'</pre>';
			}
		}else{
		$result .= "\n".'<p><span class="detected">✅ Payload verifies correctly ✅</span></p>';
		$result .= "\n".'<pre>'.htmlentities($dec).'</pre>';
		}
	}

	$result .= "\n</section>";

  }
  else {
	$payload = 'Enter verification payload here...';
	$key = 'Enter your script secret key here...';

	$result = null;
  }

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Auth Dasho</title>
<link rel="shortcut icon" href="https://cdn.onionz.dev/global/images/favicon.svg" />
<meta property="og:title" content="Auth Dasho">
<meta property="og:description" content="Auth Dasho">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>

/*
 *
 *  𝗖 𝗢 𝗟 𝗢 𝗥
 *  v 1.7.0
 *
 *  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

 :root {

    /*  General
     *  ─────────────────────────────────── */

      --oc-white: #ffffff;
      --oc-white-rgb: 255, 255, 255;
      --oc-black: #000000;
      --oc-black-rgb: 0, 0, 0;


    /*  Gray
     *  ─────────────────────────────────── */

      --oc-gray-0: #f8f9fa;
      --oc-gray-0-rgb: 248, 249, 250;
      --oc-gray-1: #f1f3f5;
      --oc-gray-1-rgb: 241, 243, 245;
      --oc-gray-2: #e9ecef;
      --oc-gray-2-rgb: 233, 236, 239;
      --oc-gray-3: #dee2e6;
      --oc-gray-3-rgb: 222, 226, 230;
      --oc-gray-4: #ced4da;
      --oc-gray-4-rgb: 206, 212, 218;
      --oc-gray-5: #adb5bd;
      --oc-gray-5-rgb: 173, 181, 189;
      --oc-gray-6: #868e96;
      --oc-gray-6-rgb: 134, 142, 150;
      --oc-gray-7: #495057;
      --oc-gray-7-rgb: 73, 80, 87;
      --oc-gray-8: #343a40;
      --oc-gray-8-rgb: 52, 58, 64;
      --oc-gray-9: #212529;
      --oc-gray-9-rgb: 33, 37, 41;


    /*  Red
     *  ─────────────────────────────────── */

      --oc-red-0: #fff5f5;
      --oc-red-0-rgb: 255, 245, 245;
      --oc-red-1: #ffe3e3;
      --oc-red-1-rgb: 255, 227, 227;
      --oc-red-2: #ffc9c9;
      --oc-red-2-rgb: 255, 201, 201;
      --oc-red-3: #ffa8a8;
      --oc-red-3-rgb: 255, 168, 168;
      --oc-red-4: #ff8787;
      --oc-red-4-rgb: 255, 135, 135;
      --oc-red-5: #ff6b6b;
      --oc-red-5-rgb: 255, 107, 107;
      --oc-red-6: #fa5252;
      --oc-red-6-rgb: 250, 82, 82;
      --oc-red-7: #f03e3e;
      --oc-red-7-rgb: 240, 62, 62;
      --oc-red-8: #e03131;
      --oc-red-8-rgb: 224, 49, 49;
      --oc-red-9: #c92a2a;
      --oc-red-9-rgb: 201, 42, 42;


    /*  Pink
     *  ─────────────────────────────────── */

      --oc-pink-0: #fff0f6;
      --oc-pink-0-rgb: 255, 240, 246;
      --oc-pink-1: #ffdeeb;
      --oc-pink-1-rgb: 255, 222, 235;
      --oc-pink-2: #fcc2d7;
      --oc-pink-2-rgb: 252, 194, 215;
      --oc-pink-3: #faa2c1;
      --oc-pink-3-rgb: 250, 162, 193;
      --oc-pink-4: #f783ac;
      --oc-pink-4-rgb: 247, 131, 172;
      --oc-pink-5: #f06595;
      --oc-pink-5-rgb: 240, 101, 149;
      --oc-pink-6: #e64980;
      --oc-pink-6-rgb: 230, 73, 128;
      --oc-pink-7: #d6336c;
      --oc-pink-7-rgb: 214, 51, 108;
      --oc-pink-8: #c2255c;
      --oc-pink-8-rgb: 194, 37, 92;
      --oc-pink-9: #a61e4d;
      --oc-pink-9-rgb: 166, 30, 77;


    /*  Grape
     *  ─────────────────────────────────── */

      --oc-grape-0: #f8f0fc;
      --oc-grape-0-rgb: 248, 240, 252;
      --oc-grape-1: #f3d9fa;
      --oc-grape-1-rgb: 243, 217, 250;
      --oc-grape-2: #eebefa;
      --oc-grape-2-rgb: 238, 190, 250;
      --oc-grape-3: #e599f7;
      --oc-grape-3-rgb: 229, 153, 247;
      --oc-grape-4: #da77f2;
      --oc-grape-4-rgb: 218, 119, 242;
      --oc-grape-5: #cc5de8;
      --oc-grape-5-rgb: 204, 93, 232;
      --oc-grape-6: #be4bdb;
      --oc-grape-6-rgb: 190, 75, 219;
      --oc-grape-7: #ae3ec9;
      --oc-grape-7-rgb: 174, 62, 201;
      --oc-grape-8: #9c36b5;
      --oc-grape-8-rgb: 156, 54, 181;
      --oc-grape-9: #862e9c;
      --oc-grape-9-rgb: 134, 46, 156;


    /*  Violet
     *  ─────────────────────────────────── */

      --oc-violet-0: #f3f0ff;
      --oc-violet-0-rgb: 243, 240, 255;
      --oc-violet-1: #e5dbff;
      --oc-violet-1-rgb: 229, 219, 255;
      --oc-violet-2: #d0bfff;
      --oc-violet-2-rgb: 208, 191, 255;
      --oc-violet-3: #b197fc;
      --oc-violet-3-rgb: 177, 151, 252;
      --oc-violet-4: #9775fa;
      --oc-violet-4-rgb: 151, 117, 250;
      --oc-violet-5: #845ef7;
      --oc-violet-5-rgb: 132, 94, 247;
      --oc-violet-6: #7950f2;
      --oc-violet-6-rgb: 121, 80, 242;
      --oc-violet-7: #7048e8;
      --oc-violet-7-rgb: 112, 72, 232;
      --oc-violet-8: #6741d9;
      --oc-violet-8-rgb: 103, 65, 217;
      --oc-violet-9: #5f3dc4;
      --oc-violet-9-rgb: 95, 61, 196;


    /*  Indigo
     *  ─────────────────────────────────── */

      --oc-indigo-0: #edf2ff;
      --oc-indigo-0-rgb: 237, 242, 255;
      --oc-indigo-1: #dbe4ff;
      --oc-indigo-1-rgb: 219, 228, 255;
      --oc-indigo-2: #bac8ff;
      --oc-indigo-2-rgb: 186, 200, 255;
      --oc-indigo-3: #91a7ff;
      --oc-indigo-3-rgb: 145, 167, 255;
      --oc-indigo-4: #748ffc;
      --oc-indigo-4-rgb: 116, 143, 252;
      --oc-indigo-5: #5c7cfa;
      --oc-indigo-5-rgb: 92, 124, 250;
      --oc-indigo-6: #4c6ef5;
      --oc-indigo-6-rgb: 76, 110, 245;
      --oc-indigo-7: #4263eb;
      --oc-indigo-7-rgb: 66, 99, 235;
      --oc-indigo-8: #3b5bdb;
      --oc-indigo-8-rgb: 59, 91, 219;
      --oc-indigo-9: #364fc7;
      --oc-indigo-9-rgb: 54, 79, 199;


    /*  Blue
     *  ─────────────────────────────────── */

      --oc-blue-0: #e7f5ff;
      --oc-blue-0-rgb: 231, 245, 255;
      --oc-blue-1: #d0ebff;
      --oc-blue-1-rgb: 208, 235, 255;
      --oc-blue-2: #a5d8ff;
      --oc-blue-2-rgb: 165, 216, 255;
      --oc-blue-3: #74c0fc;
      --oc-blue-3-rgb: 116, 192, 252;
      --oc-blue-4: #4dabf7;
      --oc-blue-4-rgb: 77, 171, 247;
      --oc-blue-5: #339af0;
      --oc-blue-5-rgb: 51, 154, 240;
      --oc-blue-6: #228be6;
      --oc-blue-6-rgb: 34, 139, 230;
      --oc-blue-7: #1c7ed6;
      --oc-blue-7-rgb: 28, 126, 214;
      --oc-blue-8: #1971c2;
      --oc-blue-8-rgb: 25, 113, 194;
      --oc-blue-9: #1864ab;
      --oc-blue-9-rgb: 24, 100, 171;


    /*  Cyan
     *  ─────────────────────────────────── */

      --oc-cyan-0: #e3fafc;
      --oc-cyan-0-rgb: 227, 250, 252;
      --oc-cyan-1: #c5f6fa;
      --oc-cyan-1-rgb: 197, 246, 250;
      --oc-cyan-2: #99e9f2;
      --oc-cyan-2-rgb: 153, 233, 242;
      --oc-cyan-3: #66d9e8;
      --oc-cyan-3-rgb: 102, 217, 232;
      --oc-cyan-4: #3bc9db;
      --oc-cyan-4-rgb: 59, 201, 219;
      --oc-cyan-5: #22b8cf;
      --oc-cyan-5-rgb: 34, 184, 207;
      --oc-cyan-6: #15aabf;
      --oc-cyan-6-rgb: 21, 170, 191;
      --oc-cyan-7: #1098ad;
      --oc-cyan-7-rgb: 16, 152, 173;
      --oc-cyan-8: #0c8599;
      --oc-cyan-8-rgb: 12, 133, 153;
      --oc-cyan-9: #0b7285;
      --oc-cyan-9-rgb: 11, 114, 133;


    /*  Teal
     *  ─────────────────────────────────── */

      --oc-teal-0: #e6fcf5;
      --oc-teal-0-rgb: 230, 252, 245;
      --oc-teal-1: #c3fae8;
      --oc-teal-1-rgb: 195, 250, 232;
      --oc-teal-2: #96f2d7;
      --oc-teal-2-rgb: 150, 242, 215;
      --oc-teal-3: #63e6be;
      --oc-teal-3-rgb: 99, 230, 190;
      --oc-teal-4: #38d9a9;
      --oc-teal-4-rgb: 56, 217, 169;
      --oc-teal-5: #20c997;
      --oc-teal-5-rgb: 32, 201, 151;
      --oc-teal-6: #12b886;
      --oc-teal-6-rgb: 18, 184, 134;
      --oc-teal-7: #0ca678;
      --oc-teal-7-rgb: 12, 166, 120;
      --oc-teal-8: #099268;
      --oc-teal-8-rgb: 9, 146, 104;
      --oc-teal-9: #087f5b;
      --oc-teal-9-rgb: 8, 127, 91;


    /*  Green
     *  ─────────────────────────────────── */

      --oc-green-0: #ebfbee;
      --oc-green-0-rgb: 235, 251, 238;
      --oc-green-1: #d3f9d8;
      --oc-green-1-rgb: 211, 249, 216;
      --oc-green-2: #b2f2bb;
      --oc-green-2-rgb: 178, 242, 187;
      --oc-green-3: #8ce99a;
      --oc-green-3-rgb: 140, 233, 154;
      --oc-green-4: #69db7c;
      --oc-green-4-rgb: 105, 219, 124;
      --oc-green-5: #51cf66;
      --oc-green-5-rgb: 81, 207, 102;
      --oc-green-6: #40c057;
      --oc-green-6-rgb: 64, 192, 87;
      --oc-green-7: #37b24d;
      --oc-green-7-rgb: 55, 178, 77;
      --oc-green-8: #2f9e44;
      --oc-green-8-rgb: 47, 158, 68;
      --oc-green-9: #2b8a3e;
      --oc-green-9-rgb: 43, 138, 62;


    /*  Lime
     *  ─────────────────────────────────── */

      --oc-lime-0: #f4fce3;
      --oc-lime-0-rgb: 244, 252, 227;
      --oc-lime-1: #e9fac8;
      --oc-lime-1-rgb: 233, 250, 200;
      --oc-lime-2: #d8f5a2;
      --oc-lime-2-rgb: 216, 245, 162;
      --oc-lime-3: #c0eb75;
      --oc-lime-3-rgb: 192, 235, 117;
      --oc-lime-4: #a9e34b;
      --oc-lime-4-rgb: 169, 227, 75;
      --oc-lime-5: #94d82d;
      --oc-lime-5-rgb: 148, 216, 45;
      --oc-lime-6: #82c91e;
      --oc-lime-6-rgb: 130, 201, 30;
      --oc-lime-7: #74b816;
      --oc-lime-7-rgb: 116, 184, 22;
      --oc-lime-8: #66a80f;
      --oc-lime-8-rgb: 102, 168, 15;
      --oc-lime-9: #5c940d;
      --oc-lime-9-rgb: 92, 148, 13;


    /*  Yellow
     *  ─────────────────────────────────── */

      --oc-yellow-0: #fff9db;
      --oc-yellow-0-rgb: 255, 249, 219;
      --oc-yellow-1: #fff3bf;
      --oc-yellow-1-rgb: 255, 243, 191;
      --oc-yellow-2: #ffec99;
      --oc-yellow-2-rgb: 255, 236, 153;
      --oc-yellow-3: #ffe066;
      --oc-yellow-3-rgb: 255, 224, 102;
      --oc-yellow-4: #ffd43b;
      --oc-yellow-4-rgb: 255, 212, 59;
      --oc-yellow-5: #fcc419;
      --oc-yellow-5-rgb: 252, 196, 25;
      --oc-yellow-6: #fab005;
      --oc-yellow-6-rgb: 250, 176, 5;
      --oc-yellow-7: #f59f00;
      --oc-yellow-7-rgb: 245, 159, 0;
      --oc-yellow-8: #f08c00;
      --oc-yellow-8-rgb: 240, 140, 0;
      --oc-yellow-9: #e67700;
      --oc-yellow-9-rgb: 230, 119, 0;


    /*  Orange
     *  ─────────────────────────────────── */

      --oc-orange-0: #fff4e6;
      --oc-orange-0-rgb: 255, 244, 230;
      --oc-orange-1: #ffe8cc;
      --oc-orange-1-rgb: 255, 232, 204;
      --oc-orange-2: #ffd8a8;
      --oc-orange-2-rgb: 255, 216, 168;
      --oc-orange-3: #ffc078;
      --oc-orange-3-rgb: 255, 192, 120;
      --oc-orange-4: #ffa94d;
      --oc-orange-4-rgb: 255, 169, 77;
      --oc-orange-5: #ff922b;
      --oc-orange-5-rgb: 255, 146, 43;
      --oc-orange-6: #fd7e14;
      --oc-orange-6-rgb: 253, 126, 20;
      --oc-orange-7: #f76707;
      --oc-orange-7-rgb: 247, 103, 7;
      --oc-orange-8: #e8590c;
      --oc-orange-8-rgb: 232, 89, 12;
      --oc-orange-9: #d9480f;
      --oc-orange-9-rgb: 217, 72, 15;

    }

/* Colors */

:root {
		--background: var(--oc-gray-9);
		--background-secondary: var(--oc-gray-8);
		--background-tertiary: var(--oc-gray-7);
		--foreground: var(--oc-white);
		--foreground-heavy: var(--oc-white);
}

*, *::before, *::after {
	box-sizing: border-box;
}

a:link, a:visited, a:hover, a:active {
	color: inherit !important;
}

html {
	font-family: 'Inter', sans-serif;
	font-feature-settings: "calt" 1;
	font-size: 1.2em;
	background: var(--background);
	color: var(--foreground);
	height: 100%;
}

* {
	transition: background .1s;
}

body {
	margin: 0;
	padding: 0;
}

@supports (font-variation-settings: normal) {
	html {
		font-family: 'Inter var', sans-serif;
	}
}

/* Type */

.centered {
	text-align: center;
}

/* Flexbox */

.flex {
	display: flex;
	flex-wrap: wrap;
}

.box {
	flex-grow: 1;
	flex-basis: 15em;
	margin: 0 .5em .5em 0;
}

.fit {
	flex-basis: 1em;
}

.half   { flex-basis: calc(50% - (.5em * 2)); }
.third  { flex-basis: calc(33% - (.5em * 3)); }
.fourth { flex-basis: calc(25% - (.5em * 4)); }
.fifth  { flex-basis: calc(20% - (.5em * 5)); }

.clickable:hover {
	cursor: pointer;
}

main, header, footer {
	display: block;
	margin: auto;
	padding: 0 2em;
	max-width: 50em;
}

header nav {
	margin: 1em 0 5em 0;
	padding: 0;
	background: var(--background);
	font-size: 80%;
}

header nav .fa-long-arrow-alt-right {
	padding: 0 .7em 0 .1em;
	color: var(--foreground);
}

header, footer {
	padding-top: 2em;
}

footer {
	text-align: center;
	padding-bottom: 3em;
}

footer small {
	font-size: .8em;
}

footer small a {
	text-decoration: none;
}

footer li a:link, footer li a:visited {
	color: #333;
}

footer li a:hover, footer li a:active {
	color: #555;
}

footer .fab {
	font-size: 1.6em;
}

footer .site {
	font-size: 80%;
	margin: 1.5em 0 !important;
}

footer .site li {
	padding-right: .5em;
}

footer .site li a {
	text-decoration: none;
}

footer img {
	width: 4em;
	margin: 2em 0 1em 0;
}

/* Headings */

h1, h2, h3, h4, h5, h6 {
	margin: 2rem 0;
	font-weight: 500;
	color: var(--foreground-heavy);
}

h1 {
	font-weight: 700;
}

section h2 {
	margin: 1rem 0 2rem 0;
}

p, ul, li, div {
	line-height: 160%;
}

ul.horizontal {
	list-style-type: none;
	margin: 0;
	padding: 0;
}

ul.horizontal li {
	display: inline;
}

.bulletless {
	list-style-type: none;
	margin: 0;
	padding: 0;
}

hr {
	border: 0;
	height: 1px;
	background: var(--foreground);
	margin: 2em 0;
}

/* Sections */

section {
	border-left: .5em solid var(--background);
	padding-left: 1em;
	padding-top: .5em;
	padding-bottom: .5em;
	margin-left: -1.5em;
}

/* Blockquotes & Code */

.code, pre, code, .monospace {
	font-family: 'Fira Code';
}

pre {
	white-space: pre-wrap;
	word-break: break-word;
}

/* Messages */

.message {
	display: flex;
	color: var(--foreground);
	margin: 1.5em 0;
	width: 100%;
	align-items: stretch;
	align-content: stretch;
}

.message-icon {
	background: var(--background-tertiary);
	align-self: stretch;
	padding: .5em;
	border-top-left-radius: .2em;
	border-bottom-left-radius: .2em;
}

.message-icon i {
	font-size: 1.5em;
	margin: .5em;
}

.message-text {
	flex-basis: 100%;
	margin: 0;
	padding: 1.25em;
	overflow: hidden;
	background: var(--background-secondary);
	align-self: stretch;
	border-top-right-radius: .2em;
	border-bottom-right-radius: .2em;
}

.confirmation .message-icon { background: var(--oc-green-5); color: var(--oc-black); }
.confirmation .message-text { background: var(--oc-green-3); color: var(--oc-black); }

.notice .message-icon { background: var(--oc-yellow-5); color: var(--oc-black); }
.notice .message-text { background: var(--oc-yellow-3); color: var(--oc-black); }

.warning .message-icon { background: var(--oc-orange-5); color: var(--oc-black); }
.warning .message-text { background: var(--oc-orange-3); color: var(--oc-black); }

.alert .message-icon { background: var(--oc-red-5); color: var(--oc-black); }
.alert .message-text { background: var(--oc-red-3); color: var(--oc-black); }

/* Nav */

nav {
	width: 100%;
	padding: .5em 1em;
	border-radius: 0.2em;
	margin: 2em 0;
	background: var(--background-secondary);
}

nav a:link, nav a:visited {
	text-decoration: none;
}

nav a:hover, nav a:active {
	text-decoration: none;
}

nav li {
	margin-right: .5em;
}

.smaller {
	font-size: 90%;
}

.even-smaller {
	font-size: 80%;
}

.larger {
	font-size: 120%;
}

.even-larger {
	font-size: 130%;
}

.border-solid {
	border: 1px solid #888;
	border-radius: .2em;
}

.border-dashed {
	border: 1px dashed #888;
	border-radius: .2em;
}

.border-dotted {
	border: 1px dotted #888;
	border-radius: .2em;
}

/* Forms */

.required:before {
	content: "REQUIRED";
	font-size: 60%;
	/*background: #e75757;*/
	color: #fff;
	padding: .25em .5em;
	border-radius: .25em;
	margin: 0 0 0 .5em;
}

.form_note {
	padding: .5em 1em;
	margin-top: 2em;
	border-radius: .2em;
}

fieldset {
	margin: 1.5em 0;
	padding: 1em;
	border: 0;
	background: var(--background-secondary);
	color: var(--foreground);
	border-radius: .2em;
}

legend {
	padding: .7em 1em 2.5em .85em;
	margin: 0 0 -2.5em -.85em;
	font-weight: 700;
	font-size: 120%;
	background: var(--background-secondary);
	color: var(--foereground);
	border-radius: .2em;
}

input, button, select, option, textarea {
	font-family: inherit;
	font-size: inherit;
	background: var(--background);
	color: var(--foreground);
	padding: .5em;
	border-radius: .2em;
}

button, select, option {
	cursor: pointer;
}

input, select, option, textarea {
	border: 1px solid #999;
}

textarea {
	width: 100%;
}

select {
	-webkit-appearance: none;
}

label {
	cursor: pointer;
	display: block;
	margin: 1em 0 .5em 0;
	font-weight: normal;
}

.label {
	cursor: pointer;
	margin: .4em 0 .4em 0;;
}

.radio label,
.checkbox label {
	display: inline;
	margin: 0;
}

.group {
	margin-bottom: 1em;
}

button, .button, input[type="submit"] {
	border: 0;
	padding: .5em 1em;
	cursor: pointer;
	border-radius: .2em;
	background: var(--oc-blue-7);
	color: var(--oc-white);
}

input[type="text"], input[type="password"], select {
	width: 95%;
	margin-bottom: .5em;
}

button:hover,
button:focus {
	opacity: .85;
}

button:active {
	transform: scale(0.97);
	transition: transform 0.1s ease-in-out;
}

form {
	width: 100%;
}

.form-reset {
	margin: 0;
}

form aside {
	margin: .2em 0 2em 0;
	font-size: 80%;
}

aside {
	float: right;
}

aside nav {
	margin: 0 0 1em 1em;
	padding: .3em .2em;
}

.heading-aligned {
	margin-top: -4em;
}

/* Toggle Switch */

.switch {
	--line: #ccc;
	--dot: var(--oc-violet-4);
	--circle: var(--oc-yellow-3);
	--background: #aaa;
	--duration: .3s;
	--text: #000;
	--shadow: 0 1px 3px rgba(0, 9, 61, 0.08);
	cursor: pointer;
	position: relative;
}
.switch:before {
	content: '';
	width: 60px;
	height: 32px;
	border-radius: 16px;
	background: var(--background);
	position: absolute;
	left: 0;
	top: 0;
	box-shadow: var(--shadow);
}
.switch input {
	display: none;
}
.switch input + div {
	position: relative;
}
.switch input + div:before, .switch input + div:after {
	--s: 1;
	content: '';
	position: absolute;
	height: 4px;
	top: 14px;
	width: 24px;
	background: var(--line);
	-webkit-transform: scaleX(var(--s));
	transform: scaleX(var(--s));
	-webkit-transition: -webkit-transform var(--duration) ease;
	transition: -webkit-transform var(--duration) ease;
	transition: transform var(--duration) ease;
	transition: transform var(--duration) ease, -webkit-transform var(--duration) ease;
}
.switch input + div:before {
	--s: 0;
	left: 4px;
	-webkit-transform-origin: 0 50%;
	transform-origin: 0 50%;
	border-radius: 2px 0 0 2px;
}
.switch input + div:after {
	left: 32px;
	-webkit-transform-origin: 100% 50%;
	transform-origin: 100% 50%;
	border-radius: 0 2px 2px 0;
}
.switch input + div span {
	padding-left: 60px;
	line-height: 28px;
	color: var(--text);
}
.switch input + div span:before {
	--x: 0;
	--b: var(--circle);
	--s: 15px;
	content: '';
	position: absolute;
	left: 4px;
	top: 4px;
	width: 24px;
	height: 24px;
	border-radius: 50%;
	box-shadow: inset 0 0 0 var(--s) var(--b);
	-webkit-transform: translateX(var(--x));
	transform: translateX(var(--x));
	-webkit-transition: box-shadow var(--duration) ease, -webkit-transform var(--duration) ease;
	transition: box-shadow var(--duration) ease, -webkit-transform var(--duration) ease;
	transition: box-shadow var(--duration) ease, transform var(--duration) ease;
	transition: box-shadow var(--duration) ease, transform var(--duration) ease, -webkit-transform var(--duration) ease;
}
.switch input + div span:not(:empty) {
	padding-left: 68px;
}
.switch input:checked + div:before {
	--s: 1;
}
.switch input:checked + div:after {
	--s: 0;
}
.switch input:checked + div span:before {
	--x: 28px;
	--s: 12px;
	--b: var(--dot);
}

body .switch + .switch {
	margin-top: 32px;
}

/* Checkboxes */

input[type=checkbox] { display: none; }
input[type=checkbox] + label:before {
	font-family: 'Font Awesome 6 Pro';
	display: inline-block;
	width: 1.3em;
}
input[type=checkbox] + label:before { content: "\f0c8"; }
input[type=checkbox]:checked + label:before { content: "\f14a"; }

/* Radio Buttons */

input[type=radio] { display: none; }
input[type=radio] + label:before {
	font-family: 'Font Awesome 6 Pro';
	display: inline-block;
	width: 1.3em;
}
input[type=radio] + label:before { content: "\f111"; }
input[type=radio]:checked + label:before { content: "\f192"; }

/* Tables */

.table-container {
	overflow-x: auto;
}

table {
	border-collapse: collapse;
	width: 100%;
}

th, td {
	background: var(--background);
	padding: .5em;
}

th {
	border-bottom: 1px solid var(--foreground);
	background: var(--background-secondary);
}

tr:nth-child(even) td {
	background: var(--background-secondary);
}

th:first-child { border-top-left-radius: 0.2em; }
th:last-child { border-top-right-radius: 0.2em; }
tr:last-child td:first-child { border-bottom-left-radius: 0.2em; }
tr:last-child td:last-child { border-bottom-right-radius: 0.2em; }

::selection {
	color: var(--background);
	background: var(--foreground);
}

@media (max-width: 900px) {
	html {
		font-size: 1.05em;
	}
	main, header, footer {
		padding: 0 1.5em;
	}
	header {
		padding-top: 2em;
	}
	header nav {
		margin: .5 0 3em 0;
	}
	footer {
		padding-top: 2em;
		padding-bottom: 3em;
	}
}

@media (max-width: 500px) {
	html {
		font-size: 1em;
	}
	main, header, footer {
		padding: 0 1em;
	}
	header {
		padding-top: 2em;
	}
	header nav {
		margin: 0 0 2em 0;
	}
	footer {
		padding-top: 2em;
		padding-bottom: 3em;
	}
}

body {
	font-family: sans-serif;
}
main {
	margin: auto;
	max-width: 50em;
	line-height: 1.5em;
}
input, textarea {
	display: block;
	width: 100%;
}
pre {
    font-family: Courier, monospace;
    font-size: 14px;
	white-space: pre-wrap;
	white-space: -moz-pre-wrap;
	white-space: -pre-wrap;
	white-space: -o-pre-wrap;
	word-wrap: break-word;
}
</style>
</head>
<body>

<header>

<h1>Auth Dasho</h1>
</header>

<main>

<form action="#results" method="post">
	<fieldset>
		<legend>Authenticate</legend>
		<p>Create an authenticated payload below.</p>
		<p>
			<label for="payload">Payload</label>
			<textarea name="payload" id="payload"><?php echo $payload; ?></textarea>
		</p>
		<p>
			<label for="key">Key</label>
			<textarea name="key" id="key"><?php echo $key; ?></textarea>
		</p>
		<input type="submit" value="Go">
	</fieldset>
</form>


<?php

echo $result;

?>

</main>

<footer>

<ul class="horizontal site">
	<li>&copy; Dasho</li>
</ul>

</footer>

</body>
</html>
