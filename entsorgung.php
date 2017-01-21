#! /usr/bin/env php
<?php

/*
2016, Moritz Kaspar Rudert (mortzu) <post@moritzrudert.de>.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of
  conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice, this list
  of conditions and the following disclaimer in the documentation and/or other materials
  provided with the distribution.

* The names of its contributors may not be used to endorse or promote products derived
  from this software without specific prior written permission.

* Feel free to send Club Mate to support the work.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
*/

// Load config files
if (file_exists(realpath(__DIR__ . '/config.php')))
  require_once realpath(__DIR__ . '/config.php');
else {
  error_log('config.php does not exists.');
  exit(1);
}

$dashing_widget_name = 'entsorgung';

// Location to get rubbish plan for
$location_street = 'Friedrich-Ebert-Straße';
$location_number = '216';

// Initialize items array
$content = array();
$content['auth_token'] = $dashing_api_auth_token;
$content['items'] = array();

// Get plan
$source = file_get_contents('http://213.168.213.236/bremereb/bify/bify.jsp?strasse=' . rawurlencode(utf8_decode($location_street)) . '&hausnummer=' . rawurlencode(utf8_decode($location_number)));

// Split
preg_match_all("|<b>.* ([0-9]{4,})</b>|", $source, $jahre);
preg_match_all("|<nobr>(.*)<nobr><br>|", $source, $matches);

$tempmonat = '';
$tempjahr = -1;

foreach ($matches[1] as $match) {
  preg_match("|\.([0-9]{2})\.|", $match, $monat);

  if ($monat[1] != $tempmonat) {
    $tempmonat = $monat[1];
    $tempjahr++;
  }

  $entry = str_replace('&nbsp;', $jahre[1][$tempjahr] . ' ', $match);
  preg_match("|([0-9]{2})\.([0-9]{2})\.([0-9]{4})|", $entry, $datum);
  $entry = utf8_encode($entry);
  $timestamp = mktime(0, 0, 0, $datum[2], $datum[1], $datum[3]);

  if ($timestamp - time() > 0 && $timestamp - time() < 60 * 60 * 24 * 14 * 2) {
    preg_match('/^((\(.+\)\s+)?([0-9]{2}\.[0-9]{2}\.)[0-9]{4})\s+(.+)/', html_entity_decode(strip_tags($entry)), $matches2);
    $content['items'][] = array('label' => $matches2[4], 'value' => $matches2[2] . $matches2[3]);
  }
}

// Initialize curl options
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $dashing_api_url . 'widgets/' . $dashing_widget_name);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, $dashing_api_username . ':' . $dashing_api_password);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));

// Fire curl call
curl_exec($ch);

// Close curl object
curl_close($ch);

?>
