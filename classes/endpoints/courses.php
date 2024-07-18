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
 * Endpoint for courses and course categories.
 *
 * File         courses_cats.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class courses extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET'];
    }

    public function execute_request() {

        $displaycategories = false;
        $displaycourses = false;

        if (isset($this->request->parameters->displaycategories)) {
            $displaycategories = true;
        }

        if (isset($this->request->parameters->displaycourses)) {
            $displaycourses = true;
        }

        $this->response->add_body_key('number_of_categories', $this->get_categories(true));
        $this->response->add_body_key('number_of_courses', $this->get_courses(true));

        if ($displaycategories) {
            $this->response->add_body_key('categories', $this->get_categories());
        }

        if ($displaycourses) {
            $this->response->add_body_key('courses', $this->get_courses());
        }
    }

    private function get_categories($count = false) {
        global $DB;

        if ($count) {
            return $DB->count_records('course_categories');
        }

        $data = [];

        $categories = $DB->get_records('course_categories');

        foreach ($categories as $category) {
            $row = new \stdClass();
            $row->id = (int)$category->id;
            $row->parent = (int)$category->parent;
            $row->name = $category->name;
            $row->sort = (int)$category->sortorder;
            $row->path = $category->path;
            $row->depth = (int)$category->depth;

            $data[] = $row;
        }

        return $data;
    }

    private function get_courses($count = false) {
        global $DB;

        $sql = "SELECT * FROM {course} WHERE id > 1";
        $courses = $DB->get_records_sql($sql);

        if ($count) {
            if ($courses) {
                return count($courses);
            } else {
                return 0;
            }
        }

        $data = [];

        foreach ($courses as $course) {
            $row = new \stdClass();
            $row->id = (int)$course->id;
            $row->category = (int)$course->category;
            $row->name = $course->fullname;

            $data[] = $row;
        }

        return $data;
    }
}
