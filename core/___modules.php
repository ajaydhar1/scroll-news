<?php

require_once __DIR__ . '/config/interest.php';
require_once __DIR__ . '/render/___render.php';

$CACHE_VER = 'v13';

date_default_timezone_set('America/New_York');

$filter_out = array("usatoday", "independent.co.uk", "nytimes", "9to5google", "tomsguide", "thehockeynews", "cbssports", "businessinsider", "abc7chicago", "livescience", "wlns", "myedmondsnews", "reuters", "sportingnews", "bloomberg", "wane.com", "politico", "wvpublic", "cnbc", "mercurynews", "utahstories", "imdb", "9to5mac", "mashable", "stpetecatalyst", "kark", "journalism.cuny.edu", "yahoo.com", "startribune", "wgntv", "msnbc", "kosu.org", "wpri.com", "theberkshireedge.com", "kron4.com", "nymag.com");

$rss_feeds = array("Politics" => "https://rss.app/feeds/tahaOzLGHPxMD9OC.xml", "Business" => "https://rss.app/feeds/tDmGft5qv7QGmWHv.xml", "Technology" => "https://rss.app/feeds/t8coleFVxgPf56NK.xml", "Sports" => "https://rss.app/feeds/tCQMLQm6AHeQ5hJk.xml", "Health" => "https://rss.app/feeds/tZPiCoHdJqTYlcZc.xml", "Science" => "https://rss.app/feeds/tLSguoVp4t7wa1eJ.xml", "Entertainment" => "https://rss.app/feeds/tBiQM8jJROm1RYn3.xml");

function getPdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dbUrl = getenv('DATABASE_URL') ?: 'postgres://u54p8tqv3cg377:pf57c19d9494bc3a1f56ab4eb97c53566f24c574235c261f7a8cfba7eb648034c@c18qegamsgjut6.cluster-czrs8kj4isg7.us-east-1.rds.amazonaws.com:5432/despq103h47h7m';
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
    /*
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('display_errors', '1');
    */

    error_log("PHP_VERSION=" . PHP_VERSION);
    error_log("php.ini=" . (php_ini_loaded_file() ?: 'NONE'));
    error_log("extension_dir=" . ini_get('extension_dir'));
    error_log("drivers=" . implode(',', class_exists('PDO') ? PDO::getAvailableDrivers() : []));

    // 1) Driver present?
    $drivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];
    if (!in_array('pgsql', $drivers, true)) {
        logj('DB init: pdo_pgsql not loaded', ['drivers' => $drivers]);
        return null;
    }

    // 2) Build DSN from DATABASE_URL (Heroku/Render) or PG* envs
    $dbUrl = getenv('DATABASE_URL') ?: 'postgres://u54p8tqv3cg377:pf57c19d9494bc3a1f56ab4eb97c53566f24c574235c261f7a8cfba7eb648034c@c18qegamsgjut6.cluster-czrs8kj4isg7.us-east-1.rds.amazonaws.com:5432/despq103h47h7m';
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
  $etime = time() - $ptime;

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

/**
 * Format a Unix timestamp (sec or ms) for newsroom display.
 */
function format_news_date($rawTs, $tzId = 'America/New_York') {
  // Strip non-digits just in case and normalize ms→s
  $digits = preg_replace('/\D/', '', (string)$rawTs);
  if ($digits === '') return '';
  $ts = (int)$digits;
  if ($ts > 1000000000000) { // looks like ms
    $ts = (int)round($ts / 1000);
  }

  try {
    $tz = new DateTimeZone($tzId);
    $dt = (new DateTimeImmutable('@' . $ts))->setTimezone($tz);

    // Same-year compact vs cross-year full
    $now = new DateTimeImmutable('now', $tz);
    $fmt = ($dt->format('Y') === $now->format('Y'))
      ? 'M j • g:i A T'      // e.g., "Nov 2 • 1:03 AM ET"
      : 'M j, Y • g:i A T';  // e.g., "Dec 28, 2024 • 9:10 PM ET"

    return $dt->format($fmt);
  } catch (Throwable $e) {
    return ''; // fail quietly
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

/**
 * Convert many date/time inputs to ISO-8601 UTC with literal Z (e.g. 2025-10-04T12:00:01Z).
 *
 * @param mixed  $value      String like "Sat, 04 Oct 2025 12:00:01 GMT", "2025-10-04 08:00:01",
 *                           ISO-8601, or Unix epoch (int/float/num-string; sec or ms).
 * @param string $assumeTz   If input has no timezone, assume this one (default 'UTC').
 * @return string|null       ISO string with Z, or null if parsing fails.
 */
function toIsoZ($value, string $assumeTz = 'UTC'): ?string
{
    // 1) DateTimeInterface passthrough
    if ($value instanceof DateTimeInterface) {
        return (new DateTimeImmutable('@' . $value->getTimestamp()))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }

    // Normalize scalars
    if (is_int($value) || is_float($value) || (is_string($value) && ctype_digit(str_replace([' ', "\t"], '', $value)))) {
        // 2) Numeric epochs: detect ms vs sec
        $num = (int)trim((string)$value);
        if ($num > 0) {
            if ($num > 20000000000) { // > ~ 2001-09 in ms
                $num = (int) floor($num / 1000); // convert ms -> sec
            }
            return (new DateTimeImmutable('@' . $num))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        }
    }

    if (!is_string($value)) {
        return null;
    }

    $in = trim($value);
    if ($in === '') {
        return null;
    }

    $assumedTz = new DateTimeZone($assumeTz);

    // 3) Try a list of explicit formats first (fast & strict)
    $formats = [
        // RFC 2822 / RFC 7231 (HTTP-date)
        'D, d M Y H:i:s T',            // "Sat, 04 Oct 2025 12:00:01 GMT"
        'D, d M Y H:i:s \G\M\T',       // explicit literal GMT

        // ISO 8601 variants
        'Y-m-d\TH:i:sP',               // "2025-10-04T12:00:01+00:00"
        'Y-m-d\TH:i:s\Z',              // "2025-10-04T12:00:01Z"
        'Y-m-d\TH:i:s.uP',             // with microseconds
        'Y-m-d\TH:i:s.u\Z',

        // Common log / db formats (no tz)
        'Y-m-d H:i:s',                 // "2025-10-04 08:00:01"
        'Y-m-d H:i',                   // "2025-10-04 08:00"
        'Y/m/d H:i:s',
        'Y/m/d H:i',

        // US / EU patterns
        'm/d/Y H:i:s',
        'm/d/Y g:i:s A',
        'm/d/Y H:i',
        'd/m/Y H:i:s',
        'd/m/Y H:i',

        // Syslog-ish
        'D M d H:i:s Y',               // "Sat Oct 04 12:00:01 2025"
    ];

    foreach ($formats as $fmt) {
        $dt = DateTimeImmutable::createFromFormat($fmt, $in, $assumedTz);
        if ($dt instanceof DateTimeInterface) {
            // If input had an explicit TZ (e.g., ISO with +02:00), PHP respected it;
            // if not, we used $assumedTz. Always output UTC with literal Z:
            return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        }
    }

    // 4) Last resort: strtotime (handles many free-form strings & named tz)
    $ts = strtotime($in);
    if ($ts !== false) {
        return (new DateTimeImmutable('@' . $ts))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }

    return null; // could not parse
}

/**
 * Convert many date/time inputs to Unix epoch seconds (UTC).
 *
 * @param mixed  $value      ISO/RFC string (possibly URL-encoded), Unix epoch (sec or ms),
 *                           or DateTimeInterface.
 * @param string $assumeTz   Timezone to assume when input has no TZ info.
 * @return int|null          Unix timestamp (seconds), or null on failure.
 */
function toEpoch($value, string $assumeTz = 'UTC'): ?int
{
    // Pass-through for DateTime*:
    if ($value instanceof DateTimeInterface) {
        return $value->getTimestamp();
    }

    // Numeric epochs (string/int/float): detect ms vs sec
    if (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\s*\d+\s*$/', $value))) {
        $num = (int) trim((string)$value);
        if ($num > 20000000000) { // likely milliseconds
            $num = (int) floor($num / 1000);
        }
        return $num;
    }

    if (!is_string($value)) {
        return null;
    }

    $in = urldecode(trim($value));
    if ($in === '') return null;

    $assumedTz = new DateTimeZone($assumeTz);

    // Try explicit formats first (strict)
    $formats = [
        // RFC 2822 / HTTP-date
        'D, d M Y H:i:s T',
        'D, d M Y H:i:s \G\M\T',

        // ISO 8601 variants
        DateTimeInterface::ATOM,      // Y-m-d\TH:i:sP
        'Y-m-d\TH:i:s\Z',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:s.u\Z',

        // Common DB/log (no tz)
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y/m/d H:i:s',
        'Y/m/d H:i',

        // US/EU (no tz)
        'm/d/Y H:i:s',
        'm/d/Y g:i:s A',
        'm/d/Y H:i',
        'd/m/Y H:i:s',
        'd/m/Y H:i',

        // Syslog-ish
        'D M d H:i:s Y',
    ];

    foreach ($formats as $fmt) {
        $dt = DateTimeImmutable::createFromFormat($fmt, $in, $assumedTz);
        if ($dt instanceof DateTimeInterface) {
            return $dt->getTimestamp();
        }
    }

    // Last resort: strtotime (handles many free-form strings & named TZs)
    $ts = strtotime($in);
    return $ts === false ? null : $ts;
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
          array_push($articles, array('article_link' => $item->link->__toString(), 'publish_date' => toEpoch(toIsoZ($item->pubDate->__toString())))); 
      }
  }

  $random_article = $articles[array_rand($articles)];

  return ['category' => $key, 'link' => $random_article['article_link'], 'pub_date' => $random_article['publish_date'], 'source' => 'rss'];
}

