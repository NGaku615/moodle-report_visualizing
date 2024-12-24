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
 * Create the JSON file with nodes and edges data
 *
 * @package     report_visualizing
 * @copyright   2024 Nakao Gaku <Admin@NGaku615.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/report/visualizing/lib.php');

require_login();

$courseid = optional_param('course', null, PARAM_INT);

$log_sort = report_visualizing_get_yscale($courseid);
$json = json_encode($log_sort);

header('Content-Type: application/json; charset=utf-8');
echo $json;