<?php
    namespace Papi\Vec2\Objects;
    use \JsonSerializable;

    class Customer implements JsonSerializable {
        /** @var string The ID of the customer */
        public $id;
        /** @var string The name on the card */
        public $name;
        /** @var int The expiry month of the customer card */
        public $exp_month;
        /** @var int The expiry year of the customer card */
        public $exp_year;
        /** @var string The last 4 digits of the card */
        public $last4;

        /**
         * Builds a customer from array data
         *
         * @param array $data
         * @return Customer
         */
        public static function from(array $data) : Customer {
            $c = new Customer();
            $c->id = isset($data['id']) ? $data['id'] : '';
            $c->name = isset($data['name']) ? $data['name'] : '';
            $c->exp_month = isset($data['exp_month']) ? intval($data['exp_month']) : 1;
            $c->exp_year = isset($data['exp_year']) ? intval($data['exp_year']) : date('Y');
            $c->last4 = isset($data['last4']) ? $data['last4'] : '';
            return $c;
        }

        /**
         * Returns a JSON serializable array
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'exp_month' => $this->exp_month,
                'exp_year' => $this->exp_year,
                'last4' => $this->last4
            ];
        }
    }