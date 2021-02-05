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
     * @param string $fullname shortname of the course.
     * @param string $token The imisid token to be added to the url
     * @throws dml_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public function i_visit_course_homepage_with_token(string $coursefullname, string $token) {
        global $DB;
        $course = $DB->get_record("course", array("fullname" => $coursefullname), 'id', MUST_EXIST);
        $params = [
            'id' => $course->id,
            'token' => $token
        ];
        if (!empty($token)) {
            $params['token'] = $token;
        }
        $url = new moodle_url('/course/view.php', $params);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Visit a local URL relative to the behat root passing an IMIS token.
     *
     * @When I visit :localurl with IMIS token :token
     *
     * @param string|moodle_url $localurl The URL relative to the behat_wwwroot to visit.
     * @param string $token The imisid token to be added to the url
     */
    public function i_visit_url_with_imis_token($localurl, $token): void {
        $params = [];
        if (!empty($token)) {
            $params['token'] = $token;
        }
        $url = new moodle_url($localurl, $params);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));

    }

    /**
     * Visit a local URL relative to the behat root with no IMIS token.
     *
     * @When I visit :localurl with no IMIS token
     *
     * @param string|moodle_url $localurl The URL relative to the behat_wwwroot to visit.
     * @param string $token The imisid token to be added to the url
     */
    public function i_visit_url_with_no_imis_token($localurl): void {
        $url = new moodle_url($localurl);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));

    }


    /**
     * Checks, that current page PATH is equal to specified
     * Example: Then I should be on "/"
     * Example: And I should be on "/bats"
     * Example: And I should be on "http://google.com"
     *
     * @Then /^(?:|I )should be on "(?P<page>[^"]+)"$/
     */
    public function assertPageAddress($page)
    {
        $this->assertSession()->addressEquals($this->locatePath($page));
    }

}
