<?php


namespace core\base\model;


use core\base\controller\Singleton;

class Crypt
{

    use Singleton;
    // метод шифрования
    private $cryptMethod = 'AES-128-CBC';
    // алгоритм хеширования
    private $hasheAlgoritm = 'sha256';
    // длинна хэша
    private $hasheLenght = 32;

    // метод шифрования данных(куки, авторизация)
    public function encrypt($str){
        // в переменную $ivlen записываю длину инициализирующего вектора шифра
        $ivlen = openssl_cipher_iv_length($this->cryptMethod);
        // в переменную $iv генерирую псевдослучайную последовательность байт
        $iv = openssl_random_pseudo_bytes($ivlen);
        // шифрую полученные данные
        $cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);
        // в переменную $hmac генерирую хеш-код на основе ключа, используя метод HMAC
        $hmac = hash_hmac($this->hasheAlgoritm, $cipherText, CRYPT_KEY, true);
        // возвращаю кодированные данные в формате MIME base64
        return $this->cryptCombine($cipherText, $iv, $hmac);

    }
    // метод дешифровки
    public function decrypt($str){

        // в переменную $ivlen записываю длину инициализирующего вектора шифра
        $ivlen = openssl_cipher_iv_length($this->cryptMethod);
        // в $crypt_data сохраняю всю строку, передавая методу cryptUnCombine $str и $ivlen
        $crypt_data = $this->cryptUnCombine($str, $ivlen);
        // сохраняю расшифровываю строку
        $original_plaintext = openssl_decrypt($crypt_data['str'], $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $crypt_data['iv']);
        // сохраняю хешированную строку
        $calcmac = hash_hmac($this->hasheAlgoritm, $crypt_data['str'], CRYPT_KEY, true);
        // если поданные строки идеинтичны возвращаю расшифрованную строку
        if (hash_equals($crypt_data['hmac'], $calcmac)) return $original_plaintext;

        return false;
    }
    // кастомный алгоритм шифрования строки, принимает строку - $str, последовательность байт - $iv, хеш - $hmac
    protected function cryptCombine($str, $iv, $hmac){

        $new_str = '';
        // записываю длинну строки в $str_len
        $str_len = strlen($str);

        $counter = (int)ceil(strlen(CRYPT_KEY) / ($str_len + $this->hasheLenght));

        $progress = 1;
        // если длинна $counter больше или равна $str_len
        if ($counter >= $str_len) $counter = 1; // то в $counter присваиваю значение 1

        for ($i = 0; $i < $str_len; $i++){

            if ($counter < $str_len){

                if ($counter === $i){

                    $new_str .= substr($iv, $progress - 1, 1);
                    $progress++;
                    $counter += $progress;

                }

            }else{

                break;

            }

            $new_str .= substr($str, $i, 1);

        }

        $new_str .= substr($str, $i);
        $new_str .= substr($iv, $progress -1);

        $new_str_half = (int)ceil(strlen($new_str) / 2);

        $new_str = substr($new_str, 0, $new_str_half) . $hmac . substr($new_str, $new_str_half);

        return base64_encode($new_str);

    }

    protected function cryptUnCombine($str, $ivlen){

        $crypt_data = [];

        $str = base64_decode($str);

        $hash_position = (int)ceil(strlen($str) / 2 - $this->hasheLenght / 2);

        $crypt_data['hmac'] = substr($str, $hash_position, $this->hasheLenght);

        $str = str_replace($crypt_data['hmac'], '', $str);

        $counter = (int)ceil(strlen(CRYPT_KEY) / (strlen($str) - $ivlen + $this->hasheLenght));

        $progress = 2;

        $crypt_data['str'] = '';
        $crypt_data['iv'] = '';

        for ($i = 0; $i < strlen($str); $i++){

            if ($ivlen + strlen($crypt_data['str']) < strlen($str)){

                if ($i === $counter){

                    $crypt_data['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;

                }else{

                    $crypt_data['str'] .= substr($str, $i, 1);

                }

            }else{

                $crypt_data_len = strlen($crypt_data['str']);

                $crypt_data['str'] .= substr($str, $i, strlen($str) - $ivlen - $crypt_data_len);

                $crypt_data['iv'] .= substr($str, $i + (strlen($str) - $ivlen - $crypt_data_len));

                break;

            }

        }

        return $crypt_data;

    }

}