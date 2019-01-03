<?php

use GaeUtil\Authz;
use GaeUtil\DataStore;
use GaeUtil\Model\Resource;
use GaeUtil\Model\UserPermission;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2018-12-24
 * Time: 14:30
 */
class AuthzTest extends TestCase {

    public function setUp() {
        $kind_schema = Authz::getPermissionKind();
        DataStore::deleteAll($kind_schema);
    }

    function testAssignRead() {
        $resource = new Resource();
        $resource->kind = "myresource";
        $resource->id = rand(10, 23);
        $userId = rand(234, 700);
        Authz::assignRead($resource, $userId);

        $this->assertTrue(Authz::canRead($resource, $userId));
        $this->assertFalse(Authz::canRead($resource, "dummyheyhey"));
        $this->assertFalse(Authz::canWrite($resource, "dummyheyhey"));
        $this->assertFalse(Authz::canWrite($resource, $userId));
    }

    function testAssignWrite() {
        $resource = new Resource();
        $resource->kind = "myresource";
        $resource->id = rand(10, 23);

        $userId = rand(234, 700);
        Authz::assignReadWrite($resource, $userId);

        $this->assertTrue(Authz::canRead($resource, $userId));
        $this->assertTrue(Authz::canWrite($resource, $userId));
        $this->assertFalse(Authz::canRead(new Resource(), $userId));
        $this->assertFalse(Authz::canWrite(new Resource(), $userId));
        $this->assertFalse(Authz::canWrite($resource, "dummyheyhey"));
        $this->assertFalse(Authz::canWrite($resource, "dummyheyhey"));
    }

    function testEntryAssignments() {
        $resource = new Resource();
        $resource->kind = "myresource";
        $resource->id = rand(10, 23);

        $users = [123, 1233, 4123];
        foreach ($users as $userId) {
            Authz::assignReadWrite($resource, $userId);
        }
        $permissions = Authz::getPermissionsForResource($resource);
        $this->assertEquals(count($users), count($permissions));
        $this->assertInstanceOf(UserPermission::class, $permissions[0]);
    }

}