// Helper: build "url NOT ILIKE :f0 AND url NOT ILIKE :f1 ..." + params
function buildNotILikeNamed(array $needles, string $col = 'url'): array {
    if (empty($needles)) return ['', []];
    $parts  = [];
    $params = [];
    foreach (array_values($needles) as $i => $s) {
        $key = ":f{$i}";
        $parts[] = "$col NOT ILIKE $key";
        $params[$key] = '%' . $s . '%';   // contains substring, case-insensitive
    }
    return [implode(' AND ', $parts), $params];
}

// Obtain PDO the same way you already do.
// If you have getPdoOrExplain(), this will use it; otherwise falls back to getPdo().
function _pdo_or_null() {
    if (function_exists('getPdoOrExplain')) return getPdoOrExplain();
    if (function_exists('getPdo'))         return getPdo();
    return null;
}

function getRandomArticle_fromDB_byRandom() {

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
        AND ( (nlp::jsonb) ? 'entities'
              AND jsonb_typeof((nlp::jsonb)->'entities') = 'array'
              AND jsonb_array_length((nlp::jsonb)->'entities') > 0 )
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

// ID-range sampler version
function getRandomArticle_fromDB_withoutRecent(bool $requireEntities = true): array {
    global $filter_out;                    // use your existing array
    $pdo = _pdo_or_null();
    if (!$pdo) {
        // No driver / no DATABASE_URL — keep your existing fallback
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                          : ['category' => 'db', 'link' => null];
    }

    // Add near the top of the function:
    $entitiesClause = '';
    if ($requireEntities) {
      // Requires: ..."entities": [ <something not just ] >
      $entitiesClause = " AND (nlp::text) NOT LIKE '%\"entities\": []%' AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"X-Forbidden\", \"count\": 1, \"label\": \"ORG\"}]%' AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"JavaScript\", \"count\": 1, \"label\": \"PRODUCT\"}]%' AND (nlp::text) NOT LIKE '%\"emotional_reaction\": {}%'";
    }

    // Base ready predicate (no JSON casts)
    $ready = "nlp IS NOT NULL AND COALESCE(octet_length(screenshot_bytes),0) > 0";

    // Filters: NOT ILIKE any of $filter_out
    [$notLikeSql, $notLikeParams] = buildNotILikeNamed(is_array($filter_out) ? $filter_out : []);

    // ---- 1) Get bounds over READY rows (helps the sampler jump into the range)
    $sqlBounds = "
        SELECT MIN(id) AS min_id, MAX(id) AS max_id
        FROM articles
        WHERE $ready $entitiesClause " . ($notLikeSql ? " AND $notLikeSql" : "");
    $stmt = $pdo->prepare($sqlBounds);
    $stmt->execute($notLikeParams);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);

    $minId = (int)($b['min_id'] ?? 0);
    $maxId = (int)($b['max_id'] ?? 0);
    if ($minId === 0 || $maxId === 0) {
        // No ready rows in DB — fall back
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                          : ['category' => 'db', 'link' => null];
    }

    // ---- 2) Fast ID-range picks (forward + wrap), a few attempts
    $pickFwdSql = "
        SELECT id, url
        FROM articles
        WHERE id >= :cand AND $ready $entitiesClause " . ($notLikeSql ? " AND $notLikeSql" : "") . "
        ORDER BY id ASC
        LIMIT 1";
    $pickWrapSql = "
        SELECT id, url
        FROM articles
        WHERE id < :cand AND $ready $entitiesClause " . ($notLikeSql ? " AND $notLikeSql" : "") . "
        ORDER BY id ASC
        LIMIT 1";

    $pickFwd  = $pdo->prepare($pickFwdSql);
    $pickWrap = $pdo->prepare($pickWrapSql);

    for ($i = 0; $i < 8; $i++) {
        $cand = ($maxId > $minId) ? random_int($minId, $maxId) : $minId;

        // Forward from candidate
        $params = array_merge([':cand' => $cand], $notLikeParams);
        $pickFwd->execute($params);
        $row = $pickFwd->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['url'])) {
            return [
                'category'   => 'db',
                'link'       => $row['url'],
                'article_id' => (int)$row['id']
            ];
        }

        // Wrap-around to beginning of range
        $pickWrap->execute($params);
        $row = $pickWrap->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['url'])) {
            return [
                'category'   => 'db',
                'link'       => $row['url'],
                'article_id' => (int)$row['id']
            ];
        }
    }

    // ---- 3) Last-resort: RANDOM() over READY set (still respects filters)
    $sqlFallback = "
        SELECT id, url, title
        FROM articles
        WHERE $ready $entitiesClause " . ($notLikeSql ? " AND $notLikeSql" : "") . "
        ORDER BY RANDOM()
        LIMIT 1";
    $stmt = $pdo->prepare($sqlFallback);
    $stmt->execute($notLikeParams);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['url'])) {
        return [
            'category'   => 'db',
            'link'       => $row['url'],
            'article_id' => (int)$row['id']
        ];
    }

    // Nothing matched — fall back
    return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                      : ['category' => 'db', 'link' => null];
}

