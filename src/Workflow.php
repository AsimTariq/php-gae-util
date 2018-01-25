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

    /**
     * Function creates and saves the workflow_config.
     *
     *
     * @param $workflowClassName
     * @param array $workFlowParams
     * @param null $name
     * @return array
     * @throws \Exception
     */
    static function create_workflow($workflowClassName, $workFlowParams = [], $initialState = [], $workflowName = null) {
        if (is_null($workflowName)) {
            $workflowName = $workflowClassName . '(' . implode(",", $workFlowParams) . ")";
        }
        $workflow_config = [
            "name" => $workflowName,
            "application" => Util::get_current_application(),
            "service" => Util::get_current_module(),
            "handler" => $workflowClassName,
            "params" => $workFlowParams,
            "active" => true,
            "initial_state" => $initialState,
            "created" => new \DateTime()
        ];

        $workflow_key = self::create_key_from_config($workflow_config);
        $workflow_config["path"] = self::get_workflow_runner_path($workflow_key);
        self::validate_config($workflow_config);
        self::validate_state($workflow_config["handler"], $initialState);
        DataStore::saveWorkflow($workflow_key, $workflow_config);
        return $workflow_config;
    }

    static function get_workflow_runner_path($workflow_key) {
        $path = Conf::get("workflow_runner_path");
        $path = str_replace("{workflow_key}", $workflow_key, $path);
        return Util::get_full_path($path);
    }

    static function create_key_from_config($config) {
        $workflow_key = [
            "handler" => $config["handler"],
            "params" => $config["params"],
        ];
        return md5(json_encode($workflow_key));
    }

    static function get_workflow_config($workflow_key) {
        $workflowConfig = DataStore::retriveWorkflow($workflow_key);
        if ($workflowConfig) {
            return $workflowConfig->getData();
        }
        return false;
    }

    static function run_from_key($workflow_key) {
        $workflowConfig = self::get_workflow_config($workflow_key);
        $workflow_job_key = self::create_workflow_job_key();
        try {
            $workflowState = self::start_job($workflow_job_key, $workflowConfig);
            $endState = self::run_from_config($workflowConfig, $workflowState);
            if ($endState) {
                return self::end_job($workflow_job_key, $endState);
            } else {
                return self::fail_job($workflow_job_key, "Job failed, Unknown error. Returned empty state.");
            }
        } catch (\Exception $exception) {
            return self::fail_job($workflow_job_key, $exception->getMessage());
        }
    }

    /**
     * @param $config
     * @param $state
     * @return mixed
     * @throws \Exception
     */
    static function run_from_config($config, $workFlowState) {
        self::validate_config($config);
        self::validate_state($config["handler"], $workFlowState);
        $workflowClassName = $config["handler"];
        $workFlowParams = $config["params"];
        $workflowClass = new $workflowClassName($config);
        call_user_func_array([$workflowClass, "set_state"], $workFlowState);
        return call_user_func_array([$workflowClass, "run"], $workFlowParams);
    }

    /**
     * @param $config
     * @return bool
     * @throws \Exception
     */
    static function validate_config($config) {
        Util::key_exist_or_fail("Workflow config", $config, "handler");
        Util::key_exist_or_fail("Workflow config", $config, "params");
        Util::key_exist_or_fail("Workflow config", $config, "initial_state");
        $workflowClassName = $config["handler"];
        $workFlowParams = $config["params"];
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
    static function validate_state($workflowClassName, $workFlowState) {
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
    static function is_workflow_in_error_state($workflow_key, $ttl) {
        $status = self::STATUS_FAILED;
        $created_after = "-$ttl sec";
        $data = DataStore::retrieveLastWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
        return (bool)$data;
    }

    /**
     * Will check the last successful job and retrive state from it.
     *
     * @param $workflow_key
     * @return array
     */
    static function get_workflow_state($workflow_key) {
        $status = self::STATUS_COMPLETED;
        $created_after = "-20 years";
        $data = DataStore::retrieveLastWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
        if ($data) {
            return $data["end_state"];
        } else {
            $workflow = DataStore::retriveWorkflow($workflow_key);
            if (!$workflow) {
                throw new \Exception("Error retrieving workflow for key $workflow_key can't determine state.");
            }
            $data = $workflow->getData();
            return $data["initial_state"];
        }
    }

    /**
     * Will check if there is still a job running
     *
     * @param $workflow_key
     * @return bool
     */
    static function is_workflow_running($workflow_key, $ttl = 86000) {
        $status = self::STATUS_RUNNING;
        $created_after = "-1 day";
        $data = DataStore::retrieveLastWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after);
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
    static function start_job($workflow_job_key, $workflow_config) {
        $workflow_key = self::create_key_from_config($workflow_config);
        $workflow_name = $workflow_config["name"];


        /**
         * Creating job in database regardless.
         */
        unset($workflow_config["active"]);
        unset($workflow_config["initial_state"]);
        $start_state = self::get_workflow_state($workflow_key);
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
        if (self::is_workflow_running($workflow_key, Moment::ONEDAY)) {
            throw new \Exception("A job for $workflow_name is already running.");
        }
        /**
         *  Check how long its since last job run... we just don't want to spam errors.
         */
        $error_ttl = Moment::ONEDAY;
        if (self::is_workflow_in_error_state($workflow_key, $error_ttl)) {
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
    static function fail_job($workflow_job_key, $message) {
        $workflow_job_data = DataStore::retriveWorkflowJob($workflow_job_key);
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
     * @param array $newState
     * @param string $message
     */
    static function end_job($workflow_job_key, $end_state = [], $message = "") {
        $workflow_job_data = DataStore::retriveWorkflowJob($workflow_job_key);
        $workflow_job_data["status"] = self::STATUS_COMPLETED;
        $workflow_job_data["end_state"] = $end_state;
        $workflow_job_data["message"] = $message;
        $workflow_job_data["finished"] = new \DateTime();
        DataStore::saveWorkflowJob($workflow_job_key, $workflow_job_data);
        return $workflow_job_data["end_state"];
    }
}







