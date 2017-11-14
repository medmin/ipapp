<?php
/**
 * @Author: xczizz
 * @Date:   2017-10-05 13:21:33
 * @Last Modified by:   xczizz
 * @Last Modified time: 2017-10-05 13:57:02
 */

/* @var $fees */
$table = '<table class="table table-bordered"><tr><th>类型</th><th>金额</th></tr>';
foreach ($fees as $fee) {
    $table .= '<tr><td>'. $fee['type'] .'</td><td>'. $fee['amount'] .'</td></tr>';
}
$table .= '</table>';

echo $table;
