<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 20/11/2017
 * Time: 23:03
 */

namespace GaeUtil;

class Workflow {

    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_FAILED = "FAILED";
    const STATUS_RUNNING = "RUNNING";

    const CONF_HANDLER = "handler";
    const CONF_PARAMS = "params";
    const CONF_INITIAL_STATE = "initial_state";

    /**
     * Function creates and saves the workflow_config.
     *
     * @param $workflow_config
     * @return mixed
     * @throws \Exception
     */
    static function createWorkflow($workflow_config) {
        self::validateConfig($workflow_config);
        self::validateState($workflow_config[self::CONF_HANDLER], $workflow_config[self::CONF_INITIAL_STATE]);

        if (!isset($workflow_config["name"])) {
            $workflow_config["name"] = $workflow_config[self::CONF_HANDLER] . '(' . implode(",", $workflow_config[self::CONF_PARAMS]) . ")";
        }
        if (!isset($workflow_config["active"])) {
            $workflow_config["active"] = true;
        }
        if (!isset($workflow_config["runrules"])) {
            $workflow_config["runrules"] = "once_pr_day";
        }
        /**
         * Setting some defaults
         */
        $workflow_config["application"] = Util::getApplicationId();
        $workflow_config["service"] = Util::getModuleId();
        $workflow_config["created"] = new \DateTime();
        $workflow_key = self::createWorkflowKeyFromConfig($workflow_config);
        DataStore::saveWorkflow($workflow_key, $workflow_config);
        return $workflow_config;
    }

    static function createWorkflowKeyFromConfig($config) {
        $workflow_key = [
            self::CONF_HANDLER => $config[self::CONF_HANDLER],
            self::CONF_PARAMS => $config[self::CONF_PARAMS],
        ];
        return md5(json_encode($workflow_key));
    }

    static function getWorkflowConfig($workflow_key) {
        $workflowConfig = DataStore::retrieveWorkflow($workflow_key);
        if ($workflowConfig) {
            return $workflowConfig->getData();
        }
        return false;
    }

    static function runFromKey($workflow_key) {
        $workflowConfig = self::getWorkflowConfig($workflow_key);
        $workflow_job_key = self::create_workflow_job_key();
        try {
            $workflowState = self::startJob($workflow_job_key, $workflowConfig);
            $endState = self::runFromConfig($workflowConfig, $workflowState);
            if ($endState) {
                return self::endJob($workflow_job_key, $endState);
            } else {
                return self::failJob($workflow_job_key, "Job failed, Unknown error. Returned empty state.");
            }
        } catch (\Exception $exception) {
            return self::failJob($workflow_job_key, $exception->getMessage());
        }
    }

    /**
     * @param $workflow_config
     * @param $state
     * @return mixed
     * @throws \Exception
     */
    static function runFromConfig($workflow_config, $workflow_state) {
        self::validateConfig($workflow_config);
        self::validateState($workflow_config[self::CONF_HANDLER], $workflow_state);
        $workflowClassName = $workflow_config[self::CONF_HANDLER];
        $workFlowParams = $workflow_config[self::CONF_PARAMS];
        $workflowClass = new $workflowClassName($workflow_config);
        call_user_func_array([$workflowClass, "set_state"], $workflow_state);
        return call_user_func_array([$workflowClass, "run"], $workFlowParams);
    }

    /**
     * @param $config
     * @return bool
     * @throws \Exception
     */
    static function validateConfig($config) {
        Util::keysExistsOrFail("Workflow config", $config, [
            self::CONF_HANDLER,
            self::CONF_PARAMS,
            self::CONF_INITIAL_STATE
        ]);
        $workflowClassName = $config[self::CONF_HANDLER];
        $workFlowParams = $config[self::CONF_PARAMS];
        if (!class_exists($workflowClassName)) {
            throw new \Exception("Allright! $workflowClassName does not exist! Creation failed.");
        }
        $method = new \ReflectionMethod($workflowClassName, "run");
        $required_number_of_params = $method->getNumberOfParameters();
        $config_number_of_params = count($workFlowParams);
        if ($required_number_of_params != $config_number_of_params) {
            throw new \Exception("$workflowClassName need exactly $required_number_of_params, $config_number_of_params params given.");
        }
        return true;
    }

    /**
     * @param $state
     * @return bool
     * @throws \Exception
     */
    static function validateState($workflowClassName, $workFlowState) {
        $method = new \ReflectionMethod($workflowClassName, "set_state");
        $required_number_of_params = $method->getNumberOfParameters();
        $state_number_of_params = count($workFlowState);
        if ($required_number_of_params != $state_number_of_params) {
            throw new \Exception("$workflowClassName state need exactly $required_number_of_params, $state_number_of_params params given.");
        }
        return true;
    }

