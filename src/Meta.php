<?php

namespace artabramov\Echidna;

class Meta
{
    private $db;
    private $data;

    // create the object
    public function __construct( \Illuminate\Database\Capsule\Manager $db ) {
        $this->db = $db;
        $this->clear();
    }

    // clear data
    public function clear() {
        $this->data = [
            'id'         => 0,
            'date'       => '0000-00-00 00:00:00',
            'user_id'    => 0,
            'meta_key'   => '',
            'meta_value' => ''
        ];
    }

    // set the data
    public function __set( string $key, $value ) {
        if( array_key_exists( $key, $this->data ) ) {
            $this->data[ $key ] = $value;
        }
    }

    // get the data
    public function __get( string $key ) {
        if( array_key_exists( $key, $this->data ) ) {
            return $this->data[ $key ];
        }
        return null;
    }

    // check data is not empty
    public function is_empty( string $key ): bool {
        if( $key == 'date' ) {
            return $this->data[$key] == '0000-00-00 00:00:00';
        }
        return empty( $this->data[ $key ] );
    }

    // data validation
    public function is_correct( string $key ) : bool {

        if ( $key == 'id' and is_int( $this->data['id'] ) and $this->data['id'] > 0 and ceil( log10( $this->data['id'] )) <= 20 ) {
            return true;

        } elseif ( $key == 'user_id' and is_int( $this->data['user_id'] ) and $this->data['user_id'] > 0 and ceil( log10( $this->data['user_id'] )) <= 20 ) {
            return true;

        } elseif ( $key == 'meta_key' and is_string( $this->data['meta_key'] ) and mb_strlen( $this->data['meta_key'], 'utf-8' ) <= 40 and preg_match("/^[a-z0-9_-]/", $this->data['meta_key'] ) ) {
            return true;

        } elseif ( $key == 'meta_value' and is_string( $this->data['meta_value'] ) and mb_strlen( $this->data['meta_value'], 'utf-8' ) <= 255 ) {
            return true;
        }

        return false;
    }

    // check that meta exists
    public function is_exists( array $args ) : bool {

        $meta = $this->db
            ->table('user_meta')
            ->select('id');

        foreach( $args as $where ) {
            $meta = $meta->where( $where[0], $where[1], $where[2] );
        }

        $meta = $meta->first();
        return empty( $meta->id ) ? false : true;
    }

    // insert a new usermeta
    public function insert() : bool {

        $this->data['id'] = $this->db
        ->table('user_meta')
        ->insertGetId([
            'date'        => $this->db::raw('now()'),
            'user_id'     => $this->data['user_id'],
            'meta_key'    => $this->data['meta_key'],
            'meta_value'  => $this->data['meta_value']
        ]);

        return empty( $this->data['id'] ) ? false : true;
    }

    // update
    public function update() : bool {

        $affected_rows = $this->db
            ->table('user_meta')
            ->where([ ['user_id', '=', $this->data['user_id']], [ 'meta_key', '=', $this->data['meta_key']] ])
            ->update([ 'meta_value'  => $this->data['meta_value']]);

        return $affected_rows > 0 ? true : false;
    }

    // select usermeta by user_id and meta_key
    public function select() : bool {

        $meta = $this->db
            ->table( 'user_meta' )
            ->where([[ 'user_id', '=', $this->data['user_id'] ], [ 'meta_key', '=', $this->data['meta_key'] ]])
            ->select( '*' )
            ->first();

        if( !empty( $meta->id )) {
            $this->data['id'] = $meta->id;
            $this->data['date'] = $meta->date;
            $this->data['user_id'] = $meta->user_id;
            $this->data['meta_key'] = $meta->meta_key;
            $this->data['meta_value'] = $meta->meta_value;
        }

        return empty( $meta->id ) ? false : true;
    }


    // delete usermeta by user_id and meta_key
    public function delete() : bool {

        $affected_rows = $this->db
            ->table('user_meta')
            ->where([ ['user_id', '=', $theis->data['user_id']], [ 'meta_key', '=', $this->data['meta_key'] ] ])
            ->delete();

        // TODO: check deleting
        return $affected_rows > 0 ? true : false;
    }

}
