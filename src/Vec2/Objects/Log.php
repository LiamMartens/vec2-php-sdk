<?php
    namespace Vec2\Objects;

    class Log {
        const RESPONSE = 'response';
        const DATA = 'data';
        const HEADERS = 'headers';

        /** @var string The file to write to */
        private $_file;
        /** @var array What stuff to log */
        private $_log;
        /** @var callable? When to log */
        private $_condition;

        public function __construct(string $file, array $log_these = [], $condition = null) {
            $this->_file = $file;
            $this->_log = $log_these;
            $this->_condition = $condition;
            // sort the log these to always have the same result
            sort($this->_log);
        }

        /**
         * Executes the condition and returns it's value
         * Just returns TRUE if no condition was passed
         *
         * @param string $method The method that was executed
         * @param string $url The url that was called
         * @param array $data The data that was sent
         * @param bool $auth_endpoint The auth endpoint flag
         * @return bool
         */
        public function condition(string $method, string $url, array $data, bool $auth_endpoint) : bool {
            if(isset($this->_condition) && is_callable($this->_condition)) {
                return boolval($this->_condition($method, $url, $data, $auth_endpoint));
            }
            return true;
        }

        /**
         * Makes a string at least a certain length and max that same length by
         * splitting it into newlines
         *
         * @param string $value The value to min max
         * @param int $length The length to reach
         * @param int $pad Make sure to leave this amount of spaces after (pad length is included in length)
         * @return string
         */
        public function split(string $value, int $length, int $pad = 0) : string {
            $length -= $pad;
            if(strlen($value) <= $length) {
                return str_pad($value, $length).str_repeat(' ', $pad);
            } else {
                return preg_replace('/(.{1,'.$length.'})/', '$1'.PHP_EOL, $value).str_repeat(' ', $pad);
            }
        }

        /**
         * Writes to the log file
         *
         * @param string $content
         * @return Log
         */
        public function write(string $content) : Log {
            $prefix = '';
            // check filesize
            if(!is_file($this->_file) || filesize($this->_file)==0) {
                $prefix = $this->split('DATETIME', 24, 4).$this->split('METHOD', 10, 4).$this->split('URL', 48, 4);
                foreach($this->_log as $p) {
                    $prefix.=$this->split(strtoupper($p), 96, 4);
                }
                $prefix=trim($prefix).PHP_EOL;
            }
            $f = fopen($this->_file, 'a');
            fwrite($f, $prefix.$content);
            fclose($f);
            return $this;
        }

        /**
         * Inserts a log entry taking into account the log these params
         *
         * @param string $method The method that was called
         * @param string $url The URL that was called
         * @param array $params All possible data to be logged
         * @return Log
         */
        public function log(string $method, string $url, array $params) : Log {
            $log = $this->split(date('Y-m-d H:i:s'), 24, 4).
                    $this->split($method, 10, 4).
                    $this->split($url, 48, 4);
            foreach($this->_log as $p) {
                if(isset($params[$p])) {
                    $value = (string)(is_array($params[$p]) ? json_encode($params[$p]) : $params[$p]);
                    $log .= $this->split($value, 96, 4);
                }
            }
            // write log entry
            $this->write(trim($log).PHP_EOL);
            // return self for chaining
            return $this;
        }
    }