// ID-range sampler version, with "recent only" window
function getRandomArticle_fromDB(bool $requireEntities = true, int $days = 35): array {
    global $filter_out;
    $pdo = _pdo_or_null();
    if (!$pdo) {
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                          : ['category' => 'db', 'link' => null, 'pub_date' => null];
    }

    // ---- Helper: figure out which timestamp column we can use
    $tsCol = 'updated_at';
    /*
    $tsCol = null;
    try {
        $candidates = ['published_at','created_at','indexed_at','added_at','first_seen_at','crawled_at'];
        $in = implode(",", array_map(fn($i) => $pdo->quote($i), $candidates));
        $chk = $pdo->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'articles' AND column_name IN ($in)
            ORDER BY
              CASE column_name
                WHEN 'published_at' THEN 1
                WHEN 'created_at'  THEN 2
                WHEN 'indexed_at'  THEN 3
                WHEN 'added_at'    THEN 4
                WHEN 'first_seen_at' THEN 5
                WHEN 'crawled_at'  THEN 6
                ELSE 99
              END
            LIMIT 1
        ");
        $tsCol = ($chk && ($row = $chk->fetch(PDO::FETCH_ASSOC))) ? $row['column_name'] : null;
    } catch (\Throwable $e) {
        $tsCol = null; // proceed without date column if anything odd happens
    }
    */

    // Entities filter (string-based, keeps you clear of JSONB regex issues)
    $entitiesClause = '';
    if ($requireEntities) {
      $entitiesClause =
        " AND (nlp::text) NOT LIKE '%\"entities\": []%'".
        " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"X-Forbidden\", \"count\": 1, \"label\": \"ORG\"}]%'".
        " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"JavaScript\", \"count\": 1, \"label\": \"PRODUCT\"}]%'".
        " AND (nlp::text) NOT LIKE '%\"emotional_reaction\": {}%'";
    }

    // Ready predicate
    $ready = "nlp IS NOT NULL AND COALESCE(octet_length(screenshot_bytes),0) > 0";

    // Filters: NOT ILIKE any of $filter_out
    [$notLikeSql, $notLikeParams] = buildNotILikeNamed(is_array($filter_out) ? $filter_out : []);

    // Build the "recent" WHERE part + params, widening window if needed
    $recentWhere = '';
    $recentParams = [];
    $windowDays  = $days;

    $buildRecent = function(int $d) use ($tsCol) {
        if ($tsCol) {
            return [" AND {$tsCol} >= :since_ts", [':since_ts' => (new DateTimeImmutable("now"))->modify("-{$d} days")->format('Y-m-d H:i:s')]];
        }
        // If no timestamp column, we can’t time-filter reliably; return empty and rely on ID recency bias.
        return ['', []];
    };

    // Try primary window
    [$recentWhere, $recentParams] = $buildRecent($windowDays);

    // ---- 1) Bounds over READY & RECENT rows
    $sqlBounds = "
        SELECT MIN(id) AS min_id, MAX(id) AS max_id
        FROM articles
        WHERE $ready $entitiesClause $recentWhere " . ($notLikeSql ? " AND $notLikeSql" : "");
    $stmt = $pdo->prepare($sqlBounds);
    $stmt->execute(array_merge($recentParams, $notLikeParams));
    $b = $stmt->fetch(PDO::FETCH_ASSOC);

    $minId = (int)($b['min_id'] ?? 0);
    $maxId = (int)($b['max_id'] ?? 0);

    // If nothing in the narrow window and we DO have a ts column, widen to ~90 days
    if ($tsCol && ($minId === 0 || $maxId === 0)) {
        $windowDays = 90;
        [$recentWhere, $recentParams] = $buildRecent($windowDays);
        $stmt = $pdo->prepare($sqlBounds);
        $stmt->execute(array_merge($recentParams, $notLikeParams));
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        $minId = (int)($b['min_id'] ?? 0);
        $maxId = (int)($b['max_id'] ?? 0);
    }

    // If still nothing and we had *no* ts column, we proceed without date restriction (legacy behavior)
    if (($minId === 0 || $maxId === 0) && !$tsCol) {
        // Try legacy bounds without a recentWhere
        $sqlBoundsLegacy = "
            SELECT MIN(id) AS min_id, MAX(id) AS max_id
            FROM articles
            WHERE $ready $entitiesClause " . ($notLikeSql ? " AND $notLikeSql" : "");
        $stmt = $pdo->prepare($sqlBoundsLegacy);
        $stmt->execute($notLikeParams);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        $minId = (int)($b['min_id'] ?? 0);
        $maxId = (int)($b['max_id'] ?? 0);
    }

    if ($minId === 0 || $maxId === 0) {
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                          : ['category' => 'db', 'link' => null, 'pub_date' => null];
    }

    // ---- 2) Fast ID-range picks constrained by RECENT
    $commonWhere = "$ready $entitiesClause $recentWhere" . ($notLikeSql ? " AND $notLikeSql" : "");

    $pickFwdSql = "
        SELECT id, url, created_at
        FROM articles
        WHERE id >= :cand AND $commonWhere
        ORDER BY id ASC
        LIMIT 1";
    $pickWrapSql = "
        SELECT id, url, created_at
        FROM articles
        WHERE id < :cand AND $commonWhere
        ORDER BY id ASC
        LIMIT 1";

    $pickFwd  = $pdo->prepare($pickFwdSql);
    $pickWrap = $pdo->prepare($pickWrapSql);

    for ($i = 0; $i < 8; $i++) {
        $cand = ($maxId > $minId) ? random_int($minId, $maxId) : $minId;

        $params = array_merge([':cand' => $cand], $recentParams, $notLikeParams);

        // Forward from candidate
        $pickFwd->execute($params);
        $row = $pickFwd->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['url'])) {
            return [
                'category'   => 'db',
                'link'       => $row['url'],
                'article_id' => (int)$row['id'],
                'pub_date'   => toEpoch(toIsoZ($row['created_at']))
            ];
        }

        // Wrap-around
        $pickWrap->execute($params);
        $row = $pickWrap->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['url'])) {
            return [
                'category'   => 'db',
                'link'       => $row['url'],
                'article_id' => (int)$row['id'],
                'pub_date'   => toEpoch(toIsoZ($row['created_at']))
            ];
        }
    }

    // ---- 3) RANDOM over READY & RECENT set
    $sqlFallback = "
        SELECT id, url, title, created_at
        FROM articles
        WHERE $commonWhere
        ORDER BY RANDOM()
        LIMIT 1";
    $stmt = $pdo->prepare($sqlFallback);
    $stmt->execute(array_merge($recentParams, $notLikeParams));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['url'])) {
        return [
            'category'   => 'db',
            'link'       => $row['url'],
            'article_id' => (int)$row['id'],
            'pub_date'   => toEpoch(toIsoZ($row['created_at']))
        ];
    }

    // Nothing matched — fall back
    return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
                                                      : ['category' => 'db', 'link' => null, 'pub_date' => null];
}

