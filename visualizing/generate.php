<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     report_visualizing
 * @copyright   2024 Nakao Gaku <Admin@NGaku615.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/report/visualizing/lib.php');
require_once($CFG->dirroot. '/report/visualizing/generate.php');
$courseid = optional_param('course', null, PARAM_INT);
$starttime = optional_param('date', null, PARAM_INT);
$endtime = $starttime + 86400;
$PAGE->set_url(new
moodle_url('/report/visualizing/generate.php', ['course' => $courseid,'date' => $starttime]));

$strftimedate = get_string("strftimedate");
$strftimedaydate = get_string("strftimedaydate");

// Get all the possible dates.
// Note that we are keeping track of real (GMT) time and user time.
// User time is only used in displays - all calcs and passing is GMT.
$timenow = time(); // GMT.

// What day is it now for the user, and when is midnight that day (in GMT).
$timemidnight = usergetmidnight($timenow);

// Put today up the top of the list.
$dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate));

// If course is empty, get it from frontpage.
$course = get_course($courseid);
if (!$course->startdate or ($course->startdate > $timenow)) {
    $course->startdate = $course->timecreated;
}
$numdates = 1;
while ($timemidnight > $course->startdate and $numdates < 365) {
    $timemidnight = $timemidnight - 86400;
    $timenow = $timenow - 86400;
    $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
    $numdates++;
}

  //縦軸の目盛を取得
$y_scale = report_visualizing_get_yscale($courseid);
$y_scale = array_reverse($y_scale);

// 画像と色の初期化
$width = 1200;
$height = 1000;
$image = imagecreatetruecolor($width, $height);
$background = imagecolorallocate($image, 255, 255, 255); // 白に変更
imagefilledrectangle($image, 0, 0, $width, $height, $background);
$pointColor = imagecolorallocate($image, 0, 0, 255);
$grid_color = imagecolorallocate($image, 50, 50, 50);
$text_color = imagecolorallocate($image,0,40,39);
$font_size = 10;
$font = $CFG->dirroot . "/report/visualizing/sazanami-gothic.ttf";
if($starttime){
    imagestring($image, 4, 0, 20 ,date('Y-m-d',$starttime), $text_color);
}

// Y軸の目盛りの位置を求める
$yAxes = [];
$labelWidths = [0];
for ($i = 0; $i < count($y_scale); $i++) {
    $name = $y_scale[$i];
    $yAxes[$name] = 50 + (($height - 100) - ($i * (($height - 100) / (count($y_scale) - 1))));
    $labelWidth = imagettfbbox($font_size, 0, $font, strip_tags($name))[2]; // ラベルの幅を取得
    array_push($labelWidths,$labelWidth);
}
$labelMargin = 10; // ラベルとグラフの最小マージン
$xstart = max($labelWidths) + $labelMargin;

// 目盛の刻みを入れる
for ($i = 0; $i < count($y_scale); $i++){
    $name = $y_scale[$i];
    imagettftext($image, $font_size, 0, max($labelWidths) - imagettfbbox($font_size, 0, $font, strip_tags($name))[2], $yAxes[$name] + 3, $text_color, $font, strip_tags($name));
    if($i % 2 == 0){
        imageline($image, $xstart, $yAxes[$name], $width - 50, $yAxes[$name], $grid_color);
    } else {
    imagedashedline($image,$xstart, $yAxes[$name], $width - 50, $yAxes[$name], $grid_color);
    }
}

// X軸およびY軸を描画
$pointColor = imagecolorallocate($image, 0, 0, 255);
imageline($image, $xstart, $height - 50, $width - 50, $height - 50, $pointColor); // X軸
imageline($image, $xstart, 50, $xstart, $height - 50, $pointColor); // Y軸
//0,6,12,18,24時に目盛を打つ
imagestring($image, 4, $xstart, $height - 40, "0", $text_color);
imagestring($image, 4, (($width - 50) - $xstart) / 4 + $xstart, $height - 40, "6", $text_color);
imagestring($image, 4, (($width - 50) - $xstart) / 2 + $xstart, $height - 40, "12", $text_color);
imagestring($image, 4, ((($width - 50) - $xstart) / 4) * 3 + $xstart, $height - 40, "18", $text_color);
imagestring($image, 4, $width - 50, $height - 40, "24", $text_color);

