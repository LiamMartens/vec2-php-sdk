<?php
    namespace Papi\Vec2\Objects;
    use \DateTime;
    use \JsonSerializable;

    class Sale implements JsonSerializable {
        /** @var int The id of the sale */
        public $id;
        /** @var string The user who bought */
        public $user;
        /** @var string The vector which was purchased */
        public $vector;
        /** @var float The price of the sale */
        public $price;
        /** @var string The transaction id */
        public $transaction;
        /** @var int The status of the sale */
        public $status;
        /** @var DateTime Last updated time */
        public $updated_at;
        /** @var DateTime Last created time */
        public $created_at;

        /**
         * Creates a sale from an array
         *
         * @param array $data
         * @return Sale
         */
        public static function from(array $data) : Sale {
            $s = new Sale();
            $s->id = isset($data['id']) ? intval($data['id']) : -1;
            $s->user = isset($data['user']) ? $data['user'] : '';
            $s->vector = isset($data['vector']) ? $data['vector'] : '';
            $s->price = isset($data['price']) ? floatval($data['price']) : 0;
            $s->transaction = isset($data['transaction']) ? $data['transaction'] : '';
            $s->status = isset($data['status']) ? intval($data['status']) : 1;
            $s->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
            $s->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
            return $s;
        }

        /**
         * Returns a JSON serializable array
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'id' => $this->id,
                'user' => $this->user,
                'vector' => $this->vector,
                'price' => $this->price,
                'transaction' => $this->transaction,
                'status' => $this->status,
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at->format('Y-m-d H:i:s')
            ];
        }
    }