/**
 * Recent-weighted article picker:
 * - Filters to READY + NLP + non-empty screenshot + notLike filters
 * - Restricts to the last $days days using $tsCol
 * - Pulls up to $limit most recent rows
 * - Picks one in PHP with an exponential decay weight so
 *   newest articles are much more likely.

$decay values:
  0.15 → super aggressive (top 20–40 rows dominate)
  0.13 → still aggressive, but ~2–3 days start to appear
  0.12 → noticeably smoother (good mix of last 2–4 days)
  0.11 → softer bias, pulls from ~5–7 days more often
  0.10 → gentle slope, pulls from ~7–10 days regularly

 */
function getRecentWeightedArticle_fromDB(
    bool $requireEntities = true,
    int $days = 35,
    int $limit = 500,       // how many recent rows to consider
    float $decay = 0.12     // higher = stronger bias to the top (baseline: 0.15)
): array {
    global $filter_out;
    $pdo = _pdo_or_null();
    if (!$pdo) {
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
            : ['category' => 'db', 'link' => null, 'pub_date' => null];
    }

    // Timestamp column we trust for recency
    $tsCol = 'updated_at';

    // Entities filter (same as your other function)
    $entitiesClause = '';
    if ($requireEntities) {
        $entitiesClause =
            " AND (nlp::text) NOT LIKE '%\"entities\": []%'".
            " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"X-Forbidden\", \"count\": 1, \"label\": \"ORG\"}]%'".
            " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"JavaScript\", \"count\": 1, \"label\": \"PRODUCT\"}]%'".
            " AND (nlp::text) NOT LIKE '%\"emotional_reaction\": {}%'";
    }

    // Ready predicate
    $ready = "nlp IS NOT NULL AND COALESCE(octet_length(screenshot_bytes),0) > 0";

    // Filters: NOT ILIKE any of $filter_out
    [$notLikeSql, $notLikeParams] = buildNotILikeNamed(is_array($filter_out) ? $filter_out : []);

    // Time window
    $sinceTs = (new DateTimeImmutable('now'))
        ->modify("-{$days} days")
        ->format('Y-m-d H:i:s');

    $recentWhere = $tsCol
        ? " AND {$tsCol} >= :since_ts"
        : ''; // fallback if tsCol somehow missing

    $commonWhere = "$ready $entitiesClause $recentWhere" . ($notLikeSql ? " AND $notLikeSql" : "");

    // Pull a capped list of the *most recent* matching articles
    $sql = "
        SELECT id, url, created_at
        FROM articles
        WHERE $commonWhere
        ORDER BY {$tsCol} DESC
        LIMIT :limit_rows
    ";

    $stmt = $pdo->prepare($sql);

    $params = array_merge(
        $notLikeParams,
        $tsCol ? [':since_ts' => $sinceTs] : [],
        [':limit_rows' => $limit]  // <-- include limit here
    );

    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        // Fall back to your existing random function or RSS
        return function_exists('getRandomArticle_fromDB')
            ? getRandomArticle_fromDB($requireEntities, $days)
            : (function_exists('getRandomArticle_fromRSS')
                ? getRandomArticle_fromRSS()
                : ['category' => 'db', 'link' => null, 'pub_date' => null]);
    }

    // --- Weighted pick in PHP ---
    // Newest row is index 0. We assign weight w_i = exp(-decay * i)
    $weights = [];
    $totalWeight = 0.0;
    $n = count($rows);

    for ($i = 0; $i < $n; $i++) {
        $w = exp(-$decay * $i);
        $weights[$i] = $w;
        $totalWeight += $w;
    }

    $r = mt_rand() / mt_getrandmax() * $totalWeight;
    $acc = 0.0;
    $chosenIndex = 0;

    for ($i = 0; $i < $n; $i++) {
        $acc += $weights[$i];
        if ($r <= $acc) {
            $chosenIndex = $i;
            break;
        }
    }

    $row = $rows[$chosenIndex];

    if (empty($row['url'])) {
        // Safety fallback if something is weird
        return function_exists('getRandomArticle_fromDB')
            ? getRandomArticle_fromDB($requireEntities, $days)
            : (function_exists('getRandomArticle_fromRSS')
                ? getRandomArticle_fromRSS()
                : ['category' => 'db', 'link' => null, 'pub_date' => null]);
    }

    return [
        'category'   => 'db',
        'link'       => $row['url'],
        'article_id' => (int)$row['id'],
        'pub_date'   => toEpoch(toIsoZ($row['created_at'])),
    ];
}

/**
 * Recent-weighted article picker for the "Stumble" button:
 * - Filters to NLP-ready articles (no screenshot requirement)
 * - Applies entity filters (unless $requireEntities = false)
 * - Respects global $filter_out NOT ILIKE terms
 * - Restricts to the last $days days using $tsCol
 * - Pulls up to $limit most recent rows
 * - Picks one in PHP with an exponential decay weight so
 *   newest articles are much more likely.
 *
 * This is similar to getRecentWeightedArticle_fromDB(), but
 * DOES NOT require screenshot_bytes to be present.
 */
