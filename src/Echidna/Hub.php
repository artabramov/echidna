<?php

namespace artabramov\Echidna\Echidna;

/**
 * All posts should be inside one of the hubs. 
 * The Hub class is responsible for working with hubs.
 */
class Hub extends \artabramov\Echidna\Echidna
{
    /**
     * Insert a new hub.
     * @param int $user_id
     * @param string $hub status
     * @param string $hub_name
     * @return bool
     */
    public function setup( int $user_id, string $hub_status, string $hub_name ) : bool {

        if( $this->is_empty( $user_id )) {
            $this->error = 'user_id is empty';

        } elseif( !$this->is_id( $user_id )) {
            $this->error = 'user_id is incorrect';

        } elseif( $this->is_empty( $hub_status )) {
            $this->error = 'hub_status is empty';

        } elseif( !$this->is_key( $hub_status )) {
            $this->error = 'hub_status is incorrect';

        } elseif( $this->is_empty( $hub_name )) {
            $this->error = 'hub_name is empty';

        } elseif( !$this->is_value( $hub_name )) {
            $this->error = 'hub_name is incorrect';

        } elseif( $this->is_exists( 'hubs', [['user_id', '=', $user_id], ['hub_name', '=', $hub_name]] )) {
            $this->error = 'hub_name is occupied';

        } else {

            $data = [
                'user_id'    => $user_id,
                'hub_status' => $hub_status,
                'hub_name'   => $hub_name
            ];

            if( !$this->insert( 'hubs', $data )) {
                $this->error = 'hub insert error';
            }
        }

        return empty( $this->error );
    }

    /**
     * Rename the hub (update the hub_name).
     * @param int $hub_id
     * @param string $hub_name
     * @return bool
     */
    public function rename( int $hub_id, string $hub_name ) : bool {

        if( $this->is_empty( $hub_id )) {
            $this->error = 'hub_id is empty';

        } elseif( !$this->is_id( $hub_id )) {
            $this->error = 'hub_id is incorrect';

        } elseif( $this->is_empty( $hub_name )) {
            $this->error = 'hub_name is empty';

        } elseif( !$this->is_value( $hub_name )) {
            $this->error = 'hub_name is incorrect';

        } elseif( !$this->is_exists( 'hubs', [['id', '=', $hub_id], ['hub_status', '<>', 'trash']] )) {
            $this->error = 'hub not found';

        } else {
            $args = [ ['id', '=', $hub_id] ];
            $data = [ 'hub_name' => $hub_name ];

            if( !$this->update( 'hubs', $args, $data )) {
                $this->error = 'hub rename error';
            }
        }

        return empty( $this->error );
    }

    /**
     * Trash the hub (update hub_status from public to trash).
     * @param int $hub_id
     * @return bool
     */
    public function trash( int $hub_id ) : bool {

        if( $this->is_empty( $hub_id )) {
            $this->error = 'hub_id is empty';

        } elseif( !$this->is_id( $hub_id )) {
            $this->error = 'hub_id is incorrect';

        } elseif( !$this->is_exists( 'hubs', [['id', '=', $hub_id], ['hub_status', '=', 'public']] )) {
            $this->error = 'hub not found';

        } else {
            $args = [ ['id', '=', $hub_id] ];
            $data = [ 'hub_status' => 'trash' ];

            if( !$this->update( 'hubs', $args, $data )) {
                $this->error = 'hub trash error';
            }
        }

        return empty( $this->error );
    }





    public function recover() : bool {}

    public function remove() : bool {}

    public function one() : bool {}

    public function some() : bool {}

}
