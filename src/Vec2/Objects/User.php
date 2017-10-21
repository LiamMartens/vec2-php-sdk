<?php
    namespace Vec2\Objects;
    use \Vec2\Vec2;
    use \DateTime;

    class User extends Profile {
        /** @var string Contains the user id */
        public $user_id;
        /** @var string Contains the users' email */
        public $email;
        /** @var boolean Contains whether the email is verified */
        public $email_verified;
        /** @var string Contains the Stripe customer ID */
        public $customer_id;
        /** @var string Contains the PayPal email */
        public $paypal;
        /** @var DateTime Contains the updated time */
        public $updated_at;

        /**
         * Creates Profile instance from array data
         *
         * @param array $data
         * @return Profile
         */
         public static function from(array $data) : Profile {
            $p = new User();
            $p->user_id = isset($data['sub']) ? $data['sub'] : '';
            $p->username = isset($data['user_metadata'][Vec2::USERNAME_META_KEY]) ? $data['user_metadata'][Vec2::USERNAME_META_KEY] : '';
            $p->first_name = isset($data['user_metadata'][Vec2::FIRSTNAME_META_KEY]) ? $data['user_metadata'][Vec2::FIRSTNAME_META_KEY] : '';
            $p->last_name = isset($data['user_metadata'][Vec2::LASTNAME_META_KEY]) ? $data['user_metadata'][Vec2::LASTNAME_META_KEY] : '';
            $p->email = isset($data['email']) ? $data['email'] : '';
            $p->email_verified = isset($data['email_verified']) ? (intval($data['email_verified'])==1) : 0;
            $p->customer_id = isset($data['user_metadata']['customer_id']) ? $data['user_metadata']['customer_id'] : '';
            $p->paypal = isset($data['user_metadata']['paypal']) ? $data['user_metadata']['paypal'] : '';
            $p->created_at = new DateTime($data['db']['created_at']);
            return $p;
        }

        /**
         * Returns an array to be serialized by JSON ext
         *
         * @return array
         */
        public function jsonSerialize() {
            return [
                'user_id' => $this->user_id,
                'username' => $this->username,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'email_verified' => $this->email_verified,
                'customer_id' => $this->customer_id,
                'paypal' => $this->paypal,
                'created_at' => $this->created_at->format('Y-m-d H:i:s')
            ];
        }
    }