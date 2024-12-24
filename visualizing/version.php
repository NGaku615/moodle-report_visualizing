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
  * Provides version and release information
  *
  * @package report_visializing
  * @author NGaku615 <admin@NGaku615.com>
  * @copyright 2024 NGaku615 <admin@NGaku.com>
  * @copyright based on work by 2024 Gaku Nakao <NGaku615@moodle.com>
  * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'report_visualizing';
$plugin->release = 'v1.0.0';
$plugin->version = 2024122400;
$plugin->requires = 2022112800; // Moodle 4.0.
$plugin->maturity = MATURITY_STABLE;