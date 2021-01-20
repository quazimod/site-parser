<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    private function getStoreItems(): array
    {
        $items = [];
        $storeUrl = config('app.STORE_URL');
        $baseStoreURL = parse_url($storeUrl)['host'];

        for ($i =0; $i < 3;$i++) {
            $body = Http::get($storeUrl, ['page' => $i+1])->body();
            $crawler = new Crawler($body);
            $nodes = $crawler->filter('.j-card-item');

            foreach ($nodes as $node) {
                $newItem = [];
                $item_crawler = new Crawler($node);
                $item_uri = $item_crawler->filter('a.j-open-full-product-card')->attr('href');
                $item_page_url = $baseStoreURL . $item_uri;
                $item_page_html = Http::get($item_page_url)->body();
                $item_page_crawler = new Crawler($item_page_html);
                $item_code = $item_page_crawler->filter('.j-article')->text();
                $item_title = $item_page_crawler->filter('.j-product-title')->text();
                $item_final_price = $item_page_crawler->filter('.final-price-block')->text();
                $item_old_price = $item_page_crawler->filter('.old-price')->text();
                $newItem[] = ['Код товара' => $item_code];
                $newItem[] = ['Название товара' => $item_title];
                $newItem[] = ['Цена со скидкой' => $item_final_price];
                $newItem[] = ['Цена без скидки' => $item_old_price];

                $item_params_node = $item_page_crawler->filter('.j-add-info-section .params .pp');
                foreach ($item_params_node as $param) {
                    $paramName = $param->firstChild->textContent;
                    $paramValue = $param->lastChild->textContent;
                    $newItem[] = [$paramName => $paramValue];
                }

                $items[] = $newItem;
            }
        }

        return $items;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(): \Illuminate\Contracts\Support\Renderable
    {
        $items = $this->getStoreItems();

        return view('welcome', [$items]);
    }
}
