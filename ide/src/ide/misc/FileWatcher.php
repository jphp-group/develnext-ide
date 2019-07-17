<?php
namespace ide\misc;

use framework\core\Component;
use framework\core\Event;
use php\lib\fs;
use php\time\Timer;

/**
 * Class FileWatcher
 * @package ide\misc
 */
class FileWatcher extends Component
{
    const CHECK_INTERVAL = '1s';

    /**
     * @var mixed
     */
    private $file;

    /**
     * @var Timer
     */
    private $timer;

    /**
     * @var int
     */
    private $time;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $hash;

    function __construct($file)
    {
        parent::__construct();

        $this->file = $file;
        $this->time = fs::time($file);
        $this->size = fs::size($file);
        $this->hash = fs::isFile($file) ? fs::hash($file, 'SHA-256') : null;
    }

    /**
     * @return bool
     */
    function isWatching(): bool
    {
        return !!$this->timer;
    }

    /**
     * @param string $interval
     */
    function start(string $interval = self::CHECK_INTERVAL)
    {
        $this->stop();

        $this->timer = Timer::every($interval, function () {
            $newTime = fs::time($this->file);
            $newSize = fs::size($this->file);
            $newHash = fs::isFile($this->file) ? fs::hash($this->file, 'SHA-256') : null;

            if ($newTime !== $this->time || $newSize != $this->size || $newHash != $this->hash){
                $this->time = $newTime;
                $this->size = $newSize;
                $this->hash = $newHash;

                $this->trigger(new Event('change', $this, null, ['oldTime' => $this->time, 'newTime' => $newTime]));
            }
        });
    }

    function stop()
    {
        if ($this->timer) {
            $this->timer->cancel();
            $this->timer = null;
        }
    }

    public function free()
    {
        $this->stop();
        parent::free();
    }
}