<?php

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

global $CFG;
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_auth_imisbridge extends behat_base
{
    /**
     * Visit a course homepage with specified token.
     *
     * @When I visit course :shortname homepage with token :token
     * @param string $shortname shortname of the course.
     * @param string $token The imisid token to be added to the url
     * @throws Exception if the specified page cannot be determined.
     */
    public function i_visit_course_homepage_with_token(string $shortname, string $token = null) {
        global $DB;
        $id = $DB->get_field('course','id',['shortname' => $shortname], MUST_EXIST);
        $params = ["id" => $id];
        if (!empty($token)) {
            $params['token'] = $token;
        }
        $url = new moodle_url('/course/view.php', $params);
        $this->execute('behat_general::i_visit', [$url]);
    }
}
