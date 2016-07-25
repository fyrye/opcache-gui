<?php
/**
 * OpCacheService.php Description
 * @author fyrye <fyrye@torntech.com>
 * @version 2016.07.25
 */
namespace fyrye\OpCacheGui;

/*
 * Shouldn't need to alter anything else below here
 */
if (!extension_loaded('Zend OPcache')) {
    die('The Zend OPcache extension does not appear to be installed');
}

class OpCacheService
{
    protected $data;
    protected $options;
    protected $defaults = [
        'allow_filelist' => true,
        'allow_invalidate' => true,
        'allow_reset' => true,
        'allow_realtime' => true,
        'refresh_time' => 5,
        'size_precision' => 2,
        'size_space' => false,
        'charts' => true,
        'debounce_rate' => 250
    ];

    private function __construct($options = [])
    {
        $this->options = array_merge($this->defaults, $options);
        $this->data = $this->compileState();
    }

    public static function init($options = [])
    {
        $self = new self($options);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            if (isset($_GET['reset']) && $self->getOption('allow_reset')) {
                echo '{ "success": "' . ($self->resetCache() ? 'yes' : 'no') . '" }';
            } else {
                if (isset($_GET['invalidate']) && $self->getOption('allow_invalidate')) {
                    echo '{ "success": "' . ($self->resetCache($_GET['invalidate']) ? 'yes' : 'no') . '" }';
                } else {
                    echo json_encode($self->getData((empty($_GET['section']) ? null : $_GET['section'])));
                }
            }
            exit;
        } else {
            if (isset($_GET['reset']) && $self->getOption('allow_reset')) {
                $self->resetCache();
                header('Location: ?');
                exit;
            } else {
                if (isset($_GET['invalidate']) && $self->getOption('allow_invalidate')) {
                    $self->resetCache($_GET['invalidate']);
                    header('Location: ?');
                    exit;
                }
            }
        }

        return $self;
    }

    public function getOption($name = null)
    {
        if ($name === null) {
            return $this->options;
        }

        return (isset($this->options[$name])
            ? $this->options[$name]
            : null
        );
    }

    public function getData($section = null, $property = null)
    {
        if ($section === null) {
            return $this->data;
        }
        $section = strtolower($section);
        if (isset($this->data[$section])) {
            if ($property === null || !isset($this->data[$section][$property])) {
                return $this->data[$section];
            }

            return $this->data[$section][$property];
        }

        return null;
    }

    public function canInvalidate()
    {
        return ($this->getOption('allow_invalidate') && function_exists('opcache_invalidate'));
    }

    public function resetCache($file = null)
    {
        $success = false;
        if ($file === null) {
            $success = opcache_reset();
        } else {
            if (function_exists('opcache_invalidate')) {
                $success = opcache_invalidate(urldecode($file), true);
            }
        }
        if ($success) {
            $this->compileState();
        }

        return $success;
    }

    protected function size($size)
    {
        $i = 0;
        $val = ['b', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        while (($size / 1024) > 1) {
            $size /= 1024;
            ++$i;
        }

        return sprintf('%.' . $this->getOption('size_precision') . 'f%s%s',
            $size, ($this->getOption('size_space') ? ' ' : ''), $val[$i]
        );
    }

    protected function compileState()
    {
        $status = opcache_get_status();
        $config = opcache_get_configuration();
        $files = [];
        if (!empty($status['scripts']) && $this->getOption('allow_filelist')) {
            uasort($status['scripts'], function ($a, $b) {
                return $a['hits'] < $b['hits'];
            });
            foreach ($status['scripts'] as &$file) {
                $file['full_path'] = str_replace('\\', '/', $file['full_path']);
                $file['readable'] = [
                    'hits' => number_format($file['hits']),
                    'memory_consumption' => $this->size($file['memory_consumption'])
                ];
            }
            $files = array_values($status['scripts']);
        }
        $overview = array_merge(
            $status['memory_usage'], $status['opcache_statistics'], [
                'used_memory_percentage' => round(100 * (
                        ($status['memory_usage']['used_memory'] + $status['memory_usage']['wasted_memory'])
                        / $config['directives']['opcache.memory_consumption'])),
                'hit_rate_percentage' => round($status['opcache_statistics']['opcache_hit_rate']),
                'wasted_percentage' => round($status['memory_usage']['current_wasted_percentage'], 2),
                'readable' => [
                    'total_memory' => $this->size($config['directives']['opcache.memory_consumption']),
                    'used_memory' => $this->size($status['memory_usage']['used_memory']),
                    'free_memory' => $this->size($status['memory_usage']['free_memory']),
                    'wasted_memory' => $this->size($status['memory_usage']['wasted_memory']),
                    'num_cached_scripts' => number_format($status['opcache_statistics']['num_cached_scripts']),
                    'hits' => number_format($status['opcache_statistics']['hits']),
                    'misses' => number_format($status['opcache_statistics']['misses']),
                    'blacklist_miss' => number_format($status['opcache_statistics']['blacklist_misses']),
                    'num_cached_keys' => number_format($status['opcache_statistics']['num_cached_keys']),
                    'max_cached_keys' => number_format($status['opcache_statistics']['max_cached_keys']),
                    'start_time' => date_format(date_create("@{$status['opcache_statistics']['start_time']}"), 'Y-m-d H:i:s'),
                    'last_restart_time' => ($status['opcache_statistics']['last_restart_time'] == 0
                        ? 'never'
                        : date_format(date_create("@{$status['opcache_statistics']['last_restart_time']}"), 'Y-m-d H:i:s')
                    )
                ]
            ]
        );
        $directives = [];
        ksort($config['directives']);
        foreach ($config['directives'] as $k => $v) {
            $directives[] = ['k' => $k, 'v' => $v];
        }
        $version = array_merge(
            $config['version'],
            [
                'php' => phpversion(),
                'server' => $_SERVER['SERVER_SOFTWARE'],
                'host' => (function_exists('gethostname')
                    ? gethostname()
                    : (php_uname('n')
                        ? : (empty($_SERVER['SERVER_NAME'])
                            ? $_SERVER['HOST_NAME']
                            : $_SERVER['SERVER_NAME']
                        )
                    )
                )
            ]
        );

        return [
            'version' => $version,
            'overview' => $overview,
            'files' => $files,
            'directives' => $directives,
            'blacklist' => $config['blacklist'],
            'functions' => get_extension_funcs('Zend OPcache')
        ];
    }
}
