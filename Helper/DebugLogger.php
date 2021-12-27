<?php

namespace Swarming\SubscribePro\Helper;

class DebugLogger
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $config;

    /**
     * DebugLogger constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Model\Config\Advanced $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Logs the full stack trace to the system.log
     */
    public function logStackTrace()
    {
        // Check if the configuration value is set for debugging
        if ($this->config->isDebuggingEnabled()) {
            foreach ($this->getStackTrace() as $line) {
                $this->logger->info($line);
            }
        }
    }

    /**
     * Returns an array of strings that hold the full stack trace
     * @return array
     */
    private function getStackTrace()
    {
        // Initialize empty array to hold return
        $traceArray = [];

        // Retrieve the debug backtrace
        $trace = debug_backtrace();

        // Pop first element off
        $caller = array_shift($trace);
        $function_name = isset($caller['function']) ? $caller['function'] : '';
        $traceArray[] = sprintf('%s: Called from %s:%s', $function_name, $caller['file'], $caller['line']);
        foreach ($trace as $entry_id => $entry) {
            $entry['file'] = $entry['file'] ? : '-';
            $entry['line'] = $entry['line'] ? : '-';
            if (empty($entry['class'])) {
                $traceArray[] = sprintf(
                    '%s %3s. %s() %s:%s',
                    $function_name,
                    $entry_id + 1,
                    $entry['function'],
                    $entry['file'],
                    $entry['line']
                );
            } else {
                $traceArray[] = sprintf(
                    '%s %3s. %s->%s() %s:%s',
                    $function_name,
                    $entry_id + 1,
                    $entry['class'],
                    $entry['function'],
                    $entry['file'],
                    $entry['line']
                );
            }
        }
        return $traceArray;
    }
}
