<form class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?= $this->adminPath . $this->action ?>"
      enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit" class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button" value="Сохранить">
                </div>
                <?php if (!$this->noDelete && $this->data):?>
                    <div class="vg-element vg-padding-in-px">
                        <a href=""
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                            <span>Удалить</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($this->data): ?>
        <input type="hidden" name="<?= $this->columns['id_row'] ?>" value="<?= $this->data[$this->columns['id_row']]?>">
    <?php endif; ?>

    <input type="hidden" name="table" value="<?= $this->table ?>">

    <?php

        foreach ($this->blocks as $class => $block){
            // если в $this->blocks пришел не ассоциативный массив, а нумерованный, записываю в $class строку
            // 'vg-rows', что бы вёртска стала половинчатой можно записать 'vg-content' и блоки растянутся на всю
            // ширину
            if (is_int($class)) $class = 'vg-rows';

            echo '<div class="vg-wrap vg-element ' . $class . ' ">';

            if ($class !== 'vg-content') echo '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';

            if ($block){

                foreach ($block as $row){

                    foreach ($this->templateArr as $template => $items){

                        if (in_array($row, $items)){

                            if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template .'.php'){
                                throw new \core\base\exceptions\RouteException('Не найде наблон' .
                                    $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template .'.php');
                            }

                            break;
                        }
                    }
                }
            }

            if ($class !== 'vg-content') echo '</div>';
            echo '</div>';
        }

    ?>


    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button"
                           value="Сохранить">
                </div>
                <div class="vg-element vg-padding-in-px">
                    <a href="/admin/shop/delete/table/shop_products/id_row/id/id/92"
                       class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                        <span>Удалить</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

				