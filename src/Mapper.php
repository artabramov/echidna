<?php
namespace artabramov\Echidna;

class Mapper
{
    protected $error;
    protected $repository;
    //protected $query;
    //protected $rows;

    public function __construct( $repository ) {
        $this->error = '';
        $this->repository = $repository;
        //$this->query = new \stdClass;
        //$this->rows = [];
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

    /**
     * Entity doc format: @entity(table=users alias=user)
     * @return string
     */
    private function get_entity_params( \ReflectionClass $entity_class ) : array {
        $doc = $entity_class->getDocComment();
        return $this->parse_params( $doc, 'entity' );
    }

    /**
     * Param doc format: @column(nullable=true unique=true regex=/^[a-z]{1,20}$/)
     * @return string
     */
    private function get_property_params( $entity, string $column ) : array {
        $class = new \ReflectionClass( $entity );
        $property = $class->getProperty( $column );
        $doc = $property->getDocComment();
        return $this->parse_params( $doc, 'column' );
    }

    /**
     * @return array
     */
    private function parse_params( string $doc, string $key ) : array {

        preg_match_all( '#@' . $key . '\((.*?)\)\n#s', $doc, $tmp );
        preg_match_all( '/\s*([^=]+)=(\S+)\s*/', !empty($tmp[1][0]) ? $tmp[1][0] : '', $tmp );
        return array_combine ( $tmp[1], $tmp[2] );
    }

    /**
     * @return bool
     */
    public function insert( $entity, array $data ) : bool {

        $this->error = '';
        $entity_class = new \ReflectionClass( $entity );
        $entity_params = $this->get_entity_params( $entity_class );

        foreach( $data as $key => $value ) {
            $property = $entity_class->getProperty( $key );
            $property->setAccessible( true );
            $property_params = $this->get_property_params( $entity, $key );

            if( $property_params[ 'nullable' ] != 'true' and empty( $value )) {
                $this->error = $key . ' is empty';
                break;

            } elseif( !empty( $value ) and !preg_match( $property_params[ 'regex' ], $value ) ) {
                $this->error = $key . ' is incorrect';
                break;

            } elseif( !empty( $value ) and  $property_params[ 'unique' ] == 'true' and $this->exists( $entity, [[ $key, '=', $value ]] ) ) {
                $this->error = $key . ' is occupied';
                break;
            }
        }
        
        if( empty( $this->error )) {
            $query = $this->repository->insert( $entity_params['table'], $data );
            $result = $this->repository->execute( $query );

            $a = 1;
        }

        /*
        if( empty( $this->error )) {
            //$data['id'] = $this->repository->insert( $entity_params['table'], $data );

            $query = $this->repository->insert( $entity_params['table'], $data );

            if( $this->repository->execute( $query ) ) {
            //if( !empty( $data['id'] )) {

                foreach( $data as $key => $value ) {
                    $property = $entity_class->getProperty( $key );
                    $property->setAccessible( true );
                    $property->setValue( $entity, $value );
                }

            } else {
                $this->error = $entity_params['alias'] . ' insert error';
            }
        }
        */

        return empty( $this->error );
    }

    /**
     * @return bool
     */
    public function update( $entity, array $data ) : bool {

        $this->error = '';
        $entity_class = new \ReflectionClass( $entity );
        $entity_params = $this->get_entity_params( $entity_class );

        foreach( $data as $key => $value ) {
            $property = $entity_class->getProperty( $key );
            $property->setAccessible( true );
            $property_params = $this->get_property_params( $entity, $key );

            if( $property_params[ 'nullable' ] != 'true' and empty( $value )) {
                $this->error = $key . ' is empty';
                break;

            } elseif( !empty( $value )) {

                if( !preg_match( $property_params[ 'regex' ], $value ) ) {
                    $this->error = $key . ' is incorrect';
                    break;

                } elseif( $property_params[ 'unique' ] == 'true' and $this->exists( $entity, [[ $key, '=', $value ]] ) ) {
                    $this->error = $key . ' is occupied';
                    break;
                }
            }
        }

        if( empty( $this->error )) {

            if( $this->repository->update( $entity_params['table'], [['id', '=', $entity->id]], $data )) {

                foreach( $data as $key => $value ) {
                    $property = $entity_class->getProperty( $key );
                    $property->setAccessible( true );
                    $property->setValue( $entity, $value );
                }

            } else {
                $this->error = $entity_params['alias'] . ' update error';
            }
        }

        return empty( $this->error );
    }

    /**
     * @return bool
     */
    public function delete( $entity ) : bool {
        $this->error = '';

        $class = new \ReflectionClass( $entity );
        $params = $this->get_entity_params( $class );

        if( $this->repository->delete( $params['table'], [['id', '=', $entity->id]] )) {
            $properties = $class->getProperties();

            foreach( $properties as $property ) {
                $property->setAccessible( true );
                $property->setValue( $entity, null );
            }

        } else {
            $this->error = $params['alias'] . ' delete error';
        }

        return empty( $this->error );
    }





    /**
     *
     */
    public function select( $entity, array $kwargs ) {
        $this->error = '';
        $class = new \ReflectionClass( $entity );
        $params = $this->get_entity_params( $class );
        $query = $this->repository->select( ['*'], $params['table'], $kwargs, ['LIMIT 1', 'OFFSET 0'] );
        $result = $this->repository->execute( $query );

        if( !empty( $this->repository->rows )) {
            foreach( $this->repository->rows[0] as $key=>$value ) {

                $property = $class->getProperty( $key );
                $property->setAccessible( true );
                $property->setValue( $entity, $this->repository->rows[0]->$key );
            }

        } else {
            $this->error = $params['alias'] . ' not found';
        }
        return empty( $this->error );

        //$this->repository->execute();
        //$rows = $this->repository->rows;

        /*
        $rows = $this->repository->select( ['*'], $params['table'], $args, ['LIMIT' => 1, 'OFFSET' => 0] );
        if( !empty( $rows )) {
            foreach( $rows[0] as $key=>$value ) {

                $property = $class->getProperty( $key );
                $property->setAccessible( true );
                $property->setValue( $entity, $rows[0]->$key );
            }

        } else {
            $this->error = $params['alias'] . ' not found';
        }
        return empty( $this->error );
        */
    }


    /**
     * @return bool
     */
    public function exists( $entity, array $args ) : bool {

        $class = new \ReflectionClass( $entity );
        $params = $this->get_entity_params( $class );
        $query = $this->repository->select( ['id'], $params['table'], $args, ['LIMIT 1', 'OFFSET 0'] );
        $this->repository->execute( $query );
        return !empty( $this->repository->rows[0]->id );
    }

}
