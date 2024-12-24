<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides the report plugin interface for the core
 *
  * @package report_visializing
  * @author NGaku615 <admin@NGaku615.com>
  * @copyright 2024 NGaku615 <admin@NGaku.com>
  * @copyright based on work by 2024 Gaku Nakao <NGaku615@moodle.com>
  * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_visualizing_extend_navigation_course($navigation, $course, $context) {

    if (has_capability('report/visualizing:view', $context)) {
        $url = new moodle_url('/report/visualizing/index.php', ['course' => $course->id]);
        $navigation->add(get_string('pluginname', 'report_visualizing'), $url, navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/report', ''));
    }
}

function report_visualizing_get_yscale($courseid) {
  global $DB;
  //縦軸の目盛を取得
  $y_scale = [];

  //セクションごとに設置されているモジュールを取得
  $courseSections = $DB->get_records_sql("SELECT id, name, sequence FROM {course_sections} WHERE course = $courseid");

  //sequenceは'（カンマ）で、区切られているので、それらをすべて1つずつ配列に格納
  $elements = [];
  $flag = []; //sectionがある場所
  foreach ($courseSections as $section) {
  $cmids = explode(',', $section->sequence);
  if($cmids[0] == "") continue; //コンテンツが置かれていないセクションは飛ばす
  if (isset($section->name)){
        array_push($y_scale,$section->name);
        array_push($flag,count($y_scale)-1);
  }
  $y_scale = array_merge($y_scale,$cmids);
  $elements = array_merge($elements,$cmids);
  }

  //moduleidとinstanceを取得
  $Modules = [];
  $instances = [];
  foreach($elements as $element){
    $sql = "SELECT id,module,instance FROM {course_modules} WHERE course = $courseid AND id = $element";
    $courseModule = $DB->get_record_sql($sql);
    array_push($Modules,$courseModule->module);
    array_push($instances,$courseModule->instance);
  }

  //コースに設置されているモジュールの名前を取得
  for($i=0,$j=0;$i<count($y_scale);$i++){ //jはモジュールの名前だけが入っている配列のインデックス,iはsection名も入っている配列のインデックス
    if(in_array($i,$flag)){
        continue;
    }else{
        $sql = "SELECT name FROM {modules} WHERE id = " . $Modules[$j];
        $modulename =  $DB->get_record_sql($sql);
        $sql = "SELECT name FROM {" . $modulename->name . "} WHERE course = $courseid AND id = " . $instances[$j];
        $CourseModuleName = $DB->get_record_sql($sql);
        $y_scale[$i] = $CourseModuleName->name;
        $j++;
    }
  }
  return $y_scale;
}

function report_visualizing_get_log($courseid,$starttime,$endtime){
  global $DB;
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
  return $log_sort;
}