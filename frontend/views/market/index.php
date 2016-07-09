<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\classes\ItemHelper;
use common\classes\RoHelper;
use kartik\typeahead\Typeahead;
use yii\helpers\ArrayHelper;
use yii\web\View;
use common\models\Item;
use common\models\Shop;
use kartik\select2\Select2;
use yii\web\JsExpression;
use kartik\dropdown\DropdownX;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use kartik\icons\Icon;

Icon::map($this);  

/* @var $this yii\web\View */
/* @var $searchModel common\models\ShopItemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'RO108 :: Easy to Buy & sell Ragnarok Online\'s Items Server Thor, Loki, Eden');
$elements = Item::getElements();
$very = Item::getVeries();
$label = [
    '',
    ['label' => 'danger', 'icon' => '994'],
    ['label' => 'info', 'icon' => '995'],
    ['label' => 'success', 'icon' => '996'],
    ['label' => 'default', 'icon' => '997'],
];

$this->registerJs("
    function popupwindow(url, title, w, h) {
        wLeft = window.screenLeft ? window.screenLeft : window.screenX;
        wTop = window.screenTop ? window.screenTop : window.screenY;

        var left = wLeft + (window.innerWidth / 2) - (w / 2);
        var top = wTop + (window.innerHeight / 2) - (h / 2);
        return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
    }
", View::POS_HEAD);

?>

<div class="shop-item-index">
<?php Pjax::begin(['timeout' => 15000 ]); ?>
<h3><?= $server ?> <small><?= Html::a('<span class="glyphicon glyphicon-refresh"></span> '. Yii::t('app', 'Clear & Refresh'), ['']) ?></small></h3>
<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'item.item_name',
                'label' => Yii::t('app', 'Selling Items'),
                'value' => function($model){
                    $item = Html::img(Yii::$app->params['item_small_image_url']. ItemHelper::getImgFileName($model->item)) .' '.
                        Html::a($model->item['nameSlot'], '#', ['onclick' => "detail=popupwindow('".Url::to(['market/detail', 'id' => $model->id])."','detail','900','900'); return false;"]);
                    return $item;
                },
                'format' => 'raw',
                'filter' => Typeahead::widget([
                    'name' => 'ShopItemSearch[item.item_name]',
                    'value' => $searchModel['item.item_name'],
                    'dataset' => [
                        [
                            'local' => ArrayHelper::getColumn($items, 'item_name'),
                            'limit' => 10,
                        ],
                    ],
                    'pluginOptions' => ['highlight' => true],
                    'pluginEvents' => [
                        "typeahead:change" => "function() { $(this).change() }",
                        "typeahead:select" => "function() { $(this).change() }",
                    ],

                ]),
                'headerOptions' => [
                    'class' => 'col-md-4'
                ],
            ],
            [
                'attribute' => 'enhancement',
                'value' => function($model){
                    return $model->enhancement ? '+'.$model->enhancement : '';
                },
               'headerOptions' => [
                    'class' => 'col-md-1'
                ],
                'filter' => Html::dropDownList(
                    'ShopItemSearch[enhancement]',
                    $searchModel['enhancement'],
                    ['' => ''] + Item::getEnhancements(),
                    ['class' => 'form-control']
                ),
            ],
            [
                'attribute' => 'option',
                'value' => function($model) use ($elements, $very, $items, $label){
                    $option = '';

                    foreach(range(1, 4) as $slot){
                        $option .= $model->{'card_'.$slot} ? '['. Html::img(Yii::$app->params['item_small_image_url'] . 'card.gif') . $model->{'itemCard'.$slot}['item_name'] . ']<br>' : '';
                    }

                    $option .= $model->very ? ' '. $very[$model->very] : '';
                    $option .= $model->element ? 
                        Html::img(Yii::$app->params['item_small_image_url']. $label[$model->element]['icon'] . '.gif').
                        ' <span class="label label-'. $label[$model->element]['label'] .'">'. $elements[$model->element].'</span>' : '';
                    return $option;
                },
                'headerOptions' => [
                    'class' => 'col-md-2'
                ],
                'format' => 'raw',
                'filter' => Select2::widget([
                    'name' => 'ShopItemSearch[option]',
                    'value' => $searchModel['option'],
                    'data' => ArrayHelper::map($option_item, 'source_id', 'nameSlot'),
                    'options' => ['placeholder' => Yii::t('app', 'Select a card or an element ...')],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'templateResult' => new JsExpression('function format(item) {
                            return \'<img src="'. Yii::$app->params['item_small_image_url'] .'\' + (item.text.toLowerCase().indexOf(\'card\') > -1 ? \'card\' : item.id) + \'.gif"/> \' + item.text;
                        }'),
                        'escapeMarkup' => new JsExpression('function(m) { return m; }'),
                    ],
                ]),
            ],
            [
                'attribute' => 'price',
                'label' => Yii::t('app', 'Price (Zeny)'),
                'value' => function($model){
                    return number_format($model->price);
                },
               'headerOptions' => [
                    'class' => 'col-md-2'
                ],
            ],
            [
                'attribute' => 'shop.character',
                'label' => Yii::t('app', 'Owner'),
                'value' => function($model){
                    return '<div class="ellipsis" title="'. $model->shop->character .'">'. $model->shop->character. '</div>';
                },
                'format' => 'raw',
                'headerOptions' => [
                    'class' => 'col-md-1'
                ],
            ],
            [
                'attribute' => 'shop.shop_name',
                'label' => Yii::t('app', 'Shop Name'),
                'value' => function($model){
                    return '<div class="ellipsis" title="'. $model->shop->shop_name .'">'. $model->shop->shop_name. '</div>';
                },
                'format' => 'raw',
                'headerOptions' => [
                    'class' => 'col-md-1',
                ],
            ],
            [
                'attribute' => 'updated_at',
                'label' => Yii::t('app', 'Latest'),
                'format' => ['date', 'php:d-H:i'],
                'filter' => false,
                'headerOptions' => [
                    'class' => 'col-md-1'
                ],
            ],
            [
                'value' => function($model){
                    if(Yii::$app->user->isGuest) return '';

                    $menu = Html::beginTag('div', ['class'=>'dropdown']);
                    $menu .= Html::a('<span class="glyphicon glyphicon-option-horizontal"></span>', [''], ['data-toggle'=>'dropdown']);
                    $menu .= DropdownX::widget([
                        'items' => [
                            ['label' => Icon::show('thumbs-down'). Yii::t('app', 'Dislike'), 'url' => ['feedback', 'id' => $model->id, 'feedback_id' => 1]],
                            ['label' => Icon::show('thumbs-up'). Yii::t('app', 'Like'), 'url' => ['feedback', 'id' => $model->id, 'feedback_id' => 2]],
                            // '<li class="divider"></li>',
                        ],
                        'encodeLabels' => false,
                    ]); 
                    $menu .= Html::endTag('div');
                    return $menu;
                },
                'format' => 'raw',
                'headerOptions' => [
                    'class' => 'col-md-1'
                ],
            ],
        ],
    ]); ?>
<?php Pjax::end(); ?></div>


