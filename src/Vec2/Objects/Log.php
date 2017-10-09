<?php
    namespace Vec2\Objects;
    use \PDO;

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
         * Writes to the log file
         *
         * @param string $content
         * @return Log
         */
        public function insert(array $params) : Log {
            if($db = new PDO('sqlite:'.$this->_file)) {
                // create table if not exists
                $db->query('CREATE TABLE IF NOT EXISTS log (
                    datetime DATETIME,
                    method VARCHAR(6),
                    url TEXT,
                    response TEXT,
                    data TEXT,
                    headers TEXT
                )');
                // insert
                $columns = array_keys($params);
                $q = $db->prepare('INSERT INTO log ('.implode(',', $columns).') VALUES ('.trim(str_repeat('?,',count($columns)), ',').')');
                $q->execute(array_values($params));
            }
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
            $insert = [
                'datetime' => date('Y-m-d H:i:s'),
                'method' => $method,
                'url' => $url
            ];
            // insert requested parameters
            foreach($this->_log as $p) {
                if(isset($params[$p])) {
                    $value = is_array($params[$p]) ? json_encode($params[$p]) : print_r($params[$p], true);
                    $insert[$p] = $value;
                }
            }
            // insert log entry
            $this->insert($insert);
            return $this;
        }
    }