imagestring($image, 5, (($width - 50) - $xstart) / 2 + $xstart - 10, $height - 20, "Time", $text_color);

$log_sort = report_visualizing_get_log($courseid,$starttime);
/*
//指定した日のログを取得
$sql_section = "SELECT id,userid,other,timecreated FROM {logstore_standard_log} AS lsl WHERE lsl.courseid = $courseid AND lsl.contextlevel = 50 AND lsl.timecreated >= $starttime AND lsl.timecreated < $endtime";
  $sectionLogs = $DB->get_records_sql($sql_section);
  foreach($sectionLogs as $key => $sectionLog){
    if(strpos($sectionLog->other,'coursesectionnumber') === false){
      //'other'のなかに'coursesectionnumber'が含まれていない場合
      unset($sectionLogs[$key]);
    }else{
      $array = json_decode($sectionLog->other,true);
      $sectionLogs[$key]->other = $array["coursesectionnumber"];
    }
  }
  foreach($sectionLogs as $key => $sectionLog){
    $sql = "SELECT id,name FROM {course_sections} AS cs WHERE cs.course = $courseid AND cs.section = " . $sectionLogs[$key]->other;
    $sectionName = $DB->get_record_sql($sql);
    $sectionLogs[$key]->other = $sectionName->name;
  }
  //以下モジュールへのアクセスログを取得
  $sql_module = "SELECT id,userid,contextinstanceid,timecreated FROM {logstore_standard_log} AS lsl WHERE lsl.courseid = $courseid AND lsl.contextlevel = 70 AND lsl.timecreated >= $starttime AND lsl.timecreated < $endtime";
  $context_logs = $DB->get_records_sql($sql_module);
  $moduleNames = [];
  foreach($context_logs as $key => $context_log){
    $sql = "SELECT id,module,instance FROM {course_modules} AS cm WHERE cm.id = $context_log->contextinstanceid";
    $course_module = $DB->get_record_sql($sql);
    $sql = "SELECT id,name FROM {modules} AS m WHERE m.id = $course_module->module";
    $module = $DB->get_record_sql($sql);
    $sql = "SELECT id,name FROM {". $module->name . "} AS m WHERE m.id = $course_module->instance";
    $moduleName = $DB->get_record_sql($sql);
    $context_logs[$key]->contextinstanceid = $moduleName->name;
  }
  //2つのアクセスログを合わせる
  $log_day = array();
  foreach ($context_logs as $cl) {
    $log_day[] = array(
        'name' => $cl->contextinstanceid,
        'timecreated' => $cl->timecreated,
        'userid' => $cl->userid
    );
  }

  foreach ($sectionLogs as $sl) {
    $log_day[] = array(
        'name' => $sl->other,
        'timecreated' => $sl->timecreated,
        'userid' => $sl->userid
    );
  }
  //時系列で並び替える
  usort($log_day,function($a,$b){
    return $a['timecreated'] - $b['timecreated'];
  });
  //ユーザidごとに連想配列を作成
  $log_sort = array();
  foreach ($log_day as $item) {
    $userid = $item["userid"];
    if (!isset($log_sort[$userid])) {
        $log_sort[$userid] = array();
    }

    $log_sort[$userid][] = array(
        'name' => $item["name"],
        'timecreated' => $item['timecreated']
    );
  }
*/
// データ点と線を描画
foreach($log_sort as $user){
    $prevX = null;
    $prevY = null;
    foreach ($user as $item) {
        $color = imagecolorallocatealpha($image, 255, 0, 0,100);

        $x = $xstart + (($item['timecreated'] - $starttime) / 86400) * ($width - ($xstart + 50));
        $y = $yAxes[$item['name']];
        imagefilledellipse($image, $x, $y, 6, 6, $color);

        // 線を描画
        if ($prevX !== null && $prevY !== null) {
            imageline($image, $prevX, $prevY, $x, $y, $color);
        }

        $prevX = $x;
        $prevY = $y;
    }
}

// 画像を出力
header('Content-Type: image/png');
imagepng($image);

// メモリを解放
imagedestroy($image);