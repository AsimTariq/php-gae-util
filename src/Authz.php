<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-20
 * Time: 23:16
 */

namespace GaeUtil;

use GaeUtil\Model\Resource;
use GaeUtil\Model\UserPermission;

class Authz {

    const READ = "read";
    const WRITE = "write";
    const READWRITE = "read,write";

    /**
     * @param Resource $resource
     * @param $userId
     * @return bool
     */
    static function canRead(Resource $resource, $userId) {
        return self::hasPermission($resource,self::READ, $userId);
    }

    /**
     * @param Resource $resource
     * @param $userId
     * @return bool
     */
    static function canWrite(Resource $resource, $userId) {
        return self::hasPermission($resource, self::WRITE, $userId);
    }

    /**
     * @param Resource $resource
     * @param $userId
     */
    static function assignRead(Resource $resource, $userId) {
        self::assignPermission($resource, self::READ, $userId);
    }

    /**
     * @param Resource $resource
     * @param $userId
     */
    static function assignReadWrite(Resource $resource, $userId) {
        self::assignPermission($resource, self::READWRITE, $userId);
    }

    /**
     * @param Resource $resource
     * @param $userId
     */
    static function reset(Resource $resource, $userId) {

    }

    /**
     * @param Resource $resource
     * @param $permission
     * @param $userId
     * @return bool
     */
    static function hasPermission(Resource $resource, $permission, $userId) {
        $perms = self::getPermissionsForUser($userId);
        $resourceKey = self::getResourceKey($resource);
        foreach ($perms as $perm) {
            if (in_array($permission, $perm->permissions) && $perm->resourceKey == $resourceKey) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $userId
     * @return UserPermission[]
     */
    static function getPermissionsForUser($userId) {
        $kind_schema = self::getPermissionKind();
        $cached = self::getUserCached($userId);
        if (!$cached->exists()) {
            $all = DataStore::fetchAllWhere($kind_schema, ["userId" => (string)$userId]);
            $cached->set($all);
        }
        return self::_mapPermission($cached->get());
    }

    /**
     * @param Resource $resource
     * @return UserPermission[]
     */
    static function getPermissionsForResource(Resource $resource) {
        $resourceKey = self::getResourceKey($resource);
        $kind_schema = self::getPermissionKind();
        $cached = self::getResourceCached($resourceKey);
        if (!$cached->exists()) {
            $all = DataStore::fetchAllWhere($kind_schema, ["resourceKey" => $resourceKey]);
            $cached->set($all);
        }
        return self::_mapPermission($cached->get());
    }

    /**
     * @param $userId
     * @return Cached
     */
    static private function getUserCached($userId) {
        return new Cached(Cached::keymaker(__METHOD__, $userId));
    }

    /**
     * @param $resourceId
     * @return Cached
     */
    static private function getResourceCached($resourceId) {
        return new Cached(Cached::keymaker(__METHOD__, $resourceId));
    }

    /**
     * @param array $arrayOfPermissionData
     * @return UserPermission[]
     */
    static private function _mapPermission(array $arrayOfPermissionData) {
        $arrayOfPermissions = [];
        foreach ($arrayOfPermissionData as $permission) {
            $arrayOfPermissions[] = new UserPermission($permission);
        }
        return $arrayOfPermissions;
    }

    /**
     * @param $kind
     * @param $id
     * @param null $displayName
     */
    static function upsertResource($kind, $id, $displayName = null) {
        $resource = new Resource();
        $resource->id = $id;
        $resource->kind = $kind;
        $resource->displayName = $displayName;
        $kind_schema = self::getResourceKind();
        $key = self::getResourceKey($resource);
        return DataStore::upsert($kind_schema, $key, $resource);
    }

    /**
     * @param Resource $resource
     * @param $permission
     * @param $userId
     */
    static function assignPermission(Resource $resource, $permission, $userId) {
        $kind_schema = self::getPermissionKind();
        $userId = (string)$userId;
        $resourceKey = self::getResourceKey($resource);
        $userPermission = new UserPermission();
        $userPermission->permissions = explode(",", $permission);
        $userPermission->resourceKey = $resourceKey;
        $userPermission->userId = $userId;
        self::getUserCached($userId)->remove();
        self::getResourceCached($resourceKey)->remove();
        $key = Cached::keymaker($resourceKey, $userId);
        return DataStore::upsert($kind_schema, $key, $userPermission);
    }

    static function getResourceKey(Resource $resource) {
        return Cached::keymaker($resource->kind, $resource->id);
    }

    static function getPermissionKind() {
        return Util::classToKindSchema(UserPermission::class);
    }

    static function getResourceKind() {
        return Util::classToKindSchema(Resource::class);
    }
}