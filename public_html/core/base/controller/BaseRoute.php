<?php


namespace core\base\controller;


class BaseRoute
{

    use Singleton, BaseMethods;

    public static function routeDirection(){
        // создаю обект трейта Singleton и вызываю метод трейта BaseMethods isAjax
        if (self::instance()->isAjax()){

            exit((new BaseAjax())->route());

        }
        // если это не ajax то вызываю синхронный роутинг
        RouteController::instance()->route();

    }

}