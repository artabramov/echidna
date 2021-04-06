<?php
namespace artabramov\Echidna\Models;
use \artabramov\Echidna\Utilities\Filter;

/**
 * @implements Sequenceable
 */
class User extends \artabramov\Echidna\Models\Echidna implements \artabramov\Echidna\Interfaces\Sequenceable
{
    private $error;
    private $id;
    private $date;
    private $user_status;
    private $user_token;
    private $user_email;
    private $user_pass;
    private $user_hash;

    public function __get( string $key ) {
        if( property_exists( $this, $key )) {
            return $this->$key;
        }
        return false;
    }

    public function __isset( string $key ) {
        if( property_exists( $this, $key )) {
            return !empty( $this->$key );
        }
        return false;
    }

    public function clear() {
        $this->e = null;
        $this->error = null;
        $this->id = null;
        $this->date = null;
        $this->user_status = null;
        $this->user_token = null;
        $this->user_email = null;
        $this->user_pass = null;
        $this->user_hash = null;
    }
    
    /**
     * @return string
     */
    private function get_token() : string {

        do {
            $user_token = bin2hex( random_bytes( 40 ));

            if( $this->count( 'users', [['user_token', '=', $user_token]] ) > 0 ) {
                $repeat = true;
                
            } else {
                $repeat = false;
            }
        } while( $repeat );

        return $user_token;
    }

    /**
     * Get non-unique one-time password.
     * @param int $pass_len
     * @param string $pass_symbs
     * @return string
     */
    private function get_pass( string $symbols, int $length ) : string {

        $user_pass = '';
        $symbols_length = mb_strlen( $symbols, 'utf-8' ) - 1;

        for( $i = 0; $i < $length; $i++ ) {
            $user_pass .= $symbols[ random_int( 0, $symbols_length ) ];
        }

        return $user_pass;
    }

    /**
     * Get hash (sha1) of the password.
     * @param string $user_pass
     * @return string
     */
    private function get_hash( $user_pass ) : string {
        return sha1( $user_pass );
    }

