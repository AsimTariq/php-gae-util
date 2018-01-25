<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 23/01/2018
 * Time: 12:45
 */

use GaeUtil\DataStore;
use GaeUtil\Util;
use GaeUtil\Workflow;



class WorkflowTest extends PHPUnit_Framework_TestCase {

    protected $workflowClassName = "TestClassForWorkflows";

    public function setUp() {
        $WorkflowKind = DataStore::getWorkflowKind();
        $WorkflowJobKind = Datastore::getWorkflowJobKind();
        DataStore::deleteAll($WorkflowKind);
        DataStore::deleteAll($WorkflowJobKind);
        sleep(1);
    }

    /**
     * Test if I can create and retrive an workflow config by key.
     *
     * @throws Exception
     */
    public function testCreate_workflow() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        $workflow_key = Workflow::create_key_from_config($workflow_config);
        $workflow_config_from_db = Workflow::get_workflow_config($workflow_key);
        $this->assertEquals("a2a81054ba4cabbd12b8fa7db22a3d1f", $workflow_key);
        $this->assertEquals($this->workflowClassName, $workflow_config_from_db["handler"]);
        $this->assertEquals($initial_params, $workflow_config_from_db["params"]);
    }


    public function testStart_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        $workflow_job_key = __METHOD__;
        $state = Workflow::start_job($workflow_job_key, $workflow_config);
        Workflow::end_job($workflow_job_key, $initial_state);
        $this->assertEquals($initial_state, $state);
        /**
         * Trying to start another job... this should fail
         */
    }

    public function testFail_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        Workflow::start_job(__METHOD__, $workflow_config);
        Workflow::fail_job(__METHOD__, "Testing failed!");
        sleep(1);
        $job = DataStore::retriveWorkflowJob(__METHOD__);
        $this->assertEquals(Workflow::STATUS_FAILED, $job["status"]);
    }

    public function testEnd_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        Workflow::start_job(__METHOD__, $workflow_config);
        Workflow::end_job(__METHOD__);
        sleep(1);
        $job = DataStore::retriveWorkflowJob(__METHOD__);
        $this->assertEquals(Workflow::STATUS_COMPLETED, $job["status"]);

    }

    public function testStartDuplicateJobShouldFail() {
        $this->setExpectedException(Exception::class);
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        $workflow_job_key = __METHOD__;
        Workflow::start_job($workflow_job_key, $workflow_config);
        sleep(1); // sleep for some weird irritating reason
        $workflow_job_key = __METHOD__ . "-should-not-start";
        Workflow::start_job($workflow_job_key, $workflow_config);

    }

    public function testJobRunner() {
        $initial_state = ["2018-01-01"];
        $expected_state = [Util::dateAfter("2018-01-01")];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, $initial_state);
        $workflow_key = Workflow::create_key_from_config($workflow_config);
        sleep(1);
        $pre_job_state = Workflow::get_workflow_state($workflow_key);
        $after_run_state = Workflow::run_from_config($workflow_config, $initial_state);
        $this->assertEquals($expected_state, $after_run_state, "State after a simple run should equal todays date.");
        $this->assertEquals($initial_state, $pre_job_state, "Prejob state should equal initial_state of the workflow.");

    }

    public function testRunFromKey() {
        $initial_date = "2018-01-01";
        $date_after = Util::dateAfter($initial_date);
        $expected_state = [$date_after];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::create_workflow($this->workflowClassName, $initial_params, [$initial_date]);
        $workflow_key = Workflow::create_key_from_config($workflow_config);
        $result_state = Workflow::run_from_key($workflow_key);
        sleep(1);
        $persisted_state = Workflow::get_workflow_state($workflow_key);
        $this->assertEquals($expected_state, $result_state, "Run state should produce the next day.");
        $this->assertEquals($result_state, $persisted_state,"State should be persisted.");
    }

}
