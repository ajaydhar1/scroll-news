<?php
error_reporting(E_ERROR | E_PARSE);
require_once("simple_html_dom.php");
require_once('___modules.php');

$entities = explode("---", $_POST['data']);

//print_r($entities);

$wiki_articles = [];

//print_r($arr['entities']);

$wiki_count = 0;
for ($i=0; $i<count($entities); $i++) {

    if ($wiki_count >= 35) {
      break;
    }

    $wik = azeo_wiki_results_2($entities[$i]);
    if (array_key_exists('title', $wik)) {
        $wiki_articles += [$wik['title'] => [$wik['url'], 2]];
        $wiki_count += 1; 
    }
}

//print_r($wiki_articles);

$themes = [];

foreach ($wiki_articles as $key => $value) {
    $already_exists = false;
    foreach ($themes as $key2 => $value2) {
        if ($value2[0] == $value[0]) {
            $already_exists = true;
            break;
        }
    }
    if (!$already_exists) {
        $themes += [$key => $value];
    }
}


//print_r($themes);

                        
/*
$themes = [
    "Biden's Build Back Better Act" => ["https://en.wikipedia.org/wiki/Build_Back_Better_Act", 2],
    "Biden's Infrastructure Bill" => ["https://en.wikipedia.org/wiki/Infrastructure_Investment_and_Jobs_Act", 2],
    "2021 United States Capitol attack" => ["https://en.wikipedia.org/wiki/2021_United_States_Capitol_attack", 1],
    "Critical Race Theory" => ["https://en.wikipedia.org/wiki/Critical_race_theory", 3],
    "COVID-19 Vaccinations" => ["https://en.wikipedia.org/wiki/COVID-19_vaccination_in_the_United_States", 3],
    "COVID-19" => ["https://en.wikipedia.org/wiki/COVID-19", 3],
    "Mexico-United States Border" => ["https://en.wikipedia.org/wiki/Mexico%E2%80%93United_States_border", 2],
    "Illegal Immigration" => ["https://en.wikipedia.org/wiki/Illegal_immigration_to_the_United_States", 3],
    "Climate Change" => ["https://en.wikipedia.org/wiki/Climate_change", 2]
  ];
*/

$theme_num = 1;

$echoed = [];


