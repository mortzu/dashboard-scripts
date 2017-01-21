<?php

class MensaParser {
  /*
   * Squishes a string by replacing all whitespace (including linebreaks) by one space
   */
  private function squish_string($str) {
    return preg_replace('/\s+/', ' ', trim($str));
  }

  private function parse_date($str) {
    setlocale(LC_TIME, 'de_DE.utf8');
    $date = strptime($str, '%d. %b');
    $date['tm_year'] = date('Y');

    if (isset($date['tm_mon']) && $date['tm_mon'] + 1 < date('m'))
      $date['tm_year']++;

    return @mktime(0, 0, 0, $date['tm_mon'] + 1, $date['tm_mday'], $date['tm_year']);
  }

  private function zip_lists($list1, $list2) {
    return array_map(function($key, $val) {
      return array($key, $val);
    }, $list1, $list2);
  }

  /*
   * Joins the texts of the given objects with a delimiter
   */
  private function join_by($objs, $delim) {
    $res = '';

    foreach ($objs as $o)
      $res = $res . $o . $delim;

    $res = substr($res, 0, strlen($res) - strlen($delim));

    return $res;
  }

  public function parse_menu($uri) {
    phpQuery::newDocument(file_get_contents($uri));

    $dates = phpQuery::map(pq('.tab-date'), function($date) {
      return $this->parse_date(pq($date)->text());
    });

    $days = phpQuery::map(pq('.food-plan'), function($day) {
      return array(phpQuery::map(pq($day)->find('.food-category'), function($category) {
        // Find name of the category
        $name = $this->squish_string(pq($category)->find('thead .category-name')->text());

        // Find description of dishes
        $descr = pq($category)->find('tbody .field-name-field-description');

        // Remove annotation numbers
        $descr->children()->remove('sup');

        $description = phpQuery::map($descr, function($meal) {
          return $this->squish_string(pq($meal)->text());
        });

        return array(array('name' => $name, 'meals' => $description));
      }));
    });

    return array_combine($dates, $days);
  }

  public function join_dishes($menu) {
    return array_map(function($day) {
      return array_map(function($category) {
        return array('name' => $category['name'],
                     'meal' => $this->join_by($category['meals'], ', '));
      }, $day);
    }, $menu);
  }

  public function parse_mensa($uri, $day = null) {
    $menu = $this->join_dishes($this->parse_menu($uri));

    if (is_null($day))
      return $menu;
    else {
      $_menus = zip_lists(array_keys($menu), $menu);
      $menu_on_or_after_day = array_filter($_menus, function($elem) use ($day) {
        return $elem[0] >= $day;
      });
      $f = reset($menu_on_or_after_day);
      return array('dishes' => $f[1], 'date' => $f[0]);
    }
  }
}

?>
