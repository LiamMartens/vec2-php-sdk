<?php
    namespace Vec2\Objects;
    use \JsonSerializable;

    class Tag implements JsonSerializable {
        /** @var int Contains the tag id */
        public $id;
        /** @var string Contains the tag name */
        public $tag;

        /**
         * Creates tag from array data
         *
         * @param array $data
         * @return Tag
         */
        public function from(array $data) : Tag {
            $t = new Tag();
            $t->id = isset($data['id']) ? intval($data['id']) : null;
            $t->tag = isset($data['tag']) ? $data['tag'] : '';
            return $t;
        }

        /**
         * Returns an array to be serialized by JSON ext
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'id' => $this->id,
                'tag' => $this->tag
            ];
        }
    }