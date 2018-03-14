<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 09/03/2018
 * Time: 09:37
 */

namespace GaeUtil;

class CloudSQL {

    static function clone_prod_database($project, $instance, $database, $bucket, $local_user = null, $local_pass = null) {
        $client = GoogleAccess::get_google_client(__METHOD__);
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');
        $service = new \Google_Service_SQLAdmin($client);
        $uri = "gs://{$bucket}/{$database}.sql";
        // TODO: Assign values to desired properties of `requestBody`:
        $requestBody = new \Google_Service_SQLAdmin_InstancesExportRequest();
        $exportContext = new \Google_Service_SQLAdmin_ExportContext();
        $exportContext->setSqlExportOptions($sqlExportOptions);
        $exportContext->setDatabases([$database]);
        $exportContext->setUri($uri);
        $requestBody->setExportContext($exportContext);

        $response = $service->instances->export($project, $instance, $requestBody);
        print_r($response);
    }
}