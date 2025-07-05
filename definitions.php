<?php
error_reporting(E_ERROR | E_PARSE);

require_once('___modules.php');

$term = $_GET['term'];

$result = '';


// get 2 definitions
$def_api_endpoint = "https://api.dictionaryapi.dev/api/v2/entries/en_US/";

$defs = azeo_getData($def_api_endpoint.urlencode($term));
$defs_array = [];

if (!empty($defs)) {
	
	$def_count = 0;

	foreach ($defs[0]['meanings'][0]['definitions'] as $def) {
		if ($def_count > 1) {
			break;
		}
		if ($def_count == 0) {
			$result = '<strong>' . ucfirst($term) . '.</strong> ' . $def['definition'];
		}
		else {
			$result = $result . ' Or. ' . $def['definition'];
		}
		array_push($defs_array, $def['definition']);
		$def_count = $def_count + 1;
	}
}

// if no good definition, check wikipedia

else {

	$wiki_api_endpoint = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=search&srsearch=";

	$wiki = azeo_getData($wiki_api_endpoint.urlencode($term));

	if (!empty($wiki)) {
		$result = '<strong>' . $wiki['query']['search'][0]['title'] . '. </strong> ' . strip_tags($wiki['query']['search'][0]['snippet']);
		$result = $result . '...';
	}

}


// get image

$wiki_img_url = "https://en.m.wikipedia.org/w/api.php?action=query&titles=".urlencode($term)."&prop=pageimages&format=json&pithumbsize=500";

$asr=azeo_getData($wiki_img_url);
$ggh=$asr['query']['pages'];

$darr=$ggh[key($ggh)];

$img_tag = '<img src="'.$darr["thumbnail"]["source"].'" class="mb-2"/>';

if($darr['thumbnail']['source'] == '')
	$img_tag = '';


if (strlen($result) > 300) {
	$result = substr($result, 0, 300);
	$result = $result . '...';
}

if ($result == '<strong>. </strong> ...') {
	$result = 'No definition found.';
}

// add image
$result = $img_tag . $result;

echo $result;