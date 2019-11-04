<?php

namespace abc\core\lib\contracts;

/**
 * Interface AuditLogWriterInterface
 *
 * @package abc\core\lib\contracts
 */
interface AuditLogStorageInterface
{

    public function __construct();

    /**
     * Method for write Audit log data to storage (DB, ElasticSearch, etc)
     *
     * @param array $data
     *
     * @return mixed
     */
    public function write(array $data);

    /**
     * Method for get Audit log events from storage (DB, ElasticSearch, etc)
     * @param array $request
     *
     * @return mixed
     */
    public function getEvents(array $request);

    /**
     * * Method for get Audit log event description from storage (DB, ElasticSearch, etc)
     * @param array $request
     *
     * @return mixed
     */
    public function getEventDetail(array $request);
}