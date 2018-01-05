<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = '用户详情';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = $model->userID;
$this->registerCss('
.delete-i {
    position: absolute;
    top: -6px;
    right: -15px;
    font-size: 10px;
    font-weight: 400;
    cursor: pointer;
    display: none;
}
#w1 tr th {
    width: 20%;
}
');
$this->registerJs('
$(".user-link").mouseover(function() {
  $(this).children(".delete-i").show();
});

$(".user-link").mouseout(function(){
  $(this).children(".delete-i").hide();
});

$("#myModal").on("hidden.bs.modal", function (e) {
  $("#myModal").find("#username").val("");
  $(".search-result").text("");
});

$("body").on("click", "#add", function() {
  var t = $(this).data("type")
  if ( t == "sub") {
    $("#myModal").find(".modal-title").text("添加下级").data("type", t);
  } else {
    $("#myModal").find(".modal-title").text("添加上级").data("type", t);
  }
  $("#myModal").modal("show");
});

$("#username").keydown(function(e) {
  if (e.keyCode == 13) {
    userSearch();
  }
});

$(".search-submit").click(function() {
  userSearch();
})

function userSearch() {
  var username = $("input#username").val();
  if ($.trim(username) == "") return;
  $.get("/users/search", {username: username}, function(data) {
    if (data.error === false) {
      var html = "<table class=\"table table-striped\" id=\"search-user\"><tr><th>用户ID</th><th>用户邮箱</th><th>姓名</th><th>用户名</th><th>操作</th></tr><tr><td id=\"search-user-id\">"+ data.id +"</td><td id=\"search-user-email\">"+ data.email +"</td><td id=\"search-user-fullname\">"+ data.fullname +"</td><td id=\"search-user-username\">"+ data.username +"</td><td data-id="+ data.id +" id=\"search-add\"><span class=\"badge bg-green\" style=\"cursor: pointer;\">添加</span></td></tr></table>";
      $(".search-result").html(html);
    } else {
      $(".search-result").text("未找到该用户");
    }
  }, "json");
}

$("body").on("click", "#search-add", function() {
  var t = $(".modal-title").data("type");
  var user_id = '. $model->userID .';
  var id = $(this).data("id");
  $.post("/users/assignment", {type: t, id: id, user_id: user_id}, function(d) {
    if (d.error == true) {
      alert(d.message);
      return;
    }
    window.location.reload();
  }, "json");
});

$(".delete-i").click(function() {
  var t = $(this).data("type");
  var user_id = '. $model->userID .';
  var id = $(this).data("id");
  var s = $(this).parent(".user-link");
  $.post("/users/delete-assignment", {type: t, id: id, user_id: user_id}, function(d) {
    if (d.error) {
      alert(d.message);
      return;
    }
    $(s).remove();
  }, "json");
});
');
?>
<div class="users-view">
    <div class="box box-info">
        <div class="box-header with-border">
            <p>
                <?php
                if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_EMPLOYEE){
                    echo Html::a('他的专利', \yii\helpers\Url::to(['patents/index', 'PatentsSearch[patentUserID]' => $model->userID]), ['class' => 'btn btn-primary']);
                    echo Html::a('所有进度', \yii\helpers\Url::to(['users/events-schedule', 'user_id' => $model->userID]), ['class' => 'btn btn-info', 'style' => 'margin-left: 5px']);
                } else {
                    echo Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->userID], ['class' => 'btn btn-primary']);
                    echo Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->userID], [
                        'class' => 'btn btn-danger',
                        'style' => 'margin-left: 5px',
                        'data' => [
                            'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]);
                }
                ?>
            </p>
        </div>
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-striped table-bordered detail-view', 'id' => 'w1'],
                'attributes' => [
                    'userID',
                    'userUsername',
//                    'userPassword',
                    'userOrganization',
                    'userFullname',
                    'userFirstname',
                    'userGivenname',
                    'userNationality',
                    'userCitizenID',
                    'userEmail:email',
                    'userCellphone',
                    'userLandline',
                    'userAddress',
                    'userLiaison',
                    'userLiaisonID',
                    'userRole',
                    'userNote:ntext',
//                    'authKey',
//                    'UnixTimestamp:datetime',
                    [
                        'label' => '上级',
                        'value' => function ($model) {
                            $html = '';
                            foreach ($model->superior as $user) {
                                $html .= '<span class="user-link" style="margin-right: 20px;position: relative;"><span class="delete-i badge" title="移除" data-id="'.$user->userID.'" data-type="superior">&times;</span>';
                                $html .= Html::a($user->userFullname, \yii\helpers\Url::to(['users/view', 'id' => $user->userID]), ['title' => $user->userUsername]);
                                $html .= '</span>';
                            }
                            $html .= '<a id="add" data-type="superior" style="font-size: .85em;padding:0 1px 0 1px;" class="btn btn-info"><i class="fa fa-user-plus"></i> 添加</a>';
                            return $html;
                        },
                        'format' => 'raw'
                    ],
                    [
                        'label' => '下级',
                        'value' => function ($model) {
                            $html = '';
                            foreach ($model->subordinate as $user) {
                                $html .= '<span class="user-link" style="margin-right: 20px;position: relative;"><span class="delete-i badge" title="移除" data-id="'.$user->userID.'" data-type="sub">&times;</span>';
                                $html .= Html::a($user->userFullname, \yii\helpers\Url::to(['users/view', 'id' => $user->userID]), ['title' => $user->userUsername]);
                                $html .= '</span>';
                            }
                            $html .= '<a id="add" data-type="sub" style="font-size: .85em;padding:0 1px 0 1px;" class="btn btn-info"><i class="fa fa-user-plus"></i> 添加</a>';
                            return $html;
                        },
                        'format' => 'raw'
                    ]
                ],
            ]) ?>
        </div>
    </div>
</div>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel" data-type=""></h4>
            </div>
            <div class="modal-body">
                <div class="row" style="border-bottom: 1px solid #eee;padding-bottom: 20px;">
                    <div class="col-xs-8">
                        <input id="username" type="text" class="form-control" placeholder="请输入用户ID或者邮箱帐号">
                    </div>
                    <div class="col-xs-3">
                        <button class="btn btn-block btn-flag btn-success search-submit" type="button">查找</button>
                    </div>
                </div>
                <div class="search-result" style="margin-top: 15px">

                </div>
            </div>
        </div>
    </div>
</div>
