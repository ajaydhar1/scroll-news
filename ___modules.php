<?php

date_default_timezone_set('America/New_York');

function search_google_knowledge($query) {
  $api_key = 'AIzaSyBhQWmKz8I-IRm3lKiQcHK9NANFgnbfAf0';
  $service_url = 'https://kgsearch.googleapis.com/v1/entities:search';
  $params = array(
    'query' => $query,
    'limit' => 1,
    'indent' => TRUE,
    'key' => $api_key);
  $url = $service_url . '?' . http_build_query($params);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = json_decode(curl_exec($ch), true);
  curl_close($ch);
  //foreach($response['itemListElement'] as $element) {
  //  echo $element['result']['name'] . '<br/>';
  //}
  return $response;
}

function azeo_wiki_results_2($keyword) {
  // WIKIPEDIA SEARCH API

  $url='https://en.wikipedia.org/w/api.php?action=opensearch&limit=1&format=json&search='.strtolower($keyword); 

  $arr=azeo_getData($url); 

  $return_result = [];

  if (count($arr[3]) > 0) {
    $return_result['title'] = $arr[1][0];
    $return_result['url'] = $arr[3][0];
  }

  return $return_result;

}

function fix_image_if_broken($url) {
  $img = $url;
  if (strpos($url, 's.yimg.com') !== false) {
    if (strpos($url, 'US_AFTP_GlobeNewsWire') !== false) {
        $img = 'https://thenewshook.com/img/yahoo-placeholder.png';
    }
  }
  return $img;
}

function xml_attribute($object, $attribute) {
  if(isset($object[$attribute]))
    return (string) $object[$attribute];
}

function strip_tags_content($text, $tags = '', $invert = FALSE) { 

  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
  $tags = array_unique($tags[1]); 
  
  if(is_array($tags) AND count($tags) > 0) { 
    if($invert == FALSE) { 
      return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text); 
    } 
    else { 
      return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
    } 
  } 
  elseif($invert == FALSE) { 
    return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
  } 
  return $text; 
} 

function time_elapsed_string($ptime) {
  $etime = time() - $ptime - (4*60*60);

  if ($etime < 1) {
    return '0 seconds';
  }

  $a = array( 365 * 24 * 60 * 60  =>  'year',
               30 * 24 * 60 * 60  =>  'month',
                    24 * 60 * 60  =>  'day',
                         60 * 60  =>  'hour',
                              60  =>  'minute',
                               1  =>  'second'
              );
  
  $a_plural = array( 'year'   => 'years',
                     'month'  => 'months',
                     'day'    => 'days',
                     'hour'   => 'hours',
                     'minute' => 'minutes',
                     'second' => 'seconds'
              );

  foreach ($a as $secs => $str) {
    $d = $etime / $secs;
    if ($d >= 1) {
      $r = round($d);
      return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
    }
  }
}

function azeo_getData($url) {

    $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
            ),
        );  

    $data=file_get_contents($url, false, stream_context_create($arrContextOptions));
    $json=json_decode($data, true); 
    return $json;
}

function azeo_getData_original($url) {
	
  $data=file_get_contents($url);

  //echo $data;

  $json=json_decode($data, true);	
	
	return $json;
}

function azeo_postData($url, $params) {

  //$data=file_get_contents($url);

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  // In real life you should use something like:
  // curl_setopt($ch, CURLOPT_POSTFIELDS, 
  //          http_build_query(array('postvar1' => 'value1')));

  // Receive server response ...
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $data = curl_exec($ch);

  curl_close ($ch);

  //echo $data;

  $json=json_decode($data, true);	
	
	return $json;
}

function azeo_limit_text($text, $limit) {
  if (str_word_count($text, 0) > $limit) {
    $words = str_word_count($text, 2);
    $pos = array_keys($words);
    $text = substr($text, 0, $pos[$limit]) . '...';
  }
  return $text;
}
    
function azeo_alt_text($text) {
  $limit = 3;
  if (str_word_count($text, 0) > $limit) {
    $words = str_word_count($text, 2);
    $pos = array_keys($words);
    $text = substr($text, 0, $pos[$limit]);
  }
  return trim($text);
}

function azeo_alt_text2($imgUrl) {
  $imgUrl = urldecode($imgUrl);
  $arr = preg_split("#/#", $imgUrl);
  $imgName = end($arr);
  $name = preg_replace('/\.[^.\s]{3,4}$/', '', $imgName);
  $text = str_replace("-", " ", $name);
  $text = str_replace("_", " ", $text);
  $text = str_replace(".", " ", $text);

  return trim($text);
}

