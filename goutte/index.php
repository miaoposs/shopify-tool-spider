<?php
use Goutte\Client;

set_time_limit(0);
error_reporting(5);

require_once('vendor/autoload.php');

$time_begin = time();
$client = new Client();
$category = [];
$basePath = 'https://apps.shopify.com';

$crawler = $client->request('GET', $basePath);
$crawler->filter('#ASCategoryNav > ul > li')->each(function($node) use (&$category, $basePath){
	// category
	$nodeName = $node->children()->nodeName();
	if ($nodeName == 'span') {
		$category_title = trim($node->children()->text());

		// category item
		$node->filter('li > ul > li > a')->each(function($node) use (&$category, $category_title, $basePath) {
			$category[] = [
				'category' => $category_title,
				'item_title' => trim($node->text()),
				'url' => $basePath . $node->attr('href')
			];
		});
	} else {
		$children = $node->children();
		$category[] = [
			'category' => trim($children->text()),
			'item_title' => trim($children->text()),
			'url' => $basePath . $children->attr('href')
		];
	}
});


// each category pagenation
$client = new Client();
foreach ($category as &$row) {
	$crawler = $client->request('GET', $row['url']);
	$row['pagenation'] = $crawler->filter('div.search-pagination > a.search-pagination__link')->count() ? $crawler->filter('div.search-pagination > a.search-pagination__link')->last()->text() : 1;
}

var_dump($category);

// every tool information
$client = new Client();
foreach ($category as $row) {
	var_dump('正在跑：' . $row['item_title']);
	for ($i=1; $i <= $row['pagenation']; $i++) { 
		$crawler = $client->request('GET', $row['url'] . '?page=' . $i);
		$crawler->filter('#SearchResultsListings > div.grid-item--app-card-listing')->each(function($node){
			$star = $node->filter('span.ui-star-rating__rating')->count() ? $node->filter('span.ui-star-rating__rating')->text() : '';
			$star_rating = substr($star, 0, strpos($star, 'of') - 1);

			$review_str = $node->filter('span.ui-review-count-summary')->count() ? $node->filter('span.ui-review-count-summary')->text() : '';
			$review = substr($review_str, 1, strpos($review_str, 'reviews') - 1);

			$info = [
				'title' => $node->filter('h4')->text(),
				'icon' => $node->filter('img')->attr('src'),
				'star-rating' => $star_rating,
				'review' => $review,
				'free_info' => $node->filter('div.ui-app-pricing--format-short')->text(),
				'desc' => $node->filter('p')->text(),
				'url' => $node->filter('a')->attr('href')
			];
			file_put_contents('data.json', json_encode($info) . "\r\n", FILE_APPEND);
		});
	}
}

var_dump('共耗时：' . (time() - $time_begin) . 's');exit;

