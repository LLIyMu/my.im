<?php


namespace libraries;


class TextModify
{
    // свойство соотношения кирилических символов к английским буквам
    protected $translitArr = [ 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => 'y', 'ы' => 'y',
        'ь' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', ' ' => '-',
    ];
    // буквы которые могут идти после мягкого знака 'ь'
    protected $lowelLetter = ['а', 'е', 'и', 'о', 'у', 'э'];
    // метод транслитерации крилицы в английский
    public function translit($str){
        // перевожу строку в нижний регистр
        $str = mb_strtolower($str);
        // объявляю пустой массив
        $temp_arr = [];
        // выполняю цикл пока счетчик не дойдет до последнего символа $i < mb_strlen($str)
        for ($i = 0; $i < mb_strlen($str); $i++){
            // собираю массив режу строку $str начиная с символа $i длина резки символов 1
            $temp_arr[] = mb_substr($str, $i, 1);
        }
        // задаю пустую строку
        $link = '';
        // если есть временный массив
        if ($temp_arr){
            // запускаю по массиву цикл форейч
            foreach ($temp_arr as $key => $char){
                // если в $this->translitArr есть символ $char, т.е. если пришел кирилический символ
                if (array_key_exists($char, $this->translitArr)){
                    // создаю оператор множественного выбора по $char
                    switch ($char){
                        // если твёрдый знак
                        case 'ъ':
                            // если это твёрдый знак 'ъ' и следующий за ним знак это 'е' то к $link добавляю
                            // английскую 'y'
                            if ($temp_arr[$key + 1] == 'е') $link .= 'y';

                            break;

                        case 'ы':
                            // если это буква 'ы' и следующий за ним знак это 'й' то к $link добавляю английскую 'i'
                            if ($temp_arr[$key + 1] == 'й') $link .= 'i';
                                // иначе к $link добавляю то что есть по дефолту $this->translitArr[$char]
                                else $link .= $this->translitArr[$char];

                            break;

                        case 'ь':
                            // если это мягкий знак 'ь' и он не последний в массиве И следующий символ который идёт
                            // за 'ь' есть в массиве $this->lowelLetter
                            if ($temp_arr[$key + 1] !== count($temp_arr) && in_array($temp_arr[$key + 1], $this->lowelLetter)){
                                // добавляю к $link то что описано в массиве $this->translitArr[$char]
                                $link .= $this->translitArr[$char];
                            }

                            break;

                        default:
                            // по дефолту добавляю к $link то что есть в массиве $this->translitArr[$char]
                            $link .= $this->translitArr[$char];
                            break;

                    }
                }else{
                    // добавляю символ $char к $link
                    $link .= $char;

                }

            }

        }
        // если в линк что то пришло
        if ($link){
            // вырезаю все символы не являющиеся буквами, цифрами, нижними подчеркиваниями и дефисами, заменяю их на
            // пустоту
            $link = preg_replace('/[^a-z0-9_-]/iu', '', $link);
            // если знаков дефиса '-' будет 2 и более заменяю его на один
            $link = preg_replace('/-{2,}/iu', '-', $link);
            // если знаков нижнего подчеркивания '_' будет 2 и более заменяю его на один
            $link = preg_replace('/_{2,}/iu', '_', $link);
            // если сначала строки идет любое количество знаков дефиса '-' или нижнего подчеркивания '_' или они есть
            // в конце то просто заменяю их на пустоту
            $link = preg_replace('/(^[-_]+)|([-_]+$)/iu', '', $link);

        }
        // возвращаю сформированную ЧПУ
        return $link;
    }

}