<?php
namespace Huluo\Extend;

/**
 * 记录操作步骤
 */
class Queue
{
    protected $sRobot;
    protected $sQueue;
    protected $aQueue = [];

    /**
     * 初始进程
     */
    public function __construct($sRobot)
    {
        $this->sRobot = $sRobot;
        $this->sQueue = __DIR__ . '/cache/queue.php';
        existsOrCreate($this->sQueue);
        if (is_file($this->sQueue)) {
            $this->aQueue = require $this->sQueue;
            if (!is_array($this->aQueue)) {
                $this->aQueue = [];
            }
        }
    }

    /**
     * 查询进程
     */
    public function get($sQueue)
    {
        return $this->aQueue[$this->sRobot][$sQueue] ?? '';
    }

    /**
     * 添加进程
     */
    public function add($sQueue, $sValue)
    {
        if (!$sQueue || !$sValue) {
            return false;
        }

        // 值存在的时候不更新
        if (isset($this->aQueue[$this->sRobot][$sQueue]) && ($this->aQueue[$this->sRobot][$sQueue] == $sValue)) {
            return true;
        }

        // 不存在更新数据
        $this->aQueue[$this->sRobot][$sQueue] = $sValue;
        array_save($this->aQueue, $this->sQueue);
        return true;
    }
}