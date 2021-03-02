<?php

namespace artabramov\Echidna;

class Role
{
    private $db;
    private $data;

    // create the object
    public function __construct( \Illuminate\Database\Capsule\Manager $db ) {
        $this->db    = $db;
        $this->clear();
    }

    // clear data
    public function clear() {
        $this->data  = [
            'id'        => 0,
            'date'      => '0000-00-00 00:00:00',
            'user_id'   => 0,
            'group_id'  => 0,
            'user_role' => ''
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

    // check is data has a value
    public function has( string $key ) : bool {
        if( !empty( $this->data[ $key ] ) ) {
            return true;
        }
        return false;
    }

    // data validation
    public function is_correct( string $key ) : bool {

        if ( $key == 'id' and is_int( $this->data['id'] ) and $this->data['id'] > 0 and ceil( log10( $this->data['id'] )) <= 20 ) {
            return true;

        } elseif ( $key == 'user_id' and is_int( $this->data['user_id'] ) and $this->data['user_id'] > 0 and ceil( log10( $this->data['user_id'] )) <= 20 ) {
            return true;

        } elseif ( $key == 'group_id' and is_int( $this->data['group_id'] ) and $this->data['group_id'] > 0 and ceil( log10( $this->data['group_id'] )) <= 20 ) {
            return true;

        } elseif ( $key == 'user_role' and is_string( $this->data['user_role'] ) and mb_strlen( $this->data['user_role'], 'utf-8' ) <= 40 and preg_match("/^[a-z0-9_-]/", $this->data['user_role'] ) ) {
            return true;
        }

        return false;
    }


    // check that the role exists
    public function is_exists( array $args ) : bool {

        $role = $this->db
            ->table('user_roles')
            ->select('id');

        foreach( $args as $where ) {
            $role = $role->where( $where[0], $where[1], $where[2] );
        }

        $role = $role->first();
        return empty( $role->id ) ? false : true;
    }

    // insert a new role
    public function insert() : bool {

        $this->data['id'] = $this->db
        ->table('user_roles')
        ->insertGetId([
            'date'      => $this->db::raw('now()'),
            'user_id'   => $this->data['user_id'],
            'group_id'  => $this->data['group_id'],
            'user_role' => $this->data['user_role']
        ]);

        return empty( $this->data['id'] ) ? false : true;
    }

    // update
    public function update() : bool {

        $affected_rows = $this->db
            ->table('user_roles')
            ->where([ 
                ['user_id', '=', $this->data['user_id']],
                ['group_id', '=', $this->data['group_id']] ])
            ->update([ 
                'user_role' => $this->data['user_role'] ]);

        return $affected_rows > 0 ? true : false;
    }

    // select the role
    public function select() : bool {

        $role = $this->db
            ->table( 'user_roles' )
            ->where([[ 'user_id', '=', $this->data['user_id'] ], [ 'group_id', '=', $this->data['group_id'] ]])
            ->select( '*' )
            ->first();

        if( !empty( $role->id )) {
            $this->data['id']        = $role->id;
            $this->data['date']      = $role->date;
            $this->data['user_id']   = $role->user_id;
            $this->data['group_id']  = $role->group_id;
            $this->data['user_role'] = $role->user_role;
        }

        return empty( $role->id ) ? false : true;
    }

    // delete
    public function delete() : bool {

        $affected_rows = $this->db
            ->table('user_roles')
            ->where([ 
                ['user_id', '=', $this->data['user_id']], 
                ['group_id', '=', $this->data['group_id']] ])
            ->delete();

        return $affected_rows > 0 ? true : false;
    }

    // count roles of the group
    public function count( array $args ) : int {

        $role = $this->db->table('user_roles');

        foreach( $args as $where ) {
            $role = $role->where( $where[0], $where[1], $where[2] );
        }

        $role = $role->count();
        return $role;
    }


}
