<?php
use Goutte\Client;

set_time_limit(0);
error_reporting(5);

require_once('vendor/autoload.php');

$count = 3450;

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
			if (strpos($node->text(), 'See all') === false) {
				$category[] = [
					'category' => $category_title,
					'item_title' => trim($node->text()),
					'url' => $basePath . $node->attr('href')
				];
			}
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
unset($row);

// every tool information
$client = new Client();
$tool = [];
$fp = fopen('data.csv', 'w');
fputcsv($fp, array('名称', '五星率', 'review人数', '详情链接', '免费信息', '工具简介', '工具图标链接', '所属分类', '分类父类'));
foreach ($category as $row) {
	var_dump('正在跑：' . $row['item_title']);

	for ($i=1; $i <= $row['pagenation']; $i++) { 
		$crawler = $client->request('GET', $row['url'] . '?page=' . $i);

		var_dump('当前第' . $i . '页，共有' . $crawler->filter('#SearchResultsListings > div.grid-item--app-card-listing')->count() . '条数据');

		$crawler->filter('#SearchResultsListings > div.grid-item--app-card-listing')->each(function($node) use (&$tool, $row){
			$star = $node->filter('span.ui-star-rating__rating')->count() ? $node->filter('span.ui-star-rating__rating')->text() : '';
			$star_rating = substr($star, 0, strpos($star, 'of') - 1);

			$review_str = $node->filter('span.ui-review-count-summary')->count() ? $node->filter('span.ui-review-count-summary')->text() : '';
			$review = substr($review_str, 1, strpos($review_str, 'reviews') - 1);

			$info = [
				'title' => $node->filter('h4')->text(),
				'star-rating' => $star_rating,
				'review' => $review,
				'url' => $node->filter('a')->attr('href'),
				'free_info' => $node->filter('div.ui-app-pricing--format-short')->text(),
				'desc' => $node->filter('p')->text(),
				'icon' => $node->filter('img')->attr('src'),
				'category_item' => $row['item_title'],
				'category' => $row['category']
			];
			var_dump($info);exit;
			fputcsv($fp, $info);
			// array_unique($tool);
			// file_put_contents('data.json', json_encode($info) . "\r\n", FILE_APPEND);
		});
	}
}

fclose($fp);
// file_put_contents('data.json', json_encode($tool));

var_dump('数据量：' . count($tool) . ', 共耗时：' . (time() - $time_begin) . 's');

