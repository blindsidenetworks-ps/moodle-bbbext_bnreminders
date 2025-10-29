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

namespace bbbext_bnreminders\form;
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * User unsubscription form.
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class unsubscribe extends \moodleform {
    /**
     * Form definition
     */
    protected function definition() {

        $mform = $this->_form;
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $unsubscribearray = [];
        $unsubscribearray[] = $mform->createElement('submit', 'unsubscribe', get_string('unsubscribe', 'bbbext_bnreminders'));
        $unsubscribearray[] = $mform->createElement('cancel');
        $mform->addGroup(
            $unsubscribearray,
            'unsubscribearray',
            get_string('unsubscribe:label', 'bbbext_bnreminders'),
            [' '],
            false
        );
        $mform->setType('unsubscribe', PARAM_BOOL);
    }
}