function getRecentWeightedArticle_forStumble_fromDB(
    bool $requireEntities = true,
    int $days = 35,
    int $limit = 500,
    float $decay = 0.12
): array {
    global $filter_out;
    $pdo = _pdo_or_null();
    if (!$pdo) {
        return function_exists('getRandomArticle_fromRSS') ? getRandomArticle_fromRSS()
            : ['category' => 'db', 'link' => null, 'pub_date' => null];
    }

    // Timestamp column we trust for recency
    $tsCol = 'updated_at';

    // Entities filter (same as your other function)
    $entitiesClause = '';
    if ($requireEntities) {
        $entitiesClause =
            " AND (nlp::text) NOT LIKE '%\"entities\": []%'".
            " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"X-Forbidden\", \"count\": 1, \"label\": \"ORG\"}]%'".
            " AND (nlp::text) NOT LIKE '%\"entities\": [{\"text\": \"JavaScript\", \"count\": 1, \"label\": \"PRODUCT\"}]%'".
            " AND (nlp::text) NOT LIKE '%\"emotional_reaction\": {}%'";
    }

    // Ready predicate for STUMBLE:
    // NLP must be present, but we do NOT require screenshot_bytes
    $ready = "nlp IS NOT NULL";

    // Filters: NOT ILIKE any of $filter_out
    [$notLikeSql, $notLikeParams] = buildNotILikeNamed(is_array($filter_out) ? $filter_out : []);

    // Time window
    $sinceTs = (new DateTimeImmutable('now'))
        ->modify("-{$days} days")
        ->format('Y-m-d H:i:s');

    $recentWhere = $tsCol
        ? " AND {$tsCol} >= :since_ts"
        : '';

    $commonWhere = "$ready $entitiesClause $recentWhere" . ($notLikeSql ? " AND $notLikeSql" : "");

    // Pull a capped list of the *most recent* matching articles
    $sql = "
        SELECT id, url, source_slug, created_at
        FROM articles
        WHERE $commonWhere
        ORDER BY {$tsCol} DESC
        LIMIT :limit_rows
    ";

    $stmt = $pdo->prepare($sql);

    $params = array_merge(
        $notLikeParams,
        $tsCol ? [':since_ts' => $sinceTs] : [],
        [':limit_rows' => $limit]
    );

    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        // Fall back to your existing random function or RSS
        return function_exists('getRandomArticle_fromDB')
            ? getRandomArticle_fromDB($requireEntities, $days)
            : (function_exists('getRandomArticle_fromRSS')
                ? getRandomArticle_fromRSS()
                : ['category' => 'db', 'link' => null, 'pub_date' => null]);
    }

    // --- Weighted pick in PHP ---
    // Newest row is index 0. We assign weight w_i = exp(-decay * i)
    $weights = [];
    $totalWeight = 0.0;
    $n = count($rows);

    for ($i = 0; $i < $n; $i++) {
        $w = exp(-$decay * $i);
        $weights[$i] = $w;
        $totalWeight += $w;
    }

    $r = mt_rand() / mt_getrandmax() * $totalWeight;
    $acc = 0.0;
    $chosenIndex = 0;

    for ($i = 0; $i < $n; $i++) {
        $acc += $weights[$i];
        if ($r <= $acc) {
            $chosenIndex = $i;
            break;
        }
    }

    $row = $rows[$chosenIndex];

    if (empty($row['url'])) {
        // Safety fallback if something is weird
        return function_exists('getRandomArticle_fromDB')
            ? getRandomArticle_fromDB($requireEntities, $days)
            : (function_exists('getRandomArticle_fromRSS')
                ? getRandomArticle_fromRSS()
                : ['category' => 'db', 'link' => null, 'pub_date' => null]);
    }

    return [
        'category' => isset($row['source_slug']) ? ucfirst($row['source_slug']) : 'db',
        'link'       => $row['url'],
        'article_id' => (int)$row['id'],
        'pub_date'   => toEpoch(toIsoZ($row['created_at'])),
        'source'     => 'db',
    ];
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

/**
 * Fetch full article row (including NLP + media_url) by canonical URL.
 *
 * Returns:
 *   - null if not found
 *   - assoc array:
 *       [
 *         'id'            => int,
 *         'url'           => string,
 *         'title'         => ?string,
 *         'description'   => ?string,
 *         'author'        => ?string,
 *         'pub_date'      => ?string (ISO / DB datetime),
 *         'media_url'     => ?string,
 *         'rss_item_id'   => ?int,
 *         'feed_id'       => ?int,
 *         'source_slug'   => ?string,
 *         'screenshot_bytes' => ?string (raw bytes or null),
 *         'nlp'           => ?array (decoded JSON),
 *       ]
 */
function getArticleFromDBByUrl(string $url): ?array
{
    $pdo = getPdoOrExplain();
    if (!$pdo) {
        logj('DB guard: getArticleFromDBByUrl() falling back (no PDO)');
        return null;
    }

    $sql = "
        SELECT
            id,
            url,
            title,
            description,
            author,
            pub_date,
            media_url,
            rss_item_id,
            feed_id,
            source_slug,
            nlp,
            screenshot_bytes
        FROM articles
        WHERE url = :url
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':url' => $url]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    // Decode NLP JSON into a PHP array (but keep the original row fields too)
    $nlp = null;
    if (!empty($row['nlp'])) {
        $decoded = json_decode($row['nlp'], true);
        if (is_array($decoded)) {
            $nlp = $decoded;
        }
    }

    return [
        'id'              => (int) $row['id'],
        'url'             => $row['url'],
        'title'           => $row['title'] ?? null,
        'description'     => $row['description'] ?? null,
        'author'          => $row['author'] ?? null,
        'pub_date'        => $row['pub_date'] ?? null,
        'media_url'       => $row['media_url'] ?? null,
        'rss_item_id'     => isset($row['rss_item_id']) ? (int) $row['rss_item_id'] : null,
        'feed_id'         => isset($row['feed_id']) ? (int) $row['feed_id'] : null,
        'source_slug'     => $row['source_slug'] ?? null,
        'screenshot_bytes'=> $row['screenshot_bytes'] ?? null,
        'nlp'             => $nlp,
    ];
}


function sn_get_latest_articles(int $limit = 12): array {

    $pdo = _pdo_or_null();

    $sql = "
        SELECT id, url, screenshot_bytes, created_at
        FROM articles
        WHERE screenshot_bytes IS NOT NULL
        ORDER BY created_at DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch a single RSS feed and return normalized items.
 *
 * @param string $url
 * @param string $category  e.g. "Business"
 * @param int    $limit
 * @return array
 */
function sn_fetch_rss_feed_items(string $url, string $category, int $limit = 8): array
{
    $items = [];

    // suppress warnings on network/XML issues
    $xml = @simplexml_load_file($url);
    if ($xml === false || !isset($xml->channel->item)) {
        return $items;
    }

    // media: namespace
    $media_ns = 'http://search.yahoo.com/mrss/';

    $count = 0;
    foreach ($xml->channel->item as $item) {
        if ($count >= $limit) break;
        $count++;

        $title = trim((string)$item->title);
        $link  = trim((string)$item->link);
        $pub   = trim((string)$item->pubDate);

        // Default image: try media:content
        $image_url = null;
        $media = $item->children($media_ns);
        if (isset($media->content)) {
            // media:content can be multiple; handle first
            $image_url = (string)$media->content[0]['url'];
        }

        // Fallback: extract <img src="..."> from description
        if (!$image_url && isset($item->description)) {
            $desc = (string)$item->description;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $desc, $m)) {
                $image_url = $m[1];
            }
        }

        // Normalize published_at as timestamp
        $ts = $pub ? strtotime($pub) : null;

        // “Source” — we can use the category + domain
        $host = null;
        if ($link) {
            $parsed = parse_url($link);
            if (!empty($parsed['host'])) {
                $host = $parsed['host'];
            }
        }

        $source_parts = [];
        if ($category) {
            $source_parts[] = $category;
        }
        if ($host) {
            $source_parts[] = $host;
        }
        $source = implode(' • ', $source_parts);

        $items[] = [
            'title'        => $title,
            'url'          => $link,
            'image_url'    => $image_url,
            'source'       => $source,
            'published_at' => $ts,      // unix timestamp
        ];
    }

    return $items;
}

