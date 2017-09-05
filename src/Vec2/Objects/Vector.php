<?php
    namespace Papi\Vec2\Objects;
    use \DateTime;
    use \JsonSerializable;

    class Vector implements JsonSerializable {
        /** @var string Contains the vector id */
        public $id;
        /** @var string Contains the user id */
        public $user;
        /** @var string Contains the vector title */
        public $title;
        /** @var boolean Contains whether the vector is shared */
        public $is_shared;
        /** @var float Contains the price */
        public $price;
        /** @var array Contains the tags */
        public $tags;
        /** @var DateTime Contains the updated at time */
        public $updated_at;
        /** @var DateTime Contains the created at time */
        public $created_at;

        /**
         * Creates a Vector from array data
         *
         * @param array $data
         * @return Vector
         */
        public static function from(array $data) : Vector {
            $v = new Vector();
            $v->id = isset($data['id']) ? $data['id'] : null;
            $v->user = isset($data['user']) ? $data['user'] : '';
            $v->title = isset($data['title']) ? $data['title'] : '';
            $v->is_shared = isset($data['is_shared']) ? (intval($data['is_shared'])==1) : false;
            $v->price = isset($data['price']) ? floatval($data['price']) : 0.0;
            $v->tags = array_map(function($t) {
                $t['id'] = $t['tag_id'];
                return Tag::from($t);
            }, isset($data['tags']) ? $data['tags'] : []);
            $v->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
            $v->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
            return $v;
        }

        /**
         * Returns a JSON serializeable array
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'id' => $this->id,
                'user' => $this->user,
                'title' => $this->title,
                'is_shared' => $this->is_shared,
                'price' => $this->price,
                'tags' => $this->tags,
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at->format('Y-m-d H:i:s')
            ];
        }
    }