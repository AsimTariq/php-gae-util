<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 19/03/2018
 * Time: 15:05
 */

namespace GaeUtil;

use google\appengine\api\taskqueue\PushQueue;
use google\appengine\api\taskqueue\PushTask;

class Tasks {

    static $tasks = [];

    static public function add($url_path, $params=[]) {
        $task = new PushTask($url_path, $params);
        self::$tasks[] = $task;
        if (count(self::$tasks) == 100) {
            self::flush();
        }
        return $task->getQueryData();
    }

    static public function flush() {
        static $queue;
        if (is_null($queue)) {
            $queue = new PushQueue();
        }
        $queue->addTasks(self::$tasks);
        self::$tasks = [];
        syslog(LOG_INFO, "Flushed " .count(self::$tasks) ." tasks to the task queue.");
    }
}