<?php
    namespace Vec2\Objects;
    use \DateTime;
    use \DateTimeZone;
    use \JsonSerializable;

    class Profile implements JsonSerializable {
        /** @var string Contains the profile username */
        public $username;
        /** @var string Contains the first name */
        public $first_name;
        /** @var string Contains the last name */
        public $last_name;
        /** @var DateTime Contains the created datetime */
        public $created_at;

        /**
         * Creates Profile instance from array data
         *
         * @param array $data
         * @return Profile
         */
        public static function from(array $data) : Profile {
            $p = new Profile();
            $p->username = isset($data['username']) ? $data['username'] : '';
            $p->first_name = isset($data['first_name']) ? $data['first_name'] : '';
            $p->last_name = isset($data['last_name']) ? $data['last_name'] : '';
            $p->created_at = new DateTime($data['created_at']);
            $p->created_at->setTimeZone(new DateTimeZone('UTC'));
            return $p;
        }

        /**
         * Returns an array to be serialized by JSON ext
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'username' => $this->username,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'created_at' => $this->created_at->format('Y-m-d H:i:s')
            ];
        }
    }