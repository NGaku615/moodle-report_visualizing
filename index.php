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
 * Plugin version and other meta-data are defined here.
 *
 * @package     report_visualizing
 * @copyright   2024 Nakao Gaku <Admin@NGaku615.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = optional_param('course', null, PARAM_INT);
$start_year = optional_param('start_year', null, PARAM_INT);
$end_year = optional_param('end_year', null, PARAM_INT);
$start_month = optional_param('start_month', null, PARAM_INT);
$end_month = optional_param('end_month', null, PARAM_INT);
$start_day = optional_param('start_day', null, PARAM_INT);
$end_day = optional_param('end_day', null, PARAM_INT);
$start_hour = optional_param('start_hour', null, PARAM_INT);
$end_hour = optional_param('end_hour', null, PARAM_INT);
$start_minute = optional_param('start_minute', null, PARAM_INT);
$end_minute = optional_param('end_minute', null, PARAM_INT);

$course = null;

if (is_null($courseid)) {
    // Site level reports.
    admin_externalpage_setup('visualizing', '', null, '', ['pagelayout' => 'report']);
} else {
    // Course level report.
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    require_login($course, false);
    require_capability('report/visualizing:view', $context);

    $PAGE->set_url(new moodle_url('/report/visualizing/index.php', ['course' => $course->id]));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title($course->shortname . ' - ' . get_string('pluginname', 'report_visualizing'));
    $PAGE->set_heading($course->fullname . ' - ' . get_string('pluginname', 'report_visualizing'));

    $strftimedate = get_string("strftimedate");
    $strftimedaydate = get_string("strftimedaydate");

    // Get all the possible dates.
    // Note that we are keeping track of real (GMT) time and user time.
    // User time is only used in displays - all calcs and passing is GMT.
    $timenow = time(); // GMT.

    // What day is it now for the user, and when is midnight that day (in GMT).
    $timemidnight = usergetmidnight($timenow);

    // Put today up the top of the list.
    $dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate) );

    $c = get_course($courseid);
    if (!$c->startdate or ($c->startdate > $timenow)) {
        $c->startdate = $c->timecreated;
    }
    $numdates = 1;
    while ($timemidnight > $c->startdate and $numdates < 365) {
        $timemidnight = $timemidnight - 86400;
        $timenow = $timenow - 86400;
        $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
        $numdates++;
    }
    //$path = 'generate.php?course='. $courseid . '&date=' . $date;
    echo $OUTPUT->header();
    //echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $CFG->wwwroot . '/report/overviewstats/index.php', 'method' => 'get'));
    //echo html_writer::empty_tag('input',array('type' => 'hidden', 'name' => 'course', 'value' => $courseid));
    //echo html_writer::select($dates,"date");
    //echo html_writer::empty_tag('input', array('type' => 'submit',
    //'value' => "generate a graph", 'class' => 'btn btn-primary'));
    //echo html_writer::end_tag('form');

    // 年プルダウン
    $year_options = [];
    for ($y = 1900; $y <= 2050; $y++) {
        $year_options[$y] = $y;
    }
    $start_year_select = html_writer::select($year_options, 'start_year', $start_year, ['' => 'select']);
    $end_year_select = html_writer::select($year_options, 'end_year', $end_year, ['' => 'select']);

    // 月プルダウン
    $month_options = [];
    for ($m = 1; $m <= 12; $m++) {
        $month_options[$m] = $m;
    }
    $start_month_select = html_writer::select($month_options, 'start_month', $start_month, ['' => 'select']);
    $end_month_select = html_writer::select($month_options, 'end_month', $end_month, ['' => 'select']);

    // 日プルダウン
    $day_options = [];
    for ($d = 1; $d <= 31; $d++) {
        $day_options[$d] = $d;
    }
    $start_day_select = html_writer::select($day_options, 'start_day', $start_day, ['' => 'select']);
    $end_day_select = html_writer::select($day_options, 'end_day', $end_day, ['' => 'select']);

    // 時間プルダウン
    $hour_options = [];
    for ($h = 0; $h < 24; $h++) {
        $hour_options[$h] = str_pad($h, 2, '0', STR_PAD_LEFT);
    }
    $start_hour_select = html_writer::select($hour_options, 'start_hour', $start_hour, ['' => 'select']);
    $end_hour_select = html_writer::select($hour_options, 'end_hour', $end_hour, ['' => 'select']);

    // 分プルダウン（5分刻み）
    $minute_options = [];
    for ($min = 0; $min < 60; $min += 5) {
        $minute_options[$min] = str_pad($min, 2, '0', STR_PAD_LEFT);
    }
    $start_minute_select = html_writer::select($minute_options, 'start_minute', $start_minute, ['' => 'select']);
    $end_minute_select = html_writer::select($minute_options, 'end_minute', $end_minute, ['' => 'select']);

    // フォームの生成
    echo html_writer::start_tag('form', array('class' => 'dayselecform', 'action' => $CFG->wwwroot . '/report/visualizing/index.php', 'method' => 'get'));
    echo html_writer::empty_tag('input',array('type' => 'hidden', 'name' => 'course', 'value' => $courseid));
    //1行目
    $form_html .= html_writer::tag('label', 'StartTime') . $start_year_select;
    $form_html .= html_writer::tag('label', '') . $start_month_select;
    $form_html .= html_writer::tag('label', '') . $start_day_select;
    $form_html .= html_writer::tag('label', '') . $start_hour_select;
    $form_html .= html_writer::tag('label', '：') . $start_minute_select;
    //2行目
    $form_html .= html_writer::tag('label', 'EndTime') . $end_year_select;
    $form_html .= html_writer::tag('label', '') . $end_month_select;
    $form_html .= html_writer::tag('label', '') . $end_day_select;
    $form_html .= html_writer::tag('label', '') . $end_hour_select;
    $form_html .= html_writer::tag('label', '：') . $end_minute_select;
    $form_html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => '変換']);
    $form_html .= html_writer::end_tag('form');

    echo $form_html;

    //if(!is_null($date))
     //   echo html_writer::img($path, 'Alternative');
    echo '<div id="course" title =' . $courseid . '></div>';
    echo '<div id="start_year" title =' . $start_year . '></div>';
    echo '<div id="end_year" title =' . $end_year . '></div>';
    echo '<div id="start_month" title =' . $start_month . '></div>';
    echo '<div id="end_month" title =' . $end_month . '></div>';
    echo '<div id="start_day" title =' . $start_day . '></div>';
    echo '<div id="end_day" title =' . $end_day . '></div>';
    echo '<div id="start_hour" title =' . $start_hour . '></div>';
    echo '<div id="end_hour" title =' . $end_hour . '></div>';
    echo '<div id="start_minute" title =' . $start_minute . '></div>';
    echo '<div id="end_minute" title =' . $end_minute . '></div>';
    echo '<script src="https://d3js.org/d3.v7.min.js"></script>';
    echo '<script src="script.js"></script>';
    echo '<div id="chart1"></div>';
    echo $OUTPUT->footer();
}