    static function create_workflow_job_key() {
        return uniqid();
    }

    /**
     * Checks if its long since last error. This allows us to not spam apis with error for instance.
     *
     * @param $workflow_key
     * @return mixed
     */
    static function isWorkflowInErrorState($workflow_key, $ttl) {
        $status = self::STATUS_FAILED;
        $created_after = "-$ttl sec";
        $data = DataStore::retrieveMostCurrentWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
        return (bool)$data;
    }

    /**
     * Will check the last successful job and retrive state from it.
     *
     * @param $workflow_key
     * @return array
     */
    static function getWorkflowState($workflow_key) {
        $status = self::STATUS_COMPLETED;
        $created_after = "-20 years";
        $data = DataStore::retrieveMostCurrentWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
        if ($data) {
            return $data["end_state"];
        } else {
            $workflow = DataStore::retrieveWorkflow($workflow_key);
            if (!$workflow) {
                throw new \Exception("Error retrieving workflow for key $workflow_key can't determine state.");
            }
            $data = $workflow->getData();
            return $data[self::CONF_INITIAL_STATE];
        }
    }

    /**
     * Will check if there is still a job running
     *
     * @param $workflow_key
     * @return bool
     */
    static function isWorkflowRunning($workflow_key, $ttl = 86000) {
        $status = self::STATUS_RUNNING;
        $created_after = "-1 day";
        $data = DataStore::retrieveMostCurrentWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
        $result = (bool)$data;
        return $result;
    }

    /**
     * Returns the workflow state from previous job.
     * Performs the check on the previous job. Returns the state form the previous job.
     *
     * @param $workflow_config
     * @param $message
     */
    static function startJob($workflow_job_key, $workflow_config) {
        $workflow_key = self::createWorkflowKeyFromConfig($workflow_config);
        $workflow_name = $workflow_config["name"];

        /**
         * Creating job in database regardless.
         */
        unset($workflow_config["active"]);
        unset($workflow_config[self::CONF_INITIAL_STATE]);
        $start_state = self::getWorkflowState($workflow_key);
        $workflow_job_data = array_merge($workflow_config, [
            "workflow_key" => $workflow_key,
            "status" => self::STATUS_RUNNING,
            "start_state" => $start_state,
            "message" => "Job started...",
            "created" => new \DateTime()
        ]);
        DataStore::saveWorkflowJob($workflow_job_key, $workflow_job_data);

        /**
         * Checking if a flow is already running
         */
        if (self::isWorkflowRunning($workflow_key, Moment::ONEDAY)) {
            throw new \Exception("A job for $workflow_name is already running.");
        }
        /**
         *  Check how long its since last job run... we just don't want to spam errors.
         */
        $error_ttl = Moment::ONEDAY;
        if (self::isWorkflowInErrorState($workflow_key, $error_ttl)) {
            throw new \Exception("A job for $workflow_name have failed less than $error_ttl seconds ago, skipping.");
        }

        return $start_state;
    }

    /**
     * Returnes a report.
     *
     * @param $workflow_config
     * @param $message
     */
    static function failJob($workflow_job_key, $message) {
        $workflow_job_data = DataStore::retrieveWorkflowJob($workflow_job_key);
        $workflow_job_data["status"] = self::STATUS_FAILED;
        $workflow_job_data["end_state"] = $workflow_job_data["start_state"];
        $workflow_job_data["message"] = $message;
        $workflow_job_data["finished"] = new \DateTime();
        DataStore::saveWorkflowJob($workflow_job_key, $workflow_job_data);
        return $workflow_job_data["end_state"];
    }

    /**
     * Returnes a report.
     *
     * @param $workflow_job_key
     * @param array $end_state
     * @param string $message
     * @return array
     */
    static function endJob($workflow_job_key, $end_state = [], $message = "") {
        $workflow_job_data = DataStore::retrieveWorkflowJob($workflow_job_key);
        $workflow_job_data["status"] = self::STATUS_COMPLETED;
        $workflow_job_data["end_state"] = $end_state;
        $workflow_job_data["message"] = $message;
        $workflow_job_data["finished"] = new \DateTime();
        DataStore::saveWorkflowJob($workflow_job_key, $workflow_job_data);
        return $workflow_job_data["end_state"];
    }

    static function endpointHandler($get_request) {
        $get_param = "";
        if (isset($get_request[$get_param])) {
            // This is a script run request
            self::runFromKey($get_request[$get_param]);
        } else {
            // This is a scheduale request
        }
    }
}







