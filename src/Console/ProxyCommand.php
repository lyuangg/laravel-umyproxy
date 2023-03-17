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

    protected $binfiles = [
        'mac_arm' => 'vendor/bin/umyproxy-darwin-arm64',
        'mac_intel' => 'vendor/bin/umyproxy-darwin-amd64',
        'linux_arm' => 'vendor/bin/umyproxy-linux-arm64',
        'linux_intel' => 'vendor/bin/umyproxy-linux-amd64',
    ];

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
        list($os, $architecture) = $this->detectOsAndArchitecture();
        $binKey = $os . '_' . $architecture;
    
        if (isset($this->binfiles[$binKey])) {
            return base_path($this->binfiles[$binKey]);
        } else {
            $this->error("Unsupported system: $os $architecture");
            die;
        }
    }

    private function detectOsAndArchitecture()
    {
        $os = php_uname("s");
        $architecture = php_uname("m");

        if (strpos($os, "Linux") !== false) {
            $os = "linux";
        } elseif (strpos($os, "Darwin") !== false) {
            $os = "mac";
        } else {
            $os = "unknown";
        }

        if (strpos($architecture, "arm") !== false) {
            $architecture = "arm";
        } elseif (strpos($architecture, "x86") !== false) {
            $architecture = "intel";
        } else {
            $architecture = "unknown";
        }

        return array($os, $architecture);
    }
}
