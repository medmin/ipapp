<aside class="main-sidebar">

    <section class="sidebar">

        <!-- Sidebar user panel -->
        <? if(!Yii::$app->user->isGuest): ?>
        <div class="user-panel">
            <div class="pull-left image">
                <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" class="img-circle" alt="User Image"/>
            </div>
            <div class="pull-left info">
                <p><?= \yii\helpers\Html::encode(Yii::$app->user->identity->userUsername) ?></p>

                <a href="javascript:;"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>
        <!-- search form -->
        <form action="<?= \yii\helpers\Url::to('/patents/search') ?>" method="get" class="sidebar-form" style="display: <?= Yii::$app->user->identity->userRole == \app\models\Users::ROLE_CLIENT ? 'none' : 'block' ?>">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
                <span class="input-group-btn">
                <button type='submit' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
            </div>
        </form>
        <!-- /.search form -->
        <? endif; ?>


        <?php
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->userRole == \app\models\Users::ROLE_CLIENT) {
            echo dmstr\widgets\Menu::widget(
                [
                    'options' => ['class' => 'sidebar-menu'],
                    'items' => [
                        ['label' => '菜单列表', 'options' => ['class' => 'header']],
                        ['label' => '我的进度', 'icon' => 'info-circle', 'url' => \yii\helpers\Url::to(['/'])],
                        ['label' => '我的案件', 'icon' => 'files-o', 'url' => \yii\helpers\Url::to(['users/my-patents'])],
                        ['label' => '年费监管', 'icon' => 'desktop', 'url' => \yii\helpers\Url::to(['users/patents'])],
                        ['label' => '我要反馈', 'icon' => 'edit', 'url' => \yii\helpers\Url::to(['site/contact'])],
                    ],
                ]
            );
        } elseif (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_EMPLOYEE) {
            echo dmstr\widgets\Menu::widget(
                [
                    'options' => ['class' => 'sidebar-menu'],
                    'items' => [
                        ['label' => '菜单列表', 'options' => ['class' => 'header']],
                        ['label' => '我的客户', 'icon' => 'group', 'url' => \yii\helpers\Url::to(['users/index'])],
                    ],
                ]
            );
        } else {
            $not_todo_count = \app\models\Patentevents::find()->where(['<', 'eventFinishUnixTS', time() * 1000])->andWhere(['eventStatus' => 'INACTIVE'])->count();
            $all_count = \app\models\Patentevents::find()->count();
            $todo_count = $all_count - $not_todo_count;
            echo dmstr\widgets\Menu::widget(
                [
                    'options' => ['class' => 'sidebar-menu'],
                    'items' => [
                        ['label' => '菜单列表', 'options' => ['class' => 'header']],
                        ['label' => '客户管理', 'icon' => 'group', 'url' => \yii\helpers\Url::to(['users/index'])],
                        ['label' => '专利列表', 'icon' => 'file-text-o', 'url' => \yii\helpers\Url::to(['patents/index'])],
                        ['label' => '专利事件', 'icon' => 'list-ul', 'url' => \yii\helpers\Url::to(['patentevents/index'])],
                        ['label' => '待办事务' . ($todo_count > 0 ? ('(' . $todo_count . ')') : ''), 'icon' => 'pencil', 'url' => \yii\helpers\Url::to(['/patentevents/todo'])],
                        ['label' => '缴费管理', 'icon' => 'rmb', 'url' => \yii\helpers\Url::to(['orders/index'])],
                        ['label' => '专利文件列表', 'icon' => 'folder-open-o', 'url' => \yii\helpers\Url::to(['patentfiles/index'])],
//                        [
//                            'label' => '系统工具',
//                            'icon' => 'share',
//                            'url' => '#',
//                            'items' => [
//                                ['label' => 'Gii', 'icon' => 'file-code-o', 'url' => ['/gii']],
//                                ['label' => 'Debug', 'icon' => 'dashboard', 'url' => ['/debug']]
//                            ],
//                        ],
                    ],
                ]
            );
        }
        ?>

    </section>

</aside>
