<?php
    namespace Vec2\Objects;
    use \Exception;

    class Message extends Exception {
        /** @var string Contains the type of the exception */
        protected $_type;

        public function __construct(int $code, string $type, string $message) {
            $this->code = $code;
            $this->_type = $type;
            $this->message = $message;
        }

        /**
         * Gets the type
         *
         * @return string
         */
        public function getType() : string {
            return $this->_type;
        }

        public function __toString() : string {
            return $this->code.' '.$this->_type.' '.$this->message;
        }
    }