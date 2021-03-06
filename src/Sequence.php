<?php
namespace artabramov\Echidna;

class Sequence
{
    protected $error;
    protected $repository;
    protected $rows;

    public function __construct( $repository ) {
        $this->error = '';
        $this->repository = $repository;
        $this->rows = [];
    }

    public function __isset( $key ) {
        if( property_exists( $this, $key )) {
            return !empty( $this->$key );
        }
        return false;
    }

    public function __get( $key ) {
        if( property_exists( $this, $key )) {
            return $this->$key;
        }
    }

    public function select( array $columns, string $table, array $kwargs, array $args = [] ) : \artabramov\Echidna\Query {
        return $this->repository->select( $columns, $table, $kwargs, $args );
    }

    // return result rows 
    public function execute( $query, $entity ) {

        $this->error = '';
        $this->rows = [];

        $this->repository->execute( $query );
        $rows = $this->repository->rows();

        foreach( $rows as $row ) {

            $instance = clone $entity;
            $reflection = new \ReflectionClass( $instance );
            $properties = $reflection->getProperties();

            foreach( $properties as $property ) {
                $property_name = $property->name;

                if( property_exists( $row, $property_name ) ) {
                    $property->setAccessible( true );
                    $property->setValue( $instance, $row->$property_name );
                }
            }
            array_push( $this->rows, $instance );
        }
    }

    // count rows in table
    public function count( string $table, array $kwargs ) {
        $query = $this->repository->select( ['COUNT(id) AS count'], $table, $kwargs, [] );
        $this->repository->execute( $query );
        $rows = $this->repository->rows();
        return (int) $rows[0]->count;
    }

}
