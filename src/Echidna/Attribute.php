<?php
namespace artabramov\Echidna\Echidna;

class Attribute extends \artabramov\Echidna\Echidna
{
    protected $error;
    protected $id;
    protected $date;
    protected $user_id;
    protected $attribute_key;
    protected $attribute_value;

    /**
     * Insert an attribute for the user.
     * @param int $user_id
     * @param string $attribute_key
     * @param int|string $attribute_value
     * @return bool
     */
    public function set( int $user_id, string $attribute_key, int|string $attribute_value ) : bool {

        if( $this->is_empty( $user_id )) {
            $this->error = 'user_id is empty';

        } elseif( !$this->is_id( $user_id )) {
            $this->error = 'user_id is incorrect';

        } elseif( $this->is_empty( $attribute_key )) {
            $this->error = 'attribute_key is empty';

        } elseif( !$this->is_key( $attribute_key )) {
            $this->error = 'attribute_key is incorrect';

        } elseif( $this->is_empty( $attribute_value )) {
            $this->error = 'attribute_value is empty';

        } elseif( !$this->is_value( $attribute_value )) {
            $this->error = 'attribute_value is incorrect';

        } elseif( $this->is_exists( 'user_attributes', [['user_id', '=', $user_id], ['attribute_key', '=', $attribute_key]] )) {
            $this->error = 'attribute is occupied';

        } else {
            $data = [
                'user_id'         => $user_id,
                'attribute_key'   => $attribute_key,
                'attribute_value' => $attribute_value
            ];

            if( !$this->insert( 'user_attributes', $data )) {
                $this->error = 'attribute insert error';
            }
        }

        return empty( $this->error );
    }

    /**
     * Update the attribure of the user.
     * @param int $user_id
     * @param string $attribute_key
     * @param int|string $attribute_value
     * @return bool
     */
    public function put( int $user_id, string $attribute_key, int|string $attribute_value ) : bool {

        if( $this->is_empty( $user_id )) {
            $this->error = 'user_id is empty';

        } elseif( !$this->is_id( $user_id )) {
            $this->error = 'user_id is incorrect';

        } elseif( $this->is_empty( $attribute_key )) {
            $this->error = 'attribute_key is empty';

        } elseif( !$this->is_key( $attribute_key )) {
            $this->error = 'attribute_key is incorrect';

        } elseif( $this->is_empty( $attribute_value )) {
            $this->error = 'attribute_value is empty';

        } elseif( !$this->is_value( $attribute_value )) {
            $this->error = 'attribute_value is incorrect';

        } elseif( !$this->is_exists( 'user_attributes', [['user_id', '=', $user_id], ['attribute_key', '=', $attribute_key]] )) {
            $this->error = 'attribute not found';

        } else {
            $args = [ ['user_id', '=', $user_id], ['attribute_key', '=', $attribute_key] ];
            $data = [ 'attribute_value' => $attribute_value ];

            if( !$this->update( 'user_attributes', $args, $data )) {
                $this->error = 'attribute update error';
            }
        }

        return empty( $this->error );
    }

    /**
     * Delete attribute of the user.
     */
    public function unset( int $user_id, string $attribute_key ) : bool {
    }

    /**
     * Select attribute of the user.
     */
    public function get( int $user_id, string $attribute_key ) : array|bool {
    }

    /**
     * Select all attributes of the user.
     */
    public function get_all( int $user_id ) : arra {
    }

}
