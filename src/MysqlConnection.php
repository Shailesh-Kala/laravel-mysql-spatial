<?php

namespace Grimzy\LaravelMysqlSpatial;

use Doctrine\DBAL\Types\Type as DoctrineType;
use Grimzy\LaravelMysqlSpatial\Schema\Builder;
use Grimzy\LaravelMysqlSpatial\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\MySqlConnection as IlluminateMySqlConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class MysqlConnection extends IlluminateMySqlConnection
{
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        if (class_exists(DoctrineType::class)) {
            // Prevent geometry type fields from throwing a 'type not found' error when changing them
            $geometries = [
                'geometry',
                'point',
                'linestring',
                'polygon',
                'multipoint',
                'multilinestring',
                'multipolygon',
                'geometrycollection',
                'geomcollection',
            ];

            // $dbPlatform = $this->getDoctrineSchemaManager()->getDatabasePlatform();
            // foreach ($geometries as $type) {
            //     $dbPlatform->registerDoctrineTypeMapping($type, 'string');
            // }

            $connectionParams = [
                'dbname' => env('DB_DATABASE'),
                'user' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'host' => env('DB_HOST'),
                'driver' => 'pdo_mysql',
            ];
            
            try {
                // Establish a standalone Doctrine connection
                $conn = DriverManager::getConnection($connectionParams);
            
                // Access the platform and register custom type mappings
                $dbPlatform = $conn->getDatabasePlatform();
                foreach ($geometries as $type) {
                    if (!$dbPlatform->hasDoctrineTypeMappingFor($type)) {
                        $dbPlatform->registerDoctrineTypeMapping($type, 'string');
                    }
                }
            } catch (Exception $e) {
                logger()->error("Failed to register Doctrine type mappings: " . $e->getMessage());
            }
        }
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new MySqlGrammar());
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new Builder($this);
    }
}
