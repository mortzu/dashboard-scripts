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

$read_values = array();
$averages = array();
$sums = array();

$dashing_widget_name = 'fhem-temperature-average';

// Initialize items array
$content = array();
$content['auth_token'] = $dashing_api_auth_token;
$content['items'] = array();

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $fhem_url . '?cmd=jsonlist&XHR=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, $fhem_username . ':' . $fhem_password);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$output = curl_exec($ch);
curl_close($ch);

$json_decode = json_decode($output, true);

foreach ($json_decode['Results'] as $devices)
  foreach ($devices['devices'] as $device)
    if ($device['TYPE'] == 'TechemHKV')
      foreach ($device['READINGS'] as $readings)
        foreach ($readings as $key => $reading)
          if (preg_match('/^temp/', $key)) {
            $read_values[$device['NAME']][$key] = $reading;

            if (!isset($sums[$key]))
              $sums[$key] = 0;

            $sums[$key] += $reading;
          }

foreach ($sums as $key => $sum) {
  if (!isset($averages[$key]))
    $averages[$key] = 0;

  $averages[$key] = round($sums[$key] / count($read_values), 2);
}

foreach ($read_values as $key => $temp)
  $content['items'][] = array('label' => ucfirst(str_replace('hkv_', '', $key)), 'value' => str_replace('.', ',', $temp['temp2']) . ' °C / ' . str_replace('.', ',', $temp['temp1']) . ' °C');

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
