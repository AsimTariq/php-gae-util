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
use GDS\Gateway\RESTv1;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase {

    protected $workflowClassName = "TestClassForWorkflows";

    public function setUp() {
        putenv("DATASTORE_EMULATOR_HOST=localhost:8081");
        DataStore::setGateway(new RESTv1("php-gae-util"));
        $WorkflowKind = DataStore::getWorkflowKind();
        $WorkflowJobKind = Datastore::getWorkflowJobKind();
        DataStore::deleteAll($WorkflowKind);
        DataStore::deleteAll($WorkflowJobKind);
        //sleep(1);
    }

    /**
     * Test if I can create and retrive an workflow config by key.
     *
     * @throws Exception
     */
    public function testCreate_workflow() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        $workflow_key = Workflow::createWorkflowKeyFromConfig($workflow_config);
        $workflow_config_from_db = Workflow::getWorkflowConfig($workflow_key);
        $this->assertEquals("a2a81054ba4cabbd12b8fa7db22a3d1f", $workflow_key);
        $this->assertEquals($this->workflowClassName, $workflow_config_from_db[Workflow::CONF_HANDLER]);
        $this->assertEquals($initial_params, $workflow_config_from_db[Workflow::CONF_PARAMS]);
    }

    /**
     * @throws Exception
     */
    public function testStart_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        $workflow_job_key = __METHOD__;
        $state = Workflow::startJob($workflow_job_key, $workflow_config);
        Workflow::endJob($workflow_job_key, $initial_state);
        $this->assertEquals($initial_state, $state);
        /**
         * Trying to start another job... this should fail
         */
    }

    /**
     * @throws Exception
     */
    public function testFail_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        Workflow::startJob(__METHOD__, $workflow_config);
        Workflow::failJob(__METHOD__, "Testing failed!");
        //sleep(1);
        $job = DataStore::retrieveWorkflowJob(__METHOD__);
        $this->assertEquals(Workflow::STATUS_FAILED, $job["status"]);
    }

    /**
     * @throws Exception
     */
    public function testEnd_job() {
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        Workflow::startJob(__METHOD__, $workflow_config);
        Workflow::endJob(__METHOD__);
        //sleep(1);
        $job = DataStore::retrieveWorkflowJob(__METHOD__);
        $this->assertEquals(Workflow::STATUS_COMPLETED, $job["status"]);

    }

    /**
     * @throws Exception
     */
    public function testStartDuplicateJobShouldFail() {
        $this->expectException(Exception::class);
        $initial_state = ["2018-01-01"];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        $workflow_job_key = __METHOD__;
        Workflow::startJob($workflow_job_key, $workflow_config);
        //sleep(1); // sleep for some weird irritating reason
        $workflow_job_key = __METHOD__ . "-should-not-start";
        Workflow::startJob($workflow_job_key, $workflow_config);

    }

    /**
     * @throws Exception
     */
    public function testJobRunner() {
        $initial_state = ["2018-01-01"];
        $expected_state = [Util::dateAfter("2018-01-01")];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        $workflow_key = Workflow::createWorkflowKeyFromConfig($workflow_config);
        //sleep(1);
        $pre_job_state = Workflow::getWorkflowState($workflow_key);
        $after_run_state = Workflow::runFromConfig($workflow_config, $initial_state);
        $this->assertEquals($expected_state, $after_run_state, "State after a simple run should equal todays date.");
        $this->assertEquals($initial_state, $pre_job_state, "Prejob state should equal initial_state of the workflow.");

    }

    /**
     * @throws Exception
     */
    public function testRunFromKey() {
        $initial_state = ["2018-01-01"];
        $date_after = Util::dateAfter($initial_state[0]);
        $expected_state = [$date_after];
        $initial_params = ["parameter1", "parameter2"];
        $workflow_config = Workflow::createWorkflow([
            Workflow::CONF_HANDLER => $this->workflowClassName,
            Workflow::CONF_PARAMS => $initial_params,
            Workflow::CONF_INITIAL_STATE => $initial_state,
        ]);
        $workflow_key = Workflow::createWorkflowKeyFromConfig($workflow_config);
        $result_state = Workflow::runFromKey($workflow_key);
        //sleep(1);
        $persisted_state = Workflow::getWorkflowState($workflow_key);
        $this->assertEquals($expected_state, $result_state, "Run state should produce the next day.");
        $this->assertEquals($result_state, $persisted_state, "State should be persisted.");
    }

}
