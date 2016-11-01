<?php
namespace Phanbook\Tools\Cli;

class Execute
{
    /**
     * single instance of class (needed for singleton)
     * @var object
     */
    protected static $instance;

    /**
     * array of commands
     * @var array
     */
    protected $command = [];

    /**
     * constructor to initialize class
     */
    private function __construct()
    {
    }

    /**
     * Get single instance of class
     *
     * @return $this
     */
    public static function singleton()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * execute a command
     *
     * @param string          $cmd
     * @param string          $file
     * @param int             $line
     * @param string          $stdout string
     * @param string          $stderr Stderr result
     * @param resource|string $stdin  Readable resource stream or string to be passed to proc STDIN
     *
     * @return TRUE|int return exit code of command
     */
    public function execute($cmd, $file, $line, &$stdout = null, &$stderr = null, $stdin = null)
    {
        // Create temporary files to write output/stderr (dont worry about stdin)
        $outFile = tempnam(".", "cli");
        $errFile = tempnam(".", "cli");

        // Map Files to Process's output, input, error to temporary files
        $descriptor = array(0 => array("pipe", "r"),
            1 => array("file", $outFile, "w"),
            2 => array("file", $errFile, "w")
        );

        $start = microtime(true);

        // Start process
        $proc = proc_open($cmd, $descriptor, $pipes);
        if (!is_resource($proc)) {
            $return = 255;
        } else {
            if ($stdin) {
                if (is_resource($stdin)) {
                    stream_copy_to_stream($stdin, $pipes[0]);
                } else {
                    fwrite($pipes[0], $stdin);
                }
            }
            fclose($pipes[0]);
            $return = proc_close($proc);
        }
        $end = microtime(true);

        // Get Output
        $stdout = implode("", file($outFile));
        $stderr = implode("", file($errFile));

        // Remove temp files
        unlink($outFile);
        unlink($errFile);

        $command = new Command;
        $command->command = $cmd;
        $command->file = $file;
        $command->line = $line;
        $command->result_code = $return;
        $command->success = $return == 0 ? true : false;
        $command->stdout = $stdout;
        $command->stderr = $stderr;
        $command->time = ($end - $start);

        $this->command[] = $command;

        return $return == 0 ? true : $return;
    }


    /**
     * Get all commands executed
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->command;
    }


    /**
     * Output the object
     */
    public function __toString()
    {
        if (PHP_SAPI == 'cli') {
            return print_r($this->command, true);
        } else {
            return "<pre>" . print_r($this->command, true) . "</pre>";
        }
    }
}
