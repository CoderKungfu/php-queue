<?php
namespace PHPQueue;

use Clio\Console;
use Clio\Daemon as D;

abstract class Daemon
{
    public $queue_name = null;
    public $pid_file;
    public $log_root;
    public $stdout = '/tmp/stdout.log';
    public $stderr = '/tmp/stderr.log';

    /**
     * @param string $pid_file
     * @param string $log_root
     */
    public function __construct($pid_file, $log_root)
    {
        $this->pid_file = $pid_file;
        $this->log_root = $log_root;
    }

    public function run()
    {
        global $argv;
        if (empty($argv[1]))
        {
            Console::output("Unknown action.");
            die();
        }
        if (empty($this->queue_name))
        {
            Console::output("Queue is not set.");
            die();
        }
        switch($argv[1])
        {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            default:
                Console::output("Unknown action.");
                break;
        }
    }

    protected function start()
    {
        Console::stdout('Starting... ');
        try
        {
            if (D::isRunning($this->pid_file)) {
                Console::output('%y[Already Running]%n');
            } else {
                $queue = $this->queue_name;
                $log_path = $this->log_root;
                D::work(array(
                          'pid' => $this->pid_file
                        , 'stdout' => $this->stdout
                        , 'stderr' => $this->stderr
                    ),
                    function($stdin, $stdout, $sterr) use ($queue, $log_path)
                    {
                        $runner = new Runner($queue, array('logPath'=>$log_path));
                        $runner->run();
                    }
                );
                Console::output('%g[OK]%n');
            }
        }
        catch (\Exception $ex)
        {
            Console::output('%r[FAILED]%n');
        }
    }

    protected function stop()
    {
        Console::stdout('Stopping... ');
        try
        {
            if (!D::isRunning($this->pid_file)) {
                Console::output('%y[Daemon not running]%n');
            } else {
                D::kill($this->pid_file, true);
                Console::output('%g[OK]%n');
            }
        }
        catch (\Exception $ex)
        {
            Console::output('%r[FAILED]%n');
        }
    }

    protected function restart()
    {
        $this->stop();
        $this->start();
    }
} 