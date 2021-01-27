<?php


namespace libraries;


class FileEdit
{

    protected $imgArr = [];
    protected $directory;
    // метод добавления файла
    public function addFile($directory = false){
        // если пришло $directory то в свойство $this->directory записываю путь
        if (!$directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            // иначе записываю то что пришло в $directory
            else $this->directory = $directory;
        // прохожу по $_FILES форычем
        foreach ($_FILES as $key => $file){
            // если пришел массив файлов, т.е. если это не один файл
            if (is_array($file['name'])){
                // создаю пустой массив, куда буду записывать файлы
                $file_arr = [];
                // через цикл for прохожу по ячейке массива $_FILES и его $file['name']
                foreach ($file['name'] as $i => $value){
                    // если $file['name'][$i] не пустой
                    if (!empty($file['name'][$i])){
                        // заполняю массив
                        $file_arr['name'] = $file['name'][$i];
                        $file_arr['type'] = $file['type'][$i];
                        $file_arr['tmp_name'] = $file['tmp_name'][$i];
                        $file_arr['error'] = $file['error'][$i];
                        $file_arr['size'] = $file['size'][$i];
                        // записываю в переменную результат работы метода createFile передавая ему полученные файлы
                        $res_name = $this->createFile($file_arr);
                        // если есть $res_name записываю в свойтво imgArr и его ячейку [$key][] то что есть в $res_name
                        if ($res_name) $this->imgArr[$key][] = $res_name;

                    }

                }
            }else{// иначе т.е. если файл одиночный
                // если есть $file['name']
                if ($file['name']){
                    // записываю в $res_name результат работы метода createFile передавая ему $file
                    $res_name = $this->createFile($file);
                    // если есть $res_name записываю в свойтво imgArr[$key] то что есть в $res_name
                    if ($res_name) $this->imgArr[$key] = $res_name;

                }

            }

        }

        return $this->getFiles();

    }

    protected function createFile($file){
        // массив имени файла
        $fileNameArr = explode('.', $file['name']);
        // сохраняю в $exp расширение файла
        $ext = $fileNameArr[count($fileNameArr) -1];

        unset($fileNameArr[count($fileNameArr) -1]);

        $fileName = implode('.', $fileNameArr);

        $fileName = (new TextModify())->translit($fileName);

        $fileName = $this->checkFile($fileName, $ext);

        $fileFullName = $this->directory . $fileName;

        if ($this->uploadFile($file['tmp_name'], $fileFullName))
            return $fileName;
        return false;
    }

    protected function uploadFile($tmpName, $dest){

        if (move_uploaded_file($tmpName, $dest)) return true;

        return false;

    }

    protected function checkFile($fileName, $ext, $fileLastName = ''){

        if (!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext))
            return $fileName . $fileLastName . '.' . $ext;

        return $this->checkFile($fileName, $ext, '_'. hash('crc32', time() . mt_rand(1, 1000)));

    }

    public function getFiles(){

        return $this->imgArr;

    }

}