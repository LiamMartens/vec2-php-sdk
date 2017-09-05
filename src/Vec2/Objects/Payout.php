<?php
    namespace Vec2\Objects;
    use \DateTime;
    use \JsonSerializable;

    class Payout implements JsonSerializable {
        /** @var int Contains the id */
        public $id;
        /** @var string Contains the user to pay out */
        public $user;
        /** @var string PayPal email */
        public $paypal;
        /** @var float The amount of the payout */
        public $amount;
        /** @var bool Completed */
        public $completed;
        /** @var DateTime Last updated time */
        public $updated_at;
        /** @var DateTime Created at time */
        public $created_at;

        /**
         * Creates a payout from an array
         *
         * @param array $data
         * @return Payout
         */
        public static function from(array $data) : Payout {
            $p = new Payout();
            $p->id = isset($data['id']) ? intval($data['id']) : -1;
            $p->user = isset($data['user']) ? $data['user'] : '';
            $p->paypal = isset($data['paypal']) ? $data['paypal'] : '';
            $p->amount = isset($data['amount']) ? floatval($data['amount']) : 0.0;
            $p->completed = isset($data['completed']) ? (intval($data['completed'])==1) : false;
            $p->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
            $p->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
            return $p;
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
                'paypal' => $this->paypal,
                'amount' => $this->amount,
                'completed' => $this->completed,
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at->format('Y-m-d H:i:s')
            ];
        }
    }