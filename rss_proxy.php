<?php
error_reporting(E_ERROR | E_PARSE);
require_once("Feed.php");
require_once('___modules.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedUrl = $_POST['feed'] ?? '';
    if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['items' => []]);
        exit;
    }

    $articles = [];

    $rss = Feed::loadRss($feedUrl);

    foreach ($rss->item as $item) {
        $media = $item->children('media', true);
        $image = (string) $media->content->attributes()->url ?? '';
        $host = parse_url((string) $item->link, PHP_URL_HOST);
        $publisher = preg_replace('/^www\./', '', $host); // remove 'www.'

        $items[] = [
            'title' => (string) $item->title,
            'link' => (string) $item->link,
            'description' => strip_tags((string) $item->description),
            'image' => $image,
            'publisher' => $publisher,
            'pubDate' => isset($item->pubDate) ? date('Y-m-d\TH:i:s\Z', strtotime($item->pubDate)) : null,
            'pubDateForLink' => isset($item->pubDate) ? toEpoch(toIsoZ(strtotime($item->pubDate))) : null
        ];

    }

    echo json_encode(['items' => $items]);
}