/**
 * Get merged + sorted headlines for homepage.
 *
 * @param int $limit total articles to return after merging
 * @return array
 */
function sn_get_homepage_headlines(int $limit = 13): array
{
    global $rss_feeds;

    $all = [];

    foreach ($rss_feeds as $category => $url) {
        $feed_items = sn_fetch_rss_feed_items($url, $category, 6); // per-feed limit
        $all = array_merge($all, $feed_items);
    }

    // Sort by published_at DESC (newest first)
    usort($all, function ($a, $b) {
        $ta = $a['published_at'] ?? 0;
        $tb = $b['published_at'] ?? 0;
        return $tb <=> $ta;
    });

    // Trim to desired overall limit
    if (count($all) > $limit) {
        $all = array_slice($all, 0, $limit);
    }

    return $all;
}

function normalize_headline(string $s): string
{
    // If it’s not valid UTF-8, assume Win-1252 and convert
    if (!mb_check_encoding($s, 'UTF-8')) {
        $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }

    // Fix common mojibake / stray characters
    $replacements = [
        // Smart quotes
        "â€˜" => "‘",
        "â€™" => "’",
        "â€œ" => "“",
        "â€" => "”",

        // Dashes
        "â€“" => "–",
        "â€”" => "—",

        // Non-breaking space / stray Â
        "Â "  => " ",
        "Â"   => "",

        // Fallback: these specific cp1252 chars sometimes leak as raw bytes
        "\x91" => "‘",
        "\x92" => "’",
        "\x93" => "“",
        "\x94" => "”",
        "\x96" => "–",
    ];

    return strtr($s, $replacements);
}

function clean_string($str) {
    // Remove spaces, parentheses, and periods
    return preg_replace('/[.,\s()\-\&]/', '', $str);}

function clean_headline(string $str): string
{
    // Specific targeted fixes FIRST
    // Replace the mojibake combo: '  →  – 
    $str = str_replace(["'", "", "�“"], "–", $str);

    // Strip the classic broken characters (already-lost data)
    $str = str_replace(
        [
            "�",                // replacement diamond
            "",               // stray cp1252 control char
            "\xEF\xBF\xBD"     // UTF-8 replacement char explicitly
        ],
        "",
        $str
    );

    // Collapse excess whitespace after replacements/stripping
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
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


/**
 * NLP-powered search over the articles table.
 *
 * $opts:
 *   - 'emotion'     => e.g. 'Sad', 'Love', 'Wow'
 *   - 'sentiment'   => e.g. 'positive', 'negative', 'neutral'
 *   - 'range'       => '24h', 'older', 'all'
 *   - 'high_signal' => bool, limit to high-signal publishers (cnn, nbc, ...)
 */
function search_nlp(PDO $db, ?string $q = '', array $opts = []): array
{
    global $SCROLL_HIGH_SIGNAL_PUBLISHERS;

    $emotion      = $opts['emotion']     ?? null;
    $sentiment    = $opts['sentiment']   ?? null;
    $range        = $opts['range']       ?? 'all';
    $highSignal   = !empty($opts['high_signal']);
    $deepDive     = !empty($opts['deep_dive']);

    $conds  = [];
    $params = [];

    // Only consider rows that actually have NLP JSON
    $conds[] = "nlp IS NOT NULL";

    // And only those with a real pub_date
    $conds[] = "pub_date IS NOT NULL";

    // --- Time window ---
    if ($range === '24h') {
        $conds[] = "pub_date >= NOW() - INTERVAL '24 hours'";
    } elseif ($range === 'older') {
        $conds[] = "pub_date < NOW() - INTERVAL '24 hours'";
    }

    // --- Sentiment filter ---
    if ($sentiment) {
        // nlp.sentiment.label  (e.g. "positive")
        $conds[] = "(nlp->'sentiment'->>'label') = :sentiment";
        $params[':sentiment'] = $sentiment;
    }

    // --- Emotion filter ---
    // emotional_reaction is an object with keys like "Wow", "Love", etc.
    // We treat "emotion" as "this key exists in emotional_reaction"
    if ($emotion) {
        // Look up that key; if it exists, ->> returns a value instead of NULL
        $conds[] = "(nlp->'emotional_reaction'->>:emotion) IS NOT NULL";
        $params[':emotion'] = $emotion;
    }

    // --- High-signal publishers filter (by URL substring) ---
    if ($highSignal && !empty($SCROLL_HIGH_SIGNAL_PUBLISHERS)) {
        $domains      = array_keys($SCROLL_HIGH_SIGNAL_PUBLISHERS);
        $domainConds  = [];

        foreach ($domains as $i => $domain) {
            // normalize: strip www and lowercase
            $domain = strtolower(preg_replace('/^www\./i', '', $domain));
            $ph = ":hs{$i}";
            // match domain as substring of the URL
            $domainConds[] = "LOWER(url) LIKE $ph";
            $params[$ph]   = '%' . $domain . '%';
        }

        if (!empty($domainConds)) {
            $conds[] = '(' . implode(' OR ', $domainConds) . ')';
        }
    }

    // --- Deep dive filter ---
    if ($deepDive && defined('SCROLL_INTEREST_ENTITY_THRESHOLD') && (SCROLL_INTEREST_ENTITY_THRESHOLD >= 1)) {
        $conds[] = "(
            SELECT COALESCE(SUM((ent->>'count')::int), 0)
            FROM jsonb_array_elements(
                CASE
                    WHEN jsonb_typeof(nlp->'entities') = 'array' THEN nlp->'entities'
                    ELSE '[]'::jsonb
                END
            ) AS ent
        ) >= :min_entity_count";

        $params[':min_entity_count'] = SCROLL_INTEREST_ENTITY_THRESHOLD;
    }

    // --- Text / topic / keyword / entity search (ALL terms must match somewhere) ---
    $q = trim((string)$q);
    if ($q !== '') {
        $conds[] = "NOT EXISTS (
            SELECT 1
            FROM unnest(
                regexp_split_to_array(
                    regexp_replace(:q, '[^[:alnum:] ]+', ' ', 'g'),
                    E'\\\\s+'
                )
            ) AS t(term)
            WHERE term <> ''
            AND NOT (
                -- Title
                title ILIKE '%' || term || '%'

                -- Topics: object keys (safe even if topics missing)
                OR EXISTS (
                    SELECT 1
                    FROM jsonb_object_keys(
                        CASE
                            WHEN jsonb_typeof(nlp->'topics') = 'object' THEN nlp->'topics'
                            ELSE '{}'::jsonb
                        END
                    ) AS k(key)
                    WHERE key ILIKE '%' || term || '%'
                )

                -- Keywords: only if it's actually an array
                OR EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements_text(
                        CASE
                            WHEN jsonb_typeof(nlp->'keywords') = 'array' THEN nlp->'keywords'
                            ELSE '[]'::jsonb
                        END
                    ) AS kw(val)
                    WHERE val ILIKE '%' || term || '%'
                )

                -- Entities: only if it's actually an array; match ent->>'text'
                OR EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements(
                        CASE
                            WHEN jsonb_typeof(nlp->'entities') = 'array' THEN nlp->'entities'
                            ELSE '[]'::jsonb
                        END
                    ) AS ent(obj)
                    WHERE COALESCE(ent.obj->>'text', '') ILIKE '%' || term || '%'
                )
            )
        )";

        $params[':q'] = $q;
    }

    $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

    $sql = "
        SELECT
            id,
            title,
            url,
            source_slug,
            media_url,
            pub_date,
            nlp
        FROM articles
        $where
        ORDER BY pub_date DESC
        LIMIT 100
    ";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // Always log the real error server-side
        error_log('search_nlp ERROR: ' . $e->getMessage());
        error_log('search_nlp SQL: ' . $sql);
        error_log('search_nlp PARAMS: ' . json_encode($params));

        // Let the caller / controller decide how to respond
        return [];
    }

}


