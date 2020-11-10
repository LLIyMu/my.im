<?php


namespace core\base\controller;


trait BaseMethods
{
    protected $styles;
    protected $scripts;

    protected function init($admin = false){

        if (!$admin){
            if (USER_CSS_JS['styles']){
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
            if (USER_CSS_JS['styles']){
                foreach (USER_CSS_JS['scripts'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
        }else{
            if (ADMIN_CSS_JS['styles']){
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
            if (ADMIN_CSS_JS['styles']){
                foreach (USER_CSS_JS['scripts'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
        }

    }

}