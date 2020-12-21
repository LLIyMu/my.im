            </div><!--.vg-main.vg-right-->
        </div><!--.vg-carcass-->

            <div class="vg-modal vg-center">

                <?php
                    // если существует $_SESSION['res']['answer']
                    if (isset($_SESSION['res']['answer'])){
                        // вывожу сообщение
                        echo $_SESSION['res']['answer'];
                        // разрегистрирую сессию
                        unset($_SESSION['res']);
                    };
                ?>

            </div>

            <script>
                const PATH = '<?=PATH?>';
                const ADMIN_MODE = 1;
            </script>

            <?php $this->getScripts(); ?>

    </body>
</html>
