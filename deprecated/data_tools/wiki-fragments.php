<?php
error_reporting(E_ERROR | E_PARSE);
require_once('___modules.php');


$data = trim($_POST['data'] ?? $_GET['data'] ?? '');

$entities = explode("---", $data);

for ($i=0; $i<count($entities); $i++) {

    if ($wiki_count >= 35) {
      break;
    }

    $wik = azeo_wiki_results_2($entities[$i]);
    //print_r($entities[$i]);
    //print_r($wik);
    if (array_key_exists('title', $wik)) {
        $wiki_articles += [$wik['title'] => [$wik['url'], 2]];
        $wiki_count += 1; 
    }
}

?>