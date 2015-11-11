<?php declare(strict_types=1);
namespace Phan;

use \Phan\Configuration;
use \Phan\Log;

class CLI {

    /**
     * @var string[]
     * The set of file names to analyze
     */
    private $file_list = [];

    /**
     * Create and read command line arguments, configuring
     * \Phan\Configuration as a side effect.
     */
    public function __construct() {
        global $argv;

        // Parse command line args
        $opts = getopt("f:m:o:c:haqbpi::");

        foreach($opts as $key => $value) {
            switch($key) {
            case 'h':
                $this->usage();
                break;
            case 'f':
                if(is_file($value) && is_readable($value)) {
                    $this->file_list =
                        file($value, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
                } else {
                    Log::err(Log::EFATAL, "Unable to open $value");
                }
                break;
            case 'm':
                if(!in_array($value, ['verbose','short','json','csv'])) {
                    $this->usage("Unknown output mode: $value");
                }
                Log::setOutputMode($value);
                break;
            case 'c':
                Configuration::instance()
                    ->parent_constructor_required =
                    explode(',', $value);
                break;
            case 'q':
                Configuration::instance()->quick_mode = true;
                break;
            case 'b':
                Configuration::instance()->backward_compatibility_checks = true;
                break;
            case 'p':
                Configuration::instance()->progress_bar = true;
                break;
            case 'o':
                Log::setFilename($value);
                break;
            case 'i':
                Log::setOutputMask(Log::getOutputMask()^Log::EUNDEF);
                break;
            default:
                $this->usage("Unknown option '-$key'"); break;
            }
        }

        $pruneargv = array();
        foreach($opts as $opt => $value) {
            foreach($argv as $key => $chunk) {
                $regex = '/^'. (isset($opt[1]) ? '--' : '-') . $opt . '/';

                if ($chunk == $value
                    && $argv[$key-1][0] == '-'
                    || preg_match($regex, $chunk)
                ) {
                    array_push($pruneargv, $key);
                }
            }
        }

        while($key = array_pop($pruneargv)) {
            unset($argv[$key]);
        }

        if(empty($this->file_list) && count($argv) < 2) {
            Log::err(Log::EFATAL, "No files to analyze");
        }

        foreach($argv as $arg) if($arg[0]=='-') {
            $this->usage("Unknown option '{$arg}'");
        }

        $this->file_list = array_merge(
            $this->file_list,
            array_slice($argv,1)
        );
    }

    /**
     * @return string[]
     * Get the set of files to analyze
     */
    public function getFileList() : array {
        return $this->file_list;
    }

    private function usage(string $msg='') {
        global $argv;

        if(!empty($msg)) {
            echo "$msg\n";
        }

        echo <<<EOB
Usage: {$argv[0]} [options] [files...]
  -f <filename>   A file containing a list of PHP files to be analyzed
  -q              Quick mode - doesn't recurse into all function calls
  -b              Check for potential PHP 5 -> PHP 7 BC issues
  -i              Ignore undeclared functions and classes
  -c              Comma-separated list of classes that require parent::__construct() to be called
  -m <mode>       Output mode: verbose, short, json, csv
  -o <filename>   Output filename
  -p              Show progress bar
  -a              Dump AST of provides files (for debugging)
  -h			  This help

EOB;
        exit;
    }

    /**
     * Update a progress bar on the screen
     *
     * @return null
     */
    public static function progress(string $msg, float $p) {
        if (!Configuration::instance()->progress_bar) {
            return;
        }
        echo "\r$msg ";
        $current = (int)($p * 60);
        $rest = 60 - $current;
        echo str_repeat("\u{25b1}", $current);
        echo str_repeat("\u{25b0}", $rest);
        echo " ".sprintf("% 3d",(int)(100*$p))."%"."\r";
    }

}