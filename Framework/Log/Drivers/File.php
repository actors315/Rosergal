<?php
/**
 * Created by PhpStorm.
 * User: xiehuanjin
 * Date: 2019/1/12
 * Time: 16:19
 */

namespace Twinkle\Log\Drivers;


use Twinkle\Log\Format\FileLine;

class File extends Log
{
    const ROTATE_HOUR = 'hour';

    const ROTATE_DAY = 'day';

    const ROTATE_MONTH = 'month';

    const ROTATE_YEAR = 'year';

    private static $ROTATE_MAP = [
        self::ROTATE_HOUR => ['Y/m/d', 'H_'],
        self::ROTATE_DAY => 'Y/m/d',
        self::ROTATE_MONTH => 'Y/m',
        self::ROTATE_YEAR => 'Y',
    ];

    public $logFile = 'error.log';

    public $logPath = '.';

    public $rotate = self::ROTATE_DAY;

    public function init($config)
    {
        parent::init($config);

        $prefix = '';
        if (isset(self::$ROTATE_MAP[$this->rotate])) {
            if (is_array(self::$ROTATE_MAP[$this->rotate])) {
                $prefix = date(self::$ROTATE_MAP[$this->rotate][1]);
                $path = date(self::$ROTATE_MAP[$this->rotate][0]);
            } else {
                $path = date(self::$ROTATE_MAP[$this->rotate]);
            }
            $this->logPath = $this->logPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        $this->logFile = $this->logPath . DIRECTORY_SEPARATOR . $prefix . $this->logFile;
    }

    public function process($level, $trace, $message, $context)
    {
        $log = new FileLine($message, $trace, $level, $context);

        if ($this->useBuffer) {
            $this->logQueue[] = $log->format();
            if (count($this->logQueue) >= $this->bufferSize) {
                $this->flushLogs();
            }
        } else {
            $this->write($log);
        }
    }

    public function write($line)
    {
        return file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}