/**
 * Classic keyword search over rss_items, joined to feeds + articles.
 *
 * $opts:
 *   - 'range'       => '24h', 'older', 'all'
 *   - 'high_signal' => bool, limit to high-signal publishers
 */
function search_classic(PDO $db, string $q, array $opts = []): array
{
    global $SCROLL_HIGH_SIGNAL_PUBLISHERS;

    $range      = $opts['range']       ?? 'all';
    $highSignal = !empty($opts['high_signal']);

    // If no query AND not filtering by high-signal, nothing to search
    if ($q === '' && !$highSignal) {
        return [];
    }

    $conds  = [];
    $params = [];

    // Text condition
    if ($q !== '') {
        $conds[] = "(
            ri.title ILIKE :q
            OR ri.description ILIKE :q
        )";
        $params[':q'] = '%' . $q . '%';
    }

    // Optional time range filter on rss_items.pub_date
    if ($range === '24h') {
        $conds[] = "ri.pub_date >= NOW() - INTERVAL '24 hours'";
    } elseif ($range === 'older') {
        $conds[] = "ri.pub_date < NOW() - INTERVAL '24 hours'";
    }

    // High-signal publishers filter (based on ri.link URL substring)
    if ($highSignal && !empty($SCROLL_HIGH_SIGNAL_PUBLISHERS)) {
        $domains      = array_keys($SCROLL_HIGH_SIGNAL_PUBLISHERS);
        $domainConds  = [];

        foreach ($domains as $i => $domain) {
            // normalize: strip www and lowercase
            $domain = strtolower(preg_replace('/^www\./i', '', $domain));
            $ph = ":hs{$i}";
            $domainConds[] = "LOWER(ri.link) LIKE $ph";
            $params[$ph]   = '%' . $domain . '%';
        }

        if (!empty($domainConds)) {
            $conds[] = '(' . implode(' OR ', $domainConds) . ')';
        }
    }

    $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

    $sql = "
        SELECT
            ri.id,
            ri.title,
            ri.link,
            ri.pub_date,
            ri.media_url,
            f.name AS feed_name,
            a.id  AS article_id,
            a.nlp
        FROM rss_items ri
        JOIN feeds f
          ON f.id = ri.feed_id
        LEFT JOIN articles a
          ON a.url = ri.link
        $where
        ORDER BY
            ri.pub_date DESC NULLS LAST,
            ri.id DESC
        LIMIT 100
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function sn_intel_sentiment_counts(PDO $db): array
{
    $sql = "
        SELECT
            (nlp->'sentiment'->>'label') AS label,
            COUNT(*) AS count
        FROM articles
        WHERE nlp IS NOT NULL
          AND pub_date IS NOT NULL
          AND pub_date >= NOW() - INTERVAL '24 hours'
        GROUP BY (nlp->'sentiment'->>'label')
    ";

    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [label => count]

    // Normalize / ensure keys exist
    return [
        'positive' => (int)($rows['positive'] ?? 0),
        'neutral'  => (int)($rows['neutral']  ?? 0),
        'negative' => (int)($rows['negative'] ?? 0),
    ];
}

function sn_intel_recent_sentiment_articles(PDO $db, string $label, int $limit = 3): array
{
    $sql = "
        SELECT
            id,
            title,
            url,
            source_slug,
            pub_date
        FROM articles
        WHERE nlp IS NOT NULL
          AND pub_date IS NOT NULL
          AND pub_date >= NOW() - INTERVAL '24 hours'
          AND (nlp->'sentiment'->>'label') = :label
        ORDER BY pub_date DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':label', $label, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sn_intel_emotion_counts(PDO $db, array $emotions = ['Wow','Love','Sad']): array
{
    // We’ll loop in PHP and reuse one query
    $out = [];
    foreach ($emotions as $emotion) {
        $sql = "
            SELECT COUNT(*) AS count
            FROM articles
            WHERE nlp IS NOT NULL
              AND pub_date IS NOT NULL
              AND pub_date >= NOW() - INTERVAL '24 hours'
              AND (nlp->'emotional_reaction'->>:emotion) IS NOT NULL
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':emotion' => $emotion]);
        $out[$emotion] = (int)$stmt->fetchColumn();
    }
    return $out;
}

