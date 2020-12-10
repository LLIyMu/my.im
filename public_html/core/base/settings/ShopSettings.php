<?php


namespace core\base\settings;



class ShopSettings
{

    use BaseSettings;

    private $routes = [
        'plugins' => [ //маршруты плагинов

            'dir' => false,
            'routes' => [

            ]
        ]
    ];

    private $templateArr = [
        'text' => ['price', 'abort', 'name'],
        'textarea' => ['goods_content', 'name']
    ];

}