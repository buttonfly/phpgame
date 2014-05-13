<?php 
require_once WORKERMAN_ROOT_DIR . 'Core/SocketWorker.php';
/**
 * 
 * 用这个worker监控文件更新
 * 当文件更新后会给每个worker进程发送平滑重启信号
 * 做到文件更新自动加载到内存
 * 
* @author walkor <worker-man@qq.com>
 */
class FileMonitor extends Man\Core\AbstractWorker
{
    
    /**
     * 需要监控的文件
     * @var array
     */
    protected $filesToInotify = array();
    
    /**
     * 终端已经关闭
     * @var bool
     */
    protected $terminalClosed = false;
    
    /**
     * 该worker进程开始服务的时候会触发一次
     * @return bool
     */
    public function start()
    {
        if(\Man\Core\Lib\Config::get('workerman.debug') != 1)
        {
            return;
        }
        if(!\Man\Core\Master::getQueueId())
        {
            while(1)
            {
                sleep(PHP_INT_MAX);
            }
        }
        $msg_type = $message = 0;
        \Man\Core\Lib\Task::init();
        \Man\Core\Lib\Task::add(1, array($this, 'sendSignalAndGetResult'));
        \Man\Core\Lib\Task::add(1, array($this, 'checkFilesModify'));
        \Man\Core\Lib\Task::add(1, array($this, 'checkTty'));
        while(1)
        {
            $this->collectFiles(true);
            if($this->hasShutDown())
            {
                exit(0);
            }
        }
        return true;
    }
    
    /**
     * 发送文件上报信号，并收集文件列表
     * @return void
     */
    public function sendSignalAndGetResult()
    {
        $this_pid = posix_getpid();
        $pid_worker_map = $this->getPidWorkerMap();
        foreach($pid_worker_map as $pid=>$worker_name)
        {
            if($pid != $this_pid)
            {
                posix_kill($pid, SIGUSR2);
            }
            $this->collectFiles();
        }
    }
    
    /**
     * 从消息队列中获取要监控的文件列表
     * @param bool $block
     * @return void
     */
    protected function collectFiles($block = false)
    {
        $msg_type = $message = null;
        $flag = $block ? 0 : MSG_IPC_NOWAIT;
        if(msg_receive(\Man\Core\Master::getQueueId(), self::MSG_TYPE_FILE_MONITOR, $msg_type, 10000, $message, true, $flag))
        {
            foreach($message as $file)
            {
                if(!isset($this->filesToInotify[$file]))
                {
                    $stat = @stat($file);
                    $mtime = isset($stat['mtime']) ? $stat['mtime'] : 0;
                    $this->filesToInotify[$file] = $mtime;
                }
            }
        }
    }

    /**
     * 发送信号给所有worker
     * @param integer $signal
     * @return void
     */
    public function sendSignalToAllWorker($signal)
    {
        $this_pid = posix_getpid();
        $pid_worker_map = $this->getPidWorkerMap();
        foreach($pid_worker_map as $pid=>$worker_name)
        {
            if($pid != $this_pid)
            {
                posix_kill($pid, $signal);
            }
        }
    }
    
    /**
     * 检查文件更新时间，如果有更改则平滑重启服务（开发的时候用到）
     * @return void
     */
    public function checkFilesModify()
    {
        $has_send_signal = false;
        foreach($this->filesToInotify as $file=>$mtime)
        {
            clearstatcache();
            $stat = @stat($file);
            if(false === $stat)
            {
                unset($this->filesToInotify[$file]);
                continue;
            }
            $mtime_now = $stat['mtime'];
            if($mtime != $mtime_now) 
            {
                $this->filesToInotify[$file] = $mtime_now;
                if(!$has_send_signal)
                {
                    \Man\Core\Lib\Log::add("$file updated and reload workers");
                    $this->sendSignalToAllWorker(SIGHUP);
                    $has_send_signal = true;
                }
            }
        }
    }
    
    /**
     * 检查控制终端是否已经关闭, 如果控制终端关闭，则停止打印数据到终端（发送平滑重启信号）
     * @return void
     */
    public function checkTty()
    {
        if(!$this->terminalClosed && !posix_ttyname(STDOUT))
        {
            // 日志
            $this->notice("terminal closed and restart worker");
            // worker重启时会检测终端是否关闭
            $this->sendSignalToAllWorker(SIGHUP);
            // 设置标记
            $this->terminalClosed = true;
        }
    }
    
} 