function azeo_results($url2) {

  $url='http://52.9.160.250/api/v/url-extraction'; 

  $arr=azeo_postData($url, 'url='.$url2); 
  return $arr;
    
}

function azeo_site_results($url2) {

  //$url='http://52.9.160.250/api/v/url-extraction-any-site'; 

  $url='https://news-nlp-api-08865bb82971.herokuapp.com/analyze_article';

  $arr=azeo_postData($url, 'article_url='.$url2); 
  return $arr;
    
}

function azeo_toolkit_results($text) {
    
  $url='http://52.9.160.250/api/v/document-extraction'; 

  $arr=azeo_postData($url, 'text='.urlencode($text)); 
  return $arr;
    
}


function azeo_create_link($url, $image, $pub, $des, $title, $pubDate) {
    
  $final='results.php?q='.base64_encode($url).'&image='.base64_encode($image).'&pub='.$pub.'&des='.urlencode($des).'&title='.urlencode($title).'&pubDate='.$pubDate;         
  $final = str_replace("\"","%22",$final);
  $final = str_replace("'","%27",$final);
  return $final;
    
}

function azeo_title($str) {
  $str=str_replace('-',' ',$str);
  $str=ucwords($str);
    
  return $str;    
}

function azeo_parallel_exec($arr) {

  $ch=array();

  for($i=0;$i<count($arr);$i++) {

    //azeo L1

    $ch[$i] = curl_init($arr[$i]);
    curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
  }

  // build the multi-curl handle, adding both $ch
  $mh = curl_multi_init();

  for($i=0;$i<count($arr);$i++) {
    // azeo L3
    curl_multi_add_handle($mh, $ch[$i]);
  }

  $running = null;
  do {
    curl_multi_exec($mh, $running);
  } while ($running);


  //azeo L4
  for($i=0;$i<count($arr);$i++) {
    curl_multi_remove_handle($mh, $ch[$i]);
  }

  curl_multi_close($mh);
  
  // azeo L5
  for($i=0;$i<count($arr);$i++) {
    $res[$i] = curl_multi_getcontent($ch[$i]);
  }

  return $res;
    
}

function removeUnescaped($str) {
  return str_replace('\"', '"', $str);
}

function get_words($sentence, $count = 7) {
  //preg_match("/(?:\w+(?:\W+|$)){0,$count}/", $sentence, $matches);
  //return $matches[0];

  $pieces = explode(" ", $sentence);
  $first_part = implode(" ", array_splice($pieces, 0, $count));
  return $first_part;
}


function doesntContainAny($string, array $needles) {
  foreach ($needles as $needle) {
    if (strpos($string, $needle) !== FALSE) { // Use strict comparison for accurate results
      return false; // Found a match, so the string *does* contain a substring
    }
  }
  return true; // No matches found after checking all substrings
}

function getRandomArticle() {
  $articles = [];

  $filter_out = array("usatoday", "independent.co.uk", "nytimes", "9to5google", "tomsguide", "thehockeynews", "cbssports", "businessinsider", "abc7chicago", "livescience", "wlns", "myedmondsnews", "reuters", "sportingnews", "bloomberg", "wane.com", "politico", "wvpublic", "cnbc", "mercurynews");
          
  $rss_feeds = array("US Politics" => "https://rss.app/feeds/tCdH9nGB4aPpVPLh.xml", "Health" => "https://rss.app/feeds/tZPiCoHdJqTYlcZc.xml", "Science" => "https://rss.app/feeds/tLSguoVp4t7wa1eJ.xml", "Business" => "https://rss.app/feeds/tDmGft5qv7QGmWHv.xml", "Sports" => "https://rss.app/feeds/tCQMLQm6AHeQ5hJk.xml", "Technology" => "https://rss.app/feeds/t8coleFVxgPf56NK.xml", "Politics" => "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml");

  $key = array_rand($rss_feeds);
  $value = $rss_feeds[$key];

  $rss_url = $value;

  $rss = Feed::loadRss($rss_url);

  foreach ($rss->item as $item) {
      if (doesntContainAny($item->link->__toString(), $filter_out)) {
          array_push($articles, $item->link->__toString()); 
      }
  }

  return ['category' => $key, 'link' => $articles[array_rand($articles)]];
}

function clean_string($str) {
    // Remove spaces, parentheses, and periods
    return preg_replace('/[.,\s()]/', '', $str);
}

function is_file_width_over_min($html) {
    if (preg_match('/data-file-width\s*=\s*"(\d+)"/i', $html, $matches)) {
        $width = (int)$matches[1];
        return $width > 1500;
    }
    return false; // attribute not found
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true; // Any string ends with an empty string
    }
    return substr_compare($haystack, $needle, -$length) === 0;
}

?>