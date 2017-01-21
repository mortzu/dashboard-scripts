<?php

/*
Copyright (c) 2016, Jan-Philipp Litza
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class BSAGModule {
  private function file_get_contents_post($url, $post_data) {
    $options = array('http' => array(
      'header' => "Content-type: text/plain;charset=UTF-8\r\n",
      'method' => 'POST',
      'content' => $post_data
    ));

    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
  }

  private function make_input_triple($data) {
    $out = array();

    foreach ($data as $k => $v)
      $out[] = $k . '=' . $v;

    $out[] = '';
    return implode('@', $out);
  }

  public function get_departures($station, $time = null) {
    if (!$time)
      $time = time();

    /*
     * I obtained these request values by reverse modifying the request made
     * by the "Fahrplaner" app.
     * Everything that wasn't needed at the time of writing is commented out
     * to not forge any statistics on their end or reveal too much
     * information on our end. It is still contained in this file in case
     * it's needed later on due to API changes or stricter input checking.
     */
    $bsag_url = 'http://fahrplaner.vbn.de/bin/stboard.exe/dn';
    $bsag_post = array(
      'productsFilter' => '11111111111111',
      'boardType' => 'dep',
      'L' => 'vs_java3',
      'date' => date('d.m.Y', $time),
      'time' => date('H:i', $time),
      'maxJourneys' => '50',
      'start' => 'yes',
      'inputTripelId' => $this->make_input_triple(array(
        'L' => $station,
      ))
    );

    $result_str = $this->file_get_contents_post($bsag_url, http_build_query($bsag_post));

    if ($result_str == '') {
      error_log('Could not get data!');
      exit(1);
    }

    $result_str = preg_replace_callback('/"(.*)"/Um', function($attr) {
      return strip_tags($attr[0]);
    }, $result_str);

    $result = new SimpleXMLElement($result_str);

    $connections = array();
    $now = time();

    foreach ($result->Journey as $connection) {
      preg_match('/^(\w+) (N?\d+[ES]?)#\1$/', $connection['prod'], $matches);

      if ($matches) {
        list(, $type, $line) = $matches;
        $dir = (string) $connection['dir'];

        if (!isset($connections[$line]))
          $connections[$line] = array(
            'line' => $line,
            'type' => strtolower($type),
            'directions' => array()
          );

        if (!isset($connections[$line]['directions'][$dir]))
          $connections[$line]['directions'][$dir] = array(
            'direction' => $dir,
            'connections' => array()
          );

        $time = strtotime($connection['fpTime']);

        if ($time < $now)
          continue;

        $connections[$line]['directions'][$dir]['connections'][] = strtotime($connection['fpTime']);
      }
    }

    foreach ($connections as &$connection)
      $connection['directions'] = array_values($connection['directions']);

    return $connections;
  }

  public function get_data($bsag_station) {
    $output = file_get_contents('http://fahrplaner.vbn.de/hafas/ajax-getstop.exe/dny?start=1&REQ0JourneyStopsS0A=1&REQ0JourneyStopsB=12&S=' . urlencode($bsag_station) . '&js=true&');
    preg_match_all('/@O=(?P<station>[^@]+)[^"]*L=(?P<id>\d+)@/', $output, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $station_id = $match['id'];
      break;
    }

    if (!isset($station_id) || $station_id == '')
      return array();

    $connections = $this->get_departures($station_id);
    ksort($connections);
    return array_values($connections);
  }
}

?>