$tab_content_count = 0;
$tab_strings_array = [];
foreach ($themes as $key => $value) {

  $html = '';
  $p1 = '';
  $p2 = '';
  $p3 = '';
  $ul_1 = '';
  $ul_2 = '';
  $ul_3 = '';

  // get first two paragraphs
  $html = file_get_html($value[0]);
  
  $got_one = false;
  $got_two = false;
  $is_ul_1 = false;
  $is_ul_2 = false;
  $is_ul_3 = false;

  foreach ($html->find('p') as $p) {
    if ((strpos($p, 'mw-empty-elt') !== false)) {
      continue;
    }
    if (!$got_one) {
      $p1 = $p;

      if (in_array(((string) $p1), $echoed)) {
        continue 2;
      }

      $got_one = true;

      $next_sibling_1 = $p->next_sibling();
      if ((substr($next_sibling_1, 0, 3) == "<ul") || (substr($next_sibling_1, 0, 3) == "<dl")) {
        $ul_1 = $next_sibling_1;
        $is_ul_1 = true;
      }
    }
    else if (!$got_two) {
      $p2 = $p;
      $got_two = true;

      $next_sibling_2 = $p->next_sibling();
      if ((substr($next_sibling_2, 0, 3) == "<ul") || (substr($next_sibling_2, 0, 3) == "<dl")) {
        $ul_2 = $next_sibling_2;
        $is_ul_2 = true;
      }

    }
    else {
      $p3 = $p;

      $next_sibling_3 = $p->next_sibling();
      if ((substr($next_sibling_3, 0, 3) == "<ul") || (substr($next_sibling_3, 0, 3) == "<dl")) {
        $ul_3 = $next_sibling_3;
        $is_ul_3 = true;
      }

      break;
    }
  }

  if ($p1 == '') {
    continue;
  }


  $tab_content_string = '';

  if ($tab_content_count == 0) {
    $tab_content_string .= '<div class="tab-pane fade show active" id="nav-content-'.clean_string($key).'" role="tabpanel" aria-labelledby="nav-'.clean_string($key).'-tab">';

       $tab_content_string .= '
  
          <div class="mt-4">
            <h2>Axis '.$theme_num.': <a href="'.$value[0].'" target="_blank">'.$key.'</a></h2>
            <div class="description mb-2" style="">'; 

            $tab_content_string .= $p1;
            if ($is_ul_1) $tab_content_string .= $ul_1;
            if ($value[1] > 1) {
              $tab_content_string .= $p2;
              if ($is_ul_2) $tab_content_string .= $ul_2;
            
              if ($value[1] > 2) {
                $tab_content_string .= $p3;
                if ($is_ul_3) $tab_content_string .= $ul_3;    
              }
            }

            // Remove all <style>...</style> blocks
            $tab_content_string = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $tab_content_string);

            if (endsWith(strtolower(trim($tab_content_string)), "may refer to: </p>")) {
              continue;
            }
            else if (endsWith(strtolower(trim($tab_content_string)), 'may also refer to: </p>') || endsWith(strtolower(trim($tab_content_string)), 'can also refer to: </p>')) {
              // Remove the last <p>...</p> block
              $last_p_pos = strrpos($tab_content_string, '<p>');
              if ($last_p_pos !== false) {
                  $tab_content_string = substr($tab_content_string, 0, $last_p_pos);
              }
            }

            if ($html->find('img')) {
              $tab_content_string .= '<div class="mt-5 images">';
              foreach($html->find('img') as $element) {
                if ((strpos($element, 'Wikimedia') == false) && (strpos($element, 'MediaWiki') == false) && (strpos($element, '/static/images/') == false) && is_file_width_over_min($element)) {
                  $tab_content_string .= $element;
                }
              }
              $tab_content_string .= '</div>';
            }

            $tab_content_string .= '</div></div>';

    $tab_content_string .= '</div>';
  }


  else {
    $tab_content_string .= '<div class="tab-pane fade show" id="nav-content-'.clean_string($key).'" role="tabpanel" aria-labelledby="nav-'.clean_string($key).'-tab">';

       $tab_content_string .= '
  
          <div class="mt-4">
            <h2>Axis '.$theme_num.': <a href="'.$value[0].'" target="_blank">'.$key.'</a></h2>
            <div class="description mb-2" style="color: #545454; font-size: 17px;">'; 

            $tab_content_string .= $p1;
            if ($is_ul_1) $tab_content_string .= $ul_1;
            if ($value[1] > 1) {
              $tab_content_string .= $p2;
              if ($is_ul_2) $tab_content_string .= $ul_2;
            
              if ($value[1] > 2) {
                $tab_content_string .= $p3;
                if ($is_ul_3) $tab_content_string .= $ul_3;    
              }
            }

            // Remove all <style>...</style> blocks
            $tab_content_string = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $tab_content_string);

            if (endsWith(strtolower(trim($tab_content_string)), "may refer to: </p>")) {
              continue;
            }
            else if (endsWith(strtolower(trim($tab_content_string)), 'may also refer to: </p>') || endsWith(strtolower(trim($tab_content_string)), 'can also refer to: </p>')) {
              // Remove the last <p>...</p> block
              $last_p_pos = strrpos($tab_content_string, '<p>');
              if ($last_p_pos !== false) {
                  $tab_content_string = substr($tab_content_string, 0, $last_p_pos);
              }
            }


            if ($html->find('img')) {
              $tab_content_string .= '<div class="mt-5 images">';
              foreach($html->find('img') as $element) {
                if ((strpos($element, 'Wikimedia') == false) && (strpos($element, 'MediaWiki') == false) && (strpos($element, '/static/images/') == false) && is_file_width_over_min($element)) {
                  $tab_content_string .= $element;
                }
              }
              $tab_content_string .= '</div>';
            }

            $tab_content_string .= '</div></div>';

    $tab_content_string .= '</div>';
  }


    $theme_num = $theme_num + 1;
    $tab_content_count++;

    array_push($echoed, ((string) $p1));

    $tab_strings_array[$key] = $tab_content_string;

}


echo '

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">

';

$theme_count = 0;
foreach ($tab_strings_array as $key => $value) {

  if ($theme_count == 0) {
    echo '<button class="nav-link active" id="nav-'.clean_string($key).'-tab" data-toggle="tab" data-target="#nav-content-'.clean_string($key).'" type="button" role="tab" aria-controls="nav-home" aria-selected="true">'.$key.'</button>';
  }

  else {
    echo '<button class="nav-link" id="nav-'.clean_string($key).'-tab" data-toggle="tab" data-target="#nav-content-'.clean_string($key).'" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">'.$key.'</button>
    ';
  }

  $theme_count++;

}

echo '
  </div>
</nav>

<div class="tab-content" id="nav-tabContent">

';

foreach ($tab_strings_array as $key => $value) {
  echo $value;
}

echo '</div>';

?>