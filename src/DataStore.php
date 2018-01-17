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

    static function saveToken($user_email, $user_data) {
        $kind_schema = Conf::get("datastore_kind");
        $store = new Store($kind_schema);
        $entity = $store->createEntity($user_data);
        $entity->setKeyName($user_email);
        $store->upsert($entity);
    }

    static function retriveTokenByUser($user_email) {
        $kind_schema = Conf::get("datastore_kind");
        $store = new Store($kind_schema);
        return $store->fetchByName($user_email);
    }

    /**
     * Function that retrives users and tokens based on URL. used for background processing in bulk.
     */
    static function retriveTokensByScope($scope){
        $kind_schema = Conf::get("datastore_kind");
        $store = new Store($kind_schema);
        return $store->query("SELECT * FROM $kind_schema WHERE scopes='$scope'");
    }
}