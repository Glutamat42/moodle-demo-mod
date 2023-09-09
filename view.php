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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_adleradaptivity.
 *
 * @package     mod_adleradaptivity
 * @copyright   2023 Markus Heck
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot . '/question/editlib.php');


// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$a = optional_param('a', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('adleradaptivity', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('adleradaptivity', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('adleradaptivity', array('id' => $a), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('adleradaptivity', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

//$event = \mod_adleradaptivity\event\course_module_viewed::create(array(
//    'objectid' => $moduleinstance->id,
//    'context' => $modulecontext
//));
//$event->add_record_snapshot('course', $course);
//$event->add_record_snapshot('adleradaptivity', $moduleinstance);
//$event->trigger();

$PAGE->set_url('/mod/adleradaptivity/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);



echo $OUTPUT->header();

$quba = question_engine::make_questions_usage_by_activity('mod_adleradaptivity', $modulecontext);
//$quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
$quba->set_preferred_behaviour("immediatefeedback");


//$questions = $DB->get_records('question');
$mc_questions = array();
foreach($questions as $key => $question) {
    $qtype = question_bank::get_qtype($question->qtype, false);
    if ($qtype->name() === 'missingtype') {
        debugging('Missing question type: ' . $question->qtype, E_WARNING);
        continue;
    }
    if ($qtype->name() !== 'multichoice') {
        debugging('Not a multichoice question: ' . $question->qtype, E_NOTICE);
        continue;
    }
    $qtype->get_question_options($question);
    $mc_questions[] = $question;
}

$questions = [];
foreach ($mc_questions as $questiondata) {
    $questions[] = question_bank::make_question($questiondata);
}

$quba->start_all_questions();



echo $OUTPUT->footer();
