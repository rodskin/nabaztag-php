<?php

$WEATHER = 1;

# weather constants
$SUNNY = 0;
$CLOUDS = 1;
$FOG = 2;
$RAIN = 3;
$SNOW = 4;
$STORMS = 5;

function encode_array($a) {
    $result = "";
    foreach($a as $e) {
        $result .= pack("C", $e);
    }

    return $result;
}

function encode_length(&$a, $length) {
    array_push($a, $length >> 16, ($length >> 8) & 0xff, $length & 0xff);
}

function encode_clear_ambient(&$a, $type) {
    array_push($a, 0, $type);
}

function encode_set_ambient(&$a, $type, $value) {
    array_push($a, $type, $value);
}

function encode_left_ear(&$a, $pos) {
    array_push($a, 4, $pos);
}

function encode_right_ear(&$a, $pos) {
    array_push($a, 5, $pos);
}

function encode_ear_positions(&$a, $left, $right) {
    array_push($a, 4, $left, 5, $right);
}

function encode_message(&$a, $text) {
    $invtable = array( 1, 171, 205, 183, 57, 163, 197, 239, 241, 27, 61, 167, 41, 19, 53, 223, 225, 139, 173, 151, 25, 131, 165, 207, 209, 251, 29, 135, 9, 243, 21, 191, 193, 107, 141, 119, 249, 99, 133, 175, 177, 219, 253, 103, 233, 211, 245, 159, 161, 75, 109, 87, 217, 67, 101, 143, 145, 187, 221, 71, 201, 179, 213, 127, 129, 43, 77, 55, 185, 35, 69, 111, 113, 155, 189, 39, 169, 147, 181, 95, 97,11, 45, 23, 153, 3, 37, 79, 81, 123, 157, 7, 137, 115, 149, 63, 65, 235, 13, 247, 121, 227, 5, 47, 49, 91, 125, 231, 105, 83, 117, 31, 33, 203, 237, 215, 89, 195, 229, 15, 17, 59, 93, 199, 73, 51, 85, 255);

    // Obfuscating algorithm by Sache

    array_push($a, 1);
    $previousChar = 35;
    for($i=0;$i<strlen($text);$i++) {
        $currentChar = ord($text[$i]);
        $code = ($invtable[$previousChar % 128]*$currentChar+47) % 256;

        $previousChar = $currentChar;
        array_push($a, $code);
    }

}

function encode_play_media($url) {
    global $ping_result_data;
    $msg = array();

    $code = "ID ".time()."\n";
    $code .= "MU ".$url."\n";
    $code .= "MW\n";

    encode_message($msg, $code);

    array_push($ping_result_data, 10);
    encode_length($ping_result_data, count($msg));
    foreach($msg as $e) { array_push($ping_result_data, $e); }
}

function process_commands($commands) {
    $result = "ID ".time()."\n";
    $parts = explode("\n", $commands);
    foreach($parts as $part) {
        if(preg_match('/^FETCH (https?:\/\/.+)/', $part, $matches) == 1) {
            $url = preg_replace_callback('/%([^%])%/', function($matches) { return $_REQUEST[$matches[1]]; }, $matches[1]);

            $result .= file_get_contents($url);
        } elseif(preg_match('/^TTS (.+)/', $part, $matches) == 1) {
            $request_url = "http://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
            $base = preg_replace('/\/[^\/]+\.php/', '/tts.php', $request_url);
            $result .= "MU ".$base."?txt=".urlencode($matches[1])."\n";
            $result .= "MW\n";
        } else {
            $result .= $part."\n";
        }
    }

    return $result;
}

?>