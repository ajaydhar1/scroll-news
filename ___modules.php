<?php

date_default_timezone_set('America/New_York');

$filter_out = array("usatoday", "independent.co.uk", "nytimes", "9to5google", "tomsguide", "thehockeynews", "cbssports", "businessinsider", "abc7chicago", "livescience", "wlns", "myedmondsnews", "reuters", "sportingnews", "bloomberg", "wane.com", "politico", "wvpublic", "cnbc", "mercurynews", "utahstories", "imdb", "9to5mac", "cnn", "mashable", "stpetecatalyst", "kark", "journalism.cuny.edu", "yahoo.com", "startribune", "wgntv", "msnbc", "kosu.org", "wpri.com", "theberkshireedge.com");

$rss_feeds = array("Politics" => "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml", "Business" => "https://rss.app/feeds/tDmGft5qv7QGmWHv.xml", "Technology" => "https://rss.app/feeds/t8coleFVxgPf56NK.xml", "Sports" => "https://rss.app/feeds/tCQMLQm6AHeQ5hJk.xml", "Health" => "https://rss.app/feeds/tZPiCoHdJqTYlcZc.xml", "Science" => "https://rss.app/feeds/tLSguoVp4t7wa1eJ.xml", "Entertainment" => "https://rss.app/feeds/tBiQM8jJROm1RYn3.xml");

function getPdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dbUrl = getenv('DATABASE_URL');
    if ($dbUrl) {
        $parts = parse_url($dbUrl);
        $host  = $parts['host'] ?? '127.0.0.1';
        $port  = $parts['port'] ?? 5432;
        $user  = $parts['user'] ?? '';
        $pass  = $parts['pass'] ?? '';
        $db    = ltrim($parts['path'] ?? '', '/');
        $dsn   = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    } else {
        $host = getenv('PGHOST')     ?: '127.0.0.1';
        $port = getenv('PGPORT')     ?: '5432';
        $db   = getenv('PGDATABASE') ?: 'postgres';
        $user = getenv('PGUSER')     ?: 'postgres';
        $pass = getenv('PGPASSWORD') ?: '';
        $dsn  = "pgsql:host={$host};port={$port};dbname={$db}";
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

// Tiny JSON logger
function logj(string $msg, array $ctx = []): void {
    error_log($msg . ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES));
}

function getPdoOrExplain(): ?PDO {
    // Make sure errors go to logs
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('display_errors', '0');

    // 1) Driver present?
    $drivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    if (!in_array('pgsql', $drivers, true)) {
        logj('DB init: pdo_pgsql not loaded', ['drivers' => $drivers]);
        return null;
    }

    // 2) Build DSN from DATABASE_URL (Heroku/Render) or PG* envs
    $dbUrl = getenv('DATABASE_URL') ?: '';
    if ($dbUrl) {
        $p = parse_url($dbUrl);
        $host = $p['host'] ?? '';
        $port = $p['port'] ?? 5432;
        $user = isset($p['user']) ? urldecode($p['user']) : '';
        $pass = isset($p['pass']) ? urldecode($p['pass']) : '';
        $db   = isset($p['path']) ? ltrim($p['path'], '/') : '';

        // Internal Render DB hosts (e.g., *.internal) typically don't need SSL.
        $isInternal = preg_match('/\.internal$/', $host) || in_array($host, ['localhost','127.0.0.1'], true);
        $sslmode = $isInternal ? 'prefer' : 'require';

        $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";
        try {
            logj('DB connect try', ['host' => $host, 'port' => (int)$port, 'db' => $db, 'sslmode' => $sslmode]);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Sanity ping
            $pdo->query('SELECT 1');
            logj('DB connect ok');
            return $pdo;
        } catch (Throwable $e) {
            logj('DB connect failed', ['err' => $e->getMessage(), 'code' => $e->getCode()]);
            return null;
        }
    } else {
        logj('DB init: DATABASE_URL not set');
        return null;
    }
}

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

