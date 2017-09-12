<?php
    namespace Vec2\Objects;
    use \DateTime;
    use \DateTimeZone;
    use \JsonSerializable;

    class Response implements JsonSerializable {
        /** @var boolean Whether the respone was a success */
        public $status;
        /** @var DateTime When the API call was executed */
        public $time;
        /** @var array The messages */
        public $messages;
        /** @var mixed Contains the data */
        public $data;

        public function __construct(bool $status = false) {
            $this->status = $status;
            $this->time = new DateTime();
            $this->messages = new MessageGroup();
            $this->data = [];
        }

        /**
         * Gets a data entry
         *
         * @param string $name
         * @return mixed
         */
        public function __get(string $name) {
            if(isset($this->data[$name])) {
                return $this->data[$name];
            }
        }

        /**
         * Sets a data entry
         *
         * @param string $name
         * @param mixed $value
         */
        public function __set(string $name, $value) {
            $this->data[$name] = $value;
        }

        /**
         * Creates a response object from array data
         *
         * @param array $data The data to build from
         * @return Response
         */
        public static function from(array $data) : Response {
            $r = new Response();
            $r->status = isset($data['status']) ? boolval($data['status']) : false;
            $r->time = isset($data['time']) ? new DateTime($data['time']) : new DateTime();
            $r->time->setTimeZone(new DateTimeZone('UTC'));
            $r->data = isset($data['data']) ? $data['data'] : [];
            $r->messages = new MessageGroup();
            if(isset($data['data']['messages'])) {
                foreach($data['data']['messages'] as $key => $group) {
                    // set key
                    $r->messages->{$key} = array_map(function($m) {
                        return new Message(intval($m['code']), $m['type'], $m['message']);
                    }, $group);
                }
            }
            return $r;
        }

        /**
         * Returns a json serializable array
         */
        public function jsonSerialize() {
            return [
                'status' => $this->status,
                'time' => $this->time->getTimestamp(),
                'data' => $this->data,
                'messages' => $this->messages
            ];
        }
    }