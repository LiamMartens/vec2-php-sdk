<?php
    namespace Vec2\Objects;
    use \ArrayAccess;
    use \Countable;
    use \Iterator;
    use \JsonSerializable;

    class MessageGroup implements ArrayAccess, Countable, Iterator, JsonSerializable {
        /** @var int iterator position */
        protected $_position = 0;
        /** @var array Contains all the messages */
        public $messages;

        /** 
         * Fetches the array keys
         *
         * @return array
         */
        protected function keys() : array {
            return array_keys($this->messages);
        }

        /**
         * Rewinds to initial position
         */
        public function rewind() {
            $this->_position = 0;
        }

        /**
         * Gets the current key
         *
         * @return mixed
         */
        public function key() {
            return $this->keys()[$this->_position];
        }

        /**
         * Gets current iterator item
         *
         * @return mixed
         */
        public function current() {
            return $this->messages[$this->key()];
        }

        /**
         * Moves to next
         */
        public function next() {
            ++$this->_position;
        }

        /**
         * Checks for existing value
         *
         * @return boolean
         */
        public function valid() {
            return isset($this->keys()[$this->_position])&&isset($this->messages[$this->key()]);
        }

        /**
         * Counts the messages
         *
         * @return int
         */
        public function count() {
            $total = 0;
            foreach($this->messages as $f => $msg) {
                $total += count($msg);
            }
            return $total;
        }

        /**
         * Gets from the messages array
         *
         * @param string $name
         * @return mixed
         */
        public function __get(string $name) {
            if(isset($this->messages[$name])) {
                return $this->messages[$name];
            }
        }

        /**
         * Sets in the messages array
         *
         * @param string $name
         * @param mixed $value
         */
        public function __set(string $name, $value) {
            $this->messages[$name] = $value;
        }

        /**
         * Sets an offset
         *
         * @param mixed $offset
         * @param mixed $value
         */
        public function offsetSet($offset, $value) {
            $this->messages[$offset] = $value;
        }

        /**
         * Check for existance
         *
         * @param mixed $offset
         * @return boolean
         */
        public function offsetExists($offset) : bool {
            return isset($this->messages[$offset]);
        }

        /**
         * Removes from the messages
         *
         * @param mixed $offset
         */
        public function offsetUnset($offset) {
            unset($this->messages[$offset]);
        }

        /**
         * Gets from an offset
         *
         * @param mixed $offset
         * @return mixed
         */
        public function offsetGet($offset) {
            return $this->offsetExists($offset) ? $this->messages[$offset] : null;
        }

        /**
         * Returns a json serializable array
         */
        public function jsonSerialize() {
            return $this->messages;
        }
    }