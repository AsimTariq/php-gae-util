<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 15/11/2017
 * Time: 11:57
 */

namespace GaeUtil;

use GDS\Store;

class DataStore {

    const DEFAULT_TOKEN_KIND = "GoogleAccessTokens";
    const DEFAULT_WORKFLOW_KIND = "GaeUtilWorkflows";

    static function getTokenKind() {
        return Conf::get("datastore_kind", SELF::DEFAULT_TOKEN_KIND);
    }

    static function getWorkflowKind() {
        return Conf::get("datastore_workflow_kind", SELF::DEFAULT_WORKFLOW_KIND);
    }

    static function getWorkflowJobKind() {
        return self::getWorkflowKind() . "Jobs";
    }

    static function deleteAll($kind_schema) {
        $store = new Store($kind_schema);
        $entities = $store->fetchAll();
        syslog(LOG_INFO, "Found " . count($entities) . " records from $kind_schema, deleting them ALL.");
        $store->delete($entities);
    }

    static function saveToken($user_email, $user_data) {
        $kind_schema = self::getTokenKind();
        self::upsert($kind_schema, $user_email, $user_data);
    }

    static function retriveTokenByUser($user_email) {
        $kind_schema = self::getTokenKind();
        $store = new Store($kind_schema);
        return $store->fetchByName($user_email);
    }

    /**
     * Function that retrives users and tokens based on URL. used for background processing in bulk.
     */
    static function retriveTokensByScope($scope) {
        $kind_schema = self::getTokenKind();
        $str_query = "SELECT * FROM $kind_schema WHERE scopes='$scope'";
        return self::fetchAll($kind_schema, $str_query);
    }

    static function fetchAll($kind_schema, $str_query) {
        $store = new Store($kind_schema);
        $result = $store->fetchAll($str_query);
        $output = [];
        foreach ($result as $row) {
            $output[] = $row->getData();
        }
        return $output;
    }

    static function saveWorkflow($key, $workflow_config) {
        $kind_schema = self::getWorkflowKind();
        self::upsert($kind_schema, $key, $workflow_config);
    }

    static function upsert($kind_schema, $key, $data) {
        $store = new Store($kind_schema);
        $entity = $store->createEntity($data);
        $entity->setKeyName($key);
        $store->upsert($entity);
        syslog(LOG_INFO, "Saving $key at kind $kind_schema.");
    }

    static function retriveWorkflow($workflow_key) {
        $kind_schema = self::getWorkflowKind();
        $store = new Store($kind_schema);
        return $store->fetchByName($workflow_key);
    }

    static function retrieveAllWorkflowJobs() {
        $kind_schema = self::getWorkflowJobKind();
        $store = new Store($kind_schema);
        return $store->fetchAll();
    }

    static function saveWorkflowJob($workflow_job_key, $workflow_job_data) {
        $kind_schema = self::getWorkflowJobKind();
        $cached = new Cached($kind_schema . "/" . $workflow_job_key);
        self::upsert($kind_schema, $workflow_job_key, $workflow_job_data);
        $cached->set($workflow_job_data, 5);
        // caching data just to force this shit to work on the deveserver.
    }

    static function retriveWorkflowJob($workflow_job_key) {
        $kind_schema = self::getWorkflowJobKind();
        $cached = new Cached($kind_schema . "/" . $workflow_job_key);
        if($cached->exists()){
            return $cached->get();
        }
        $store = new Store($kind_schema);
        $result = $store->fetchByName($workflow_job_key);
        if ($result) {
            return $result->getData();
        } else {
            return false;
        }

    }

    /**
     * @param $workflow_key
     * @return array
     */
    static function retriveMostCurrentWorkflowJob($workflow_key) {
        $kind_schema = self::getWorkflowJobKind();
        $store = new Store($kind_schema);
        $result = $store->fetchOne("SELECT * FROM $kind_schema WHERE workflow_key='$workflow_key' ORDER BY created DESC");
        return $result->getData();
    }

    /**
     * Used to check if job is running and getting last successful job run to retrive state.
     *
     * @param $status
     * @return array
     */
    static function retrieveLastWorkflowJobByAgeAndStatus($workflow_key, $status, $created_after) {
        $kind_schema = self::getWorkflowJobKind();
        $store = new Store($kind_schema);
        $obsolete_time = new \DateTime($created_after);
        $where = [
            "workflow_key" => $workflow_key,
            "status" => $status,
            "obsolete_time" => $obsolete_time
        ];
        syslog(LOG_INFO, "Retrieve last $status created should be larger than:" . $obsolete_time->format("c"));
        $str_query = "SELECT * FROM $kind_schema 
        WHERE workflow_key = @workflow_key AND status = @status AND created > @obsolete_time 
        ORDER BY created DESC";
        $result = $store->fetchOne($str_query, $where);
        if ($result) {
            return $result->getData();
        } else {
            return false;
        }
    }

}