    /**
     * @param mixed $user_email
     * @return bool
     */
    public function register( mixed $user_email ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_email )) {
            $this->error = 'user_email is empty';

        } elseif( !Filter::is_email( $user_email )) {
            $this->error = 'user_email is incorrect';

        } elseif( $this->count( 'users', [[ 'user_email', '=', $user_email ]] ) > 0 ) {
            $this->error = 'user_email is occupied';

        } else {
            $this->user_status = 'pending';
            $this->user_token = $this->get_token();
            $this->user_email = $user_email;
            $this->user_hash = '';

            $data = [
                'user_status' => $this->user_status,
                'user_token'  => $this->user_token,
                'user_email'  => $this->user_email,
                'user_hash'   => $this->user_hash,
            ];

            $this->id = $this->insert( 'users', $data );

            if( empty( $this->id )) {
                $this->clear();
                $this->error = 'user insert error';
            }            
        }

        return empty( $this->error );
    }

    /**
     * @param mixed $user_email
     * @param int $pass_len
     * @param string $pass_symbs
     * @return bool
     */
    public function restore( mixed $user_email, int $pass_length = 6, string $pass_symbols = '0123456789' ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_email )) {
            $this->error = 'user_email is empty';

        } elseif( !Filter::is_email( $user_email )) {
            $this->error = 'user_email is incorrect';

        } elseif( $this->count( 'users', [[ 'user_email', '=', $user_email ]] ) == 0 ) {
            $this->error = 'user not found';

        } else {
            $this->user_email = $user_email;
            $this->user_pass = $this->get_pass( $pass_symbols, $pass_length );
            $this->user_hash = $this->get_hash( $this->user_pass );

            $args = [[ 'user_email', '=', $this->user_email ]];
            $data = [ 'user_hash' => $this->user_hash ];

            if( !$this->update( 'users', $args, $data )) {
                $this->clear();
                $this->error = 'user update error';
            }
        }
        return empty( $this->error );
    }

    /**
     * @param mixed $user_email
     * @param mixed $user_pass
     * @return bool
     */
    public function signin( mixed $user_email, mixed $user_pass ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_email )) {
            $this->error = 'user_email is empty';

        } elseif( !Filter::is_email( $user_email )) {
            $this->error = 'user_email is incorrect';

        } elseif( Filter::is_empty( $user_pass )) {
            $this->error = 'user_pass is empty';

        } elseif( $this->count( 'users', [[ 'user_email', '=', $user_email ], [ 'user_hash', '=', $this->get_hash( $user_pass ) ], [ 'user_status', '<>', 'trash' ]] ) == 0 ) {
            //$a = $this->get_hash( $user_pass );
            $this->error = 'user not found';

        } else {
            $this->user_status = 'approved';
            $this->user_email = $user_email;
            $this->user_pass = $user_pass;
            $this->user_hash = '';

            $args = [[ 'user_email', '=', $this->user_email ]];
            $data = ['user_status' => $this->user_status, 'user_hash' => $this->user_hash ];

            if( !$this->update( 'users', $args, $data )) {
                $this->clear();
                $this->error = 'user update error';
            }
        }

        return empty( $this->error );
    }

    /**
     * @param mixed $user_id
     * @return bool
     */
    public function signout( mixed $user_id ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_id )) {
            $this->error = 'user_id is empty';

        } elseif( !Filter::is_int( $user_id )) {
            $this->error = 'user_id is incorrect';

        } elseif( $this->count( 'users', [[ 'id', '=', $user_id ], ['user_status', '=', 'approved']] ) == 0 ) {
            $this->error = 'user not found';
        
        } else {
            $this->id = $user_id;
            $this->user_token = $this->get_token();

            $args = [[ 'id', '=', $this->user_id ]];
            $data = [ 'user_token' => $this->user_token ];

            if( !$this->update( 'users', $args, $data )) {
                $this->clear();
                $this->error = 'user update error';
            }
        }

        return empty( $this->error );
    }

    /**
     * @param mixed $user_token
     * @return bool
     */
    public function auth( mixed $user_token ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_token )) {
            $this->error = 'user_token is empty';

        } elseif( !Filter::is_hex( $user_token, 80 )) {
            $this->error = 'user_token is incorrect';

        } elseif( $this->count( 'users', [[ 'user_token', '=', $user_token ], ['user_status', '=', 'approved']] ) == 0 ) {
            $this->error = 'user not found';

        } else {
            $this->user_token = $user_token;

            $args = [['user_token', '=', $this->user_token]];
            $rows = $this->select( '*', 'users', $args, 1, 0 );

            if( empty( $rows[0] )) {
                $this->clear();

            } else {
                $this->id          = $rows[0]['id'];
                $this->date        = $rows[0]['date'];
                $this->user_status = $rows[0]['user_status'];
                $this->user_token  = $rows[0]['user_token'];
                $this->user_email  = $rows[0]['user_email'];
                $this->user_hash   = $rows[0]['user_hash'];
            }
        }

        return empty( $this->error );
    }

    /**
     * This is a part of the Sequence interface. Get the element by id.
     * @param mixed $user_id
     * @return bool
     */
    public function getone( mixed $user_id ) : bool {

        $this->clear();

        if( Filter::is_empty( $user_id )) {
            $this->error = 'user_id is empty';

        } elseif( !Filter::is_int( $user_id )) {
            $this->error = 'user_id is incorrect';

        } elseif( $this->count( 'users', [[ 'id', '=', $user_id ]] ) == 0 ) {
            $this->error = 'user not found';

        } else {
            $this->id = $user_id;

            $args = [[ 'id', '=', $this->id ]];
            $rows = $this->select( '*', 'users', $args, 1, 0 );

            if( empty( $rows[0] )) {
                $this->clear();
                $this->error = 'user select error';

            } else {
                $this->id          = $rows[0]['id'];
                $this->date        = $rows[0]['date'];
                $this->user_status = $rows[0]['user_status'];
                $this->user_token  = $rows[0]['user_token'];
                $this->user_email  = $rows[0]['user_email'];
                $this->user_hash   = $rows[0]['user_hash'];
            }
        }

        return empty( $this->error );
    }

}
