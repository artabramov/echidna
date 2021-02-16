<?php

namespace artabramov\Echidna;

class User
{
    private $db;
    private $error;
    private $id;
    private $date;
    private $status;
    private $user_token;
    private $user_email;
    private $user_hash;
    private $meta;


    public function __construct( \Illuminate\Database\Capsule\Manager $db ) {
        $this->db         = $db;
        $this->error      = '';
        $this->id         = 0;
        $this->date       = '0000-00-00 00:00:00';
        $this->status     = '';
        $this->user_token = '';
        $this->user_email = '';
        $this->user_hash  = '';
        $this->meta       = [];
    }


    public function __set( string $key, $value ) {
        if( isset( $this->$key )) {
            $this->$key = $value;
        } else {
            $this->meta[ $key ] = $value;
        }
    }


    public function __get( string $key ) {
        if( isset( $this->$key )) {
            return $this->$key;
        } elseif( isset( $this->usermeta[ $key ] )) {
            return $this->usermeta[ $key ];
        } else {
            return null;
        }
    }


    public function create_token() {
        do {
            $user_token = sha1( bin2hex( random_bytes( 64 )));
            if( $this->is_exists( [['user_token', '=', $user_token]] )) {
                $repeat = true;
            } else {
                $repeat = false;
            }
        } while( $repeat );
        return $user_token;
    }


    private function is_exists( array $args ) {
        $user = $this->db->table('users')->select('id');
        foreach( $args as $where ) {
            $user = $user->where( $where[0], $where[1], $where[2] );
        }
        $user = $user->first();
        return !empty( $user->id );
    }


    public function create_pass() {
        $user_pass = '';
        for( $i = 0; $i < 6; $i++ ) {
            $user_pass .= mt_rand( 0,9 );
        }
        return $user_pass;
    }


    public function get_hash( $value ) {
        return sha1( $value );
    }


    public function create_user() {

        $this->error = '';

        if( empty( $this->user_email )) {
            $this->error = 'User email is empty';

        } elseif( mb_strlen( $this->user_email, 'utf-8' ) > 255 ) {
            $this->error = 'User email is is too long';

        } elseif( !preg_match("/^[a-z0-9._-]{1,80}@(([a-z0-9-]+\.)+(com|net|org|mil|"."edu|gov|arpa|info|biz|inc|name|[a-z]{2})|[0-9]{1,3}\.[0-9]{1,3}\.[0-"."9]{1,3}\.[0-9]{1,3})$/", $this->user_email )) {
            $this->error = 'User email is incorrect';

        } elseif( $this->is_exists( [['user_email', '=', $this->user_email]] )) {
            $this->error = 'User email is occupied';

        } else {
            $this->id = $this->db->table('users')->insertGetId([
                'date'       => $this->db::raw('now()'),
                'status'     => $this->status,
                'user_token' => $this->user_token,
                'user_email' => trim( strtolower( $this->user_email )),
                'user_hash'  => $this->user_hash
            ]);

            if( empty( $this->id ) ) {
                $this->error = 'User insertion error';
            }
        }
    }
}