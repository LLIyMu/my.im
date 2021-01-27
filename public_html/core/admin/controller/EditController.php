<?php


namespace core\admin\controller;


use core\base\exceptions\RouteException;

class EditController extends BaseAdmin
{

    // свойство для определения пути к шаблону
    protected $action = 'edit';

    protected function inputData(){

        // вызываю родительский метод, который инициализирует и запускает все нужные методы
        if (!$this->userId) $this->execBase();

        $this->checkPost();

        $this->createTableData();

        $this->createData();

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();

        $this->createManyToMany();

        $this->template = ADMIN_TEMPLATE . 'add';

        return $this->expansion();

    }

    protected function createData(){

        // записываю в $id значение по условию - если параметры($this->parameters) таблицы ([$this->table]) это число
        // то записываю приведенное к нормальному числу значение, иначе
        // записываю очищаю строку
        $id = is_numeric($this->parameters[$this->table]) ?
            $this->clearNum($this->parameters[$this->table]) :
            $this->clearStr($this->parameters[$this->table]);

        if (!$id) throw new RouteException( 'Не корректный идентификатор - ' . $id . ' при редактировании таблицы - ' . $this->table);

        $this->data = $this->model->get($this->table, [
            'where' => [$this->columns['id_row'] => $id]
        ]);

        $this->data && $this->data = $this->data[0];

    }


}