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

require_once realpath(__DIR__ . '/BSAGModule.class.php');

// Load config files
if (file_exists(realpath(__DIR__ . '/config.php')))
  require_once realpath(__DIR__ . '/config.php');
else {
  error_log('config.php does not exists.');
  exit(1);
}

$dashing_widget_name = 'bsag';

$bsag = new BSAGModule;
$bsag_result = $bsag->get_data($bsag_start);

// Initialize items array
$content = array();
$content['auth_token'] = $dashing_api_auth_token;
$content['items'] = array();

foreach ($bsag_result as $lines) {
  foreach ($lines['directions'] as $directions) {
    $i = 1;

    foreach ($directions['connections'] as $connection) {
      if ($i > 4)
        break;

      $content['items'][] = array('label' => $lines['line'] . ' ' . $directions['direction'], 'value' => date('H:i', $connection));
      $i++;
    }
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