function http_get($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_ENCODING       => '', // auto-decode gzip/deflate
    CURLOPT_USERAGENT      => 'ScrollNewsBot/1.0 (+https://scrollnews.io/contact)',
    CURLOPT_HTTPHEADER     => [
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language: en-US,en;q=0.9',
    ],
    // CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // uncomment if IPv6 issues
  ]);

  $body   = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err    = curl_error($ch);
  curl_close($ch);
  return [$status, $body, $err];
}

function azeo_wiki_results_2($keyword) {
  // WIKIPEDIA SEARCH API

  $url='https://en.wikipedia.org/w/api.php?action=opensearch&limit=1&format=json&search='.urlencode($keyword); 

  //print_r($url);

  $arr=azeo_getData($url); 

  $return_result = [];

  //print_r("Before array print");
  //print_r($arr);
  //print_r("After array print");
  //exit;

  if (is_array($arr) && count($arr[3]) > 0) {
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

    //print_r("before url");
    //print_r($url);
    //print_r("after url");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // Set the custom User-Agent string
    $userAgent = "ScrollNewsBot/1.0 (+https://scrollnews.io/contact)";
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

    $data = curl_exec($ch);

    //print_r("before data");
    //print_r($data);
    //print_r("after data");

    curl_close ($ch);
    $json=json_decode($data, true); 
    return $json;

}

function azeo_getData_second($url) {

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

  $arr=azeo_getData($url . '?url='.$url2); 
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

function getRandomArticle_fromRSS() {

  global $filter_out;
  global $rss_feeds;

  $articles = [];

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

function getRandomArticle_fromDB() {

    global $filter_out;

    $pdo = getPdoOrExplain();
    if (!$pdo) {
        logj('DB guard: falling back to RSS (no PDO)');
        return getRandomArticle_fromRSS();
    }

    // Build the filter params once
    $filters = array_map(fn($s) => '%' . $s . '%', $filter_out);

    // Only add the clause if there are filters
    $notLikeClause = '';
    if (!empty($filters)) {
        $placeholders = implode(',', array_fill(0, count($filters), '?'));
        $notLikeClause = " AND NOT (url ILIKE ANY(ARRAY[$placeholders]))";
    }

    $sql = "
      SELECT id, url, nlp, screenshot_bytes
      FROM articles
      WHERE nlp IS NOT NULL
        AND nlp NOT LIKE '%\"entities\": []%'
        AND COALESCE(octet_length(screenshot_bytes),0) > 0
      $notLikeClause
      ORDER BY RANDOM()
      LIMIT 1
    ";

    try {
        // IMPORTANT: prepare + execute WITH params
        $stmt = $pdo->prepare($sql);
        $stmt->execute($filters); // <— this must match the number of ? in ARRAY[...]
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['url'])) {
            // Nothing ready in cache; return a safe fallback shape.
            return ['category' => 'db', 'link' => null];
        }

        // Optional: keep the full row in case your caller wants it later.
        // Return shape remains backward-compatible.
        return [
            'category' => 'db',          // previously was RSS category; now mark as DB
            'link'     => $row['url'],   // the URL your app will open
            'article'  => $row           // (optional) full record for advanced use
        ];
    } catch (Throwable $e) {
        error_log("getRandomArticle DB error: " . $e->getMessage());
        return ['category' => 'db', 'link' => null];
    }
}

// Returns the row or null if not found
function getNLPFromDB(string $url) {
    
    $pdo = getPdoOrExplain();
    if (!$pdo) {
        logj('DB guard: falling back to RSS (no PDO)');
        return null;
    }

    $sql = "
        SELECT id, url, nlp, screenshot_bytes
        FROM articles
        WHERE url = :url
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':url' => $url]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return json_decode($row['nlp'], true) ?: null;
}

function clean_string($str) {
    // Remove spaces, parentheses, and periods
    return preg_replace('/[.,\s()\-\&]/', '', $str);}

function clean_headline($str) {

  return str_replace("", "", str_replace('�', '', $str));
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