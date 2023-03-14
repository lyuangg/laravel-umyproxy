<?php
namespace Umyproxy\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ProxyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'umyproxy:serve 
                                {--host= : mysql host}
                                {--life= : mysql connection max life time }
                                {--port= : mysql port }
                                {--size= : mysql pool size}
                                {--socket= : umyproxy socket path}
                                {--wait= : wait mysql connection timeout}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动umyproxy，默认自动读取 config/database.php 的配置';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->startServ();
        return 0;
    }

    private function startServ()
    {
        $this->info("start...");
        $cmd = $this->makeProxyCommand();
        $this->info($cmd);

        $p = Process::fromShellCommandline($cmd, null, []);
        $p->setWorkingDirectory(getcwd());
        $p->setTimeout(null);
        $p->run(function($type, $output) {
            $this->info($output);
        });
    }

    private function makeProxyCommand()
    {
        $bin = $this->binFile();

        $host = config("database.connections.mysql.host");
        if ($this->option('host')) {
            $host = $this->option('host');
        }
        $life = 0;
        if($this->option("life")) {
            $life = $this->option('life');
        }
        $port = config("database.connections.mysql.port");
        if($this->option("port")) {
            $port = $this->option('port');
        }
        $size = 0;
        if($this->option("size")) {
            $size = $this->option('size');
        }
        $socket = "/tmp/umyproxy.socket";
        if($this->option("socket")) {
            $socket = $this->option('socket');
        }
        $wait = 0;
        if($this->option("wait")) {
            $wait = $this->option('wait');
        }

        $args = " --host=$host --port=$port --socket=$socket";
        if($size) {
            $args .= " --size=$size";
        }
        if($life) {
            $args .= " --life=$life";
        }
        if($wait) {
            $args .= " --wait=$wait";
        }

        return $bin.$args;
    }

    private function binFile()
    {
        // windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->error("Does not support windows");
            die;
        }

        // macos
        $binfile = "";
        if (strtolower(php_uname('s')) == 'darwin') {
            $binfile = base_path("vendor/bin/umyproxy-mac");
        } else {
            $binfile = base_path("vendor/bin/umyproxy-linux");
        }
        if(!file_exists($binfile)) {
            $this->error("$binfile not found!");
            die;
        }
        return $binfile;
    }
}
