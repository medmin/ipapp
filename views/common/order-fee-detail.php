<?php
/**
 * @Author: xczizz
 * @Date:   2017-10-05 13:21:33
 * @Last Modified by:   xczizz
 * @Last Modified time: 2017-10-05 13:57:02
 */

/* @var $fees */
$table = '<table class="table table-bordered"><tr><th>专利名称</th><th>类型</th><th>金额</th></tr>';
foreach ($fees as $ajxxb_id => $fee) {
    $table .= '<tr>';
    $table .= '<td rowspan="'. count($fee) .'" width="30%">'. \app\models\Patents::findOne(['patentAjxxbID' => $ajxxb_id])->patentTitle .'</td>';
    foreach ($fee as $idx => $item) {
        if ($idx == 0) {
            $table .= '<td>'. $item['fee_type'] .'</td>';
            $table .= '<td>'. $item['amount'] .'</td>';
            $table .= '</tr>';
        } else {
            $table .= '<tr><td>'. $item['fee_type'] .'</td><td>'. $item['amount'] .'</td></tr>';
        }
    }

}
$table .= '</table>';

echo $table;
