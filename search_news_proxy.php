<?php
header('Content-Type: application/json');
error_reporting(E_ERROR | E_PARSE);
require_once("Feed.php");
require_once('___modules.php');


if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['error' => 'No query specified']);
    exit;
}

$query = urlencode($_GET['q']);
$rss_url = "https://news.google.com/rss/search?q=$query&ceid=US:en&hl=en-US&gl=US";

$articles = [];
$rss = Feed::loadRss($rss_url);

$items = [];
foreach ($rss->item as $item) {

    $title = (string)$item->title;
    $link = (string)$item->link;
    $pubDate = (string)$item->pubDate;
      
    // Extract publisher (typically after the " - " in title)
    $parts = explode(" - ", $title);
    $publisher = count($parts) > 1 ? array_pop($parts) : 'Unknown';
      
    $title = implode(" - ", $parts);

    if (doesntContainAny(strtolower(str_replace(" ", "", $publisher)), $filter_out)) {
        $items[] = [
            'title' => $title,
            'publisher' => $publisher,
            'link' => $link,
            'pubDate' => date(DATE_ISO8601, strtotime($pubDate)),
            'pubDateForLink' => isset($item->pubDate) ? toEpoch(toIsoZ(strtotime($item->pubDate))) : null
        ];
    }
}

echo json_encode(['items' => $items]);