function sn_intel_recent_emotion_articles(PDO $db, string $emotion, int $limit = 3): array
{
    $sql = "
        SELECT
            id,
            title,
            url,
            source_slug,
            pub_date
        FROM articles
        WHERE nlp IS NOT NULL
          AND pub_date IS NOT NULL
          AND pub_date >= NOW() - INTERVAL '24 hours'
          AND (nlp->'emotional_reaction'->>:emotion) IS NOT NULL
        ORDER BY pub_date DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':emotion', $emotion, PDO::PARAM_STR);
    $stmt->bindValue(':limit',   $limit,   PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sn_intel_trending_entities(PDO $db, array $labels = ['PERSON','ORG','GPE'], int $limit = 5): array
{
    $sql = "
        SELECT
            ent->>'text'  AS entity_text,
            ent->>'label' AS entity_label,
            COUNT(*)      AS mentions,
            COUNT(DISTINCT a.id) AS article_count
        FROM articles a
        CROSS JOIN LATERAL jsonb_array_elements(a.nlp->'entities') AS ent
        WHERE a.nlp IS NOT NULL
          AND a.pub_date IS NOT NULL
          AND a.pub_date >= NOW() - INTERVAL '24 hours'
          AND ent->>'label' = ANY(:labels)
        GROUP BY entity_text, entity_label
        HAVING COUNT(DISTINCT a.id) >= 2
        ORDER BY mentions DESC
        LIMIT :limit
    ";

    // Need to pass labels as text[] for Postgres
    $labelsParam = '{' . implode(',', array_map('pg_escape_string', $labels)) . '}';

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':labels', $labelsParam, PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $limit,      PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sn_intel_recent_entity_articles(PDO $db, string $entityText, int $limit = 3): array
{
    $sql = "
        SELECT
            a.id,
            COALESCE(ri.title, a.title) AS title,
            a.url,
            COALESCE(f.name, a.source_slug) AS feed_name,
            a.pub_date
        FROM articles a
        LEFT JOIN rss_items ri
            ON ri.link = a.url
        LEFT JOIN feeds f
            ON f.id = ri.feed_id
        WHERE a.nlp IS NOT NULL
          AND a.pub_date IS NOT NULL
          AND a.pub_date >= NOW() - INTERVAL '24 hours'
          AND EXISTS (
              SELECT 1
              FROM jsonb_array_elements(a.nlp->'entities') ent
              WHERE ent->>'text' = :entity
          )
        ORDER BY a.pub_date DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':entity', $entityText, PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $limit,      PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sn_format_pub_date(?string $raw): string {
    if (empty($raw)) return '';

    $ts = strtotime($raw);
    if ($ts === false) return '';

    if (function_exists('format_news_date')) {
        return format_news_date($ts, 'America/New_York');
    }

    return date('M j, Y • g:i A', $ts);
}


function _fragment_cache_dir(): string {
    $dir = dirname(__DIR__) . '/_cache_fragments';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}

function _fragment_cache_path(string $key): string {
    $safe = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
    return _fragment_cache_dir() . '/' . $safe . '.html';
}

function fragment_cache_swr(
    string $key,
    int $ttl,
    int $staleTtl,          // kept for signature; not required anymore but fine
    callable $renderFn,
    bool $bust = false,
    bool $silent = false,
    bool $revalidate = true
): void {
    $path = _fragment_cache_path($key);
    $lock = $path . '.lock';
    $now  = time();

    $lockMaxAge = 30; // seconds; prevents stuck locks

    $lockIsFresh = function() use ($lock, $now, $lockMaxAge): bool {
        if (!is_file($lock)) return false;
        $lockTs = (int)@file_get_contents($lock);
        if ($lockTs <= 0) $lockTs = filemtime($lock) ?: $now;
        return ($now - $lockTs) <= $lockMaxAge;
    };

    $breakLockIfStale = function() use ($lock, $lockIsFresh) {
        if (is_file($lock) && !$lockIsFresh()) {
            @unlink($lock);
        }
    };

    // If cache exists and we're not busting: always serve it immediately
    if (!$bust && is_file($path)) {
        $age = $now - filemtime($path);

        // Serve cached HTML immediately (fast path)
        if (!$silent) readfile($path);

        // If stale, revalidate (single-flight)
        if ($revalidate && $age > $ttl) {
            $breakLockIfStale();

            $lockHandle = @fopen($lock, 'x');
            if ($lockHandle) {
                fwrite($lockHandle, (string)$now);
                fclose($lockHandle);

                if (!$silent && function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }

                try {
                    ob_start();
                    $renderFn();
                    $html = ob_get_clean();
                    if ($html !== '') {
                        file_put_contents($path, $html, LOCK_EX);
                    }
                } catch (Throwable $e) {
                    if (ob_get_level()) ob_end_clean();
                } finally {
                    @unlink($lock);
                }
            }
        }
        return;
    }

    // No cache or bust: blocking render (cache warm path)
    ob_start();
    $renderFn();
    $html = ob_get_clean();
    file_put_contents($path, $html, LOCK_EX);
    if (!$silent) echo $html;
}

function fl_headline_emoji(string $title): string {
    $t = mb_strtolower($title);

    // urgency / breaking
    if (preg_match('/\b(breaking|urgent|alert|live|explosion|shooting|killed|dead|war|attack)\b/u', $t)) return "🔥";

    // conflict / politics / law
    if (preg_match('/\b(trump|biden|election|vote|democrat|republican|congress|senate|court|judge|lawsuit|indict|probe)\b/u', $t)) return "⚖️";

    // money / markets
    if (preg_match('/\b(stocks|market|dow|nasdaq|s&p|fed|rates|inflation|jobs report|earnings|crypto|bitcoin|oil)\b/u', $t)) return "💰";

    // crime / danger (but not “breaking”)
    if (preg_match('/\b(crime|arrest|charged|suspect|police|gun|fraud|scam)\b/u', $t)) return "🚨";

    // health / science
    if (preg_match('/\b(covid|virus|flu|health|cdc|vaccine|cancer|study|research|ai|robot|space|nasa)\b/u', $t)) return "🧪";

    // weather / disasters
    if (preg_match('/\b(storm|hurricane|tornado|flood|earthquake|wildfire|snow)\b/u', $t)) return "🌪️";

    // entertainment / culture
    if (preg_match('/\b(movie|film|music|celebrity|hollywood|oscars|grammys|tv|show)\b/u', $t)) return "🎭";

    // global / geopolitics
    if (preg_match('/\b(ukraine|russia|china|israel|gaza|iran|north korea|eu|un)\b/u', $t)) return "🌍";

    // default mood
    return "📰";
}


/**
 * Build a link to analysis.php with consistent query params.
 *
 * @param string $term     The entity/place/topic text (no leading #).
 * @param string $window   Time window like "24h", "7d", "3w", etc.
 * @param string $context  Context bucket like "entities", "places", "topics".
 * @param array  $extra    Optional extra query params to merge in.
 * @param string $path     Path to analysis page (default: "analysis.php").
 *
 * @return string Relative URL suitable for href.
 */
function sn_analysis_url(string $term, string $window = '24h', string $context = 'entity', array $extra = [], string $path = 'analysis.php'): string
{
    $term = trim($term);
    if ($term === '') {
        // Safe fallback; caller can decide to not render if they want.
        return $path;
    }

    if ($context === 'topic') {
        $term = ucwords($term);
    }

    // Normalize inputs a bit (optional but helps avoid inconsistent URLs)
    $window  = trim($window) ?: '24h';
    $context = trim($context) ?: 'entity';

    $params = array_merge([
        'context' => $context,     // context bucket
        'value' => $term,        // analysis target
        'w' => $window,      // time window
    ], $extra);

    return $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

?>