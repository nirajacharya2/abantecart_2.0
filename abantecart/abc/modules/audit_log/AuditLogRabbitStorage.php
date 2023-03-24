<?php

namespace abc\modules\audit_log;

use abc\core\ABC;
use abc\core\engine\Registry;
use abc\core\lib\AException;
use abc\core\lib\ALog;
use abc\core\lib\contracts\AuditLogStorageInterface;
use AuditLog\AuditLogClient;
use AuditLog\AuditLogConfig;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use RuntimeException;

/**
 * Class AuditLogRabbitStorage
 *
 * @package abc\modules\audit_log
 */
class AuditLogRabbitStorage implements AuditLogStorageInterface
{

    /**
     * @var ALog $log
     */
    private $log;

    protected $conf;
    /**
     * @var AMQPStreamConnection $conn
     */
    protected $conn;
    /**
     * @var AMQPChannel $channel
     */
    protected $channel;


    /**
     * AuditLogRabbitStorage constructor.
     */
    public function __construct()
    {
        $this->log = Registry::log();
        $this->connect();
    }

    protected function connect()
    {
        $this->conf = ABC::env('RABBIT_MQ');
        $params = [
            'host'     => $this->conf['HOST'],
            'port'     => $this->conf['PORT'],
            'user'     => $this->conf['USER'],
            'password' => $this->conf['PASSWORD'],
        ];
        if (isset($this->conf['PROTOCOL']) && strtolower($this->conf['PROTOCOL']) === 'amqps') {
            $params['ssl_options'] = [
                'dsn' => 'amqps:'
            ];
            $this->conn = new AMQPSSLConnection(...$params);
        } else {
            $this->conn = new AMQPStreamConnection(...$params);
        }
        $this->channel = $this->conn->channel();
        $this->channel->exchange_declare('exch_main', 'direct', false, true, false);
        $this->channel->exchange_declare('exch_backup', 'fanout', false, true, false);
        $this->channel->queue_declare(
            'audit_log',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => 'exch_backup',
                'x-message-ttl'          => 15000,
            ])
        );
        $this->channel->queue_declare('audit_log_backup', false, true, false, false, false, new AMQPTable([]));
        $this->channel->queue_bind('audit_log', 'exch_main');
        $this->channel->queue_bind('audit_log_backup', 'exch_backup');
    }

    public function disconnect()
    {
        $this->channel->close();
        $this->conn->close();
    }

    /**
     * Method for write Audit log data to storage (DB, ElasticSearch, etc)
     *
     * @param array $data
     *
     * @return void
     * @throws Exception
     *
     */
    public function write(array $data)
    {
        $domain = ABC::env('AUDIT_LOG_API')['DOMAIN'];
        $data = [
            'data' => $data,
            'domain' => $domain ?: 'audit-log-index',
        ];

        try {
            if (!$this->conn->isConnected() || !$this->channel->is_open()) {
                $this->connect();
            }
            $msg = new AMQPMessage(json_encode($data));
            $this->channel->basic_publish($msg, '', $this->conf['QUEUE']);
        } catch (Exception $exception) {
            if (!file_exists(ABC::env('DIR_SYSTEM') . 'rabbitmq')) {
                if (!mkdir($concurrentDirectory = ABC::env('DIR_SYSTEM') . 'rabbitmq', 0775, true)
                    && !is_dir($concurrentDirectory)
                ) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }
            $this->log->error($exception->getMessage());
            $backupFile = ABC::env('DIR_SYSTEM') . 'rabbitmq/rabbit_data.bak';
            file_put_contents($backupFile, json_encode($data) . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function getEventsRaw(array $request)
    {
        $api = ABC::env('AUDIT_LOG_API');
        $conf = new AuditLogConfig($api['HOST']);
        $client = new AuditLogClient($conf);
        try {
            $request = $this->prepareRequest($request);
            $events = $client->getEvents($api['DOMAIN'], $request);
            return [
                'items' => $events['events'],
                'total' => $events['total'],
            ];
        } catch (Exception $exception) {
            $this->log->error($exception->getMessage());
        }
        return [];
    }

    /**
     * @param array $request
     *
     * @return array
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    public function getEvents(array $request)
    {
        $result = $this->getEventsRaw($request);
        if (is_array($result)) {
            $result['items'] = $this->prepareEvents($result['items']);
        }
        return $result;
    }

    /**
     * @param $request
     *
     * @return array
     */
    protected function prepareRequest($request)
    {
        $allowSortBy = [
            'date_added' => 'request.timestamp',
            'event' => 'entity.group',
            'main_auditable_id' => 'entity.id',
            'main_auditable_model' => 'entity.name',
            'user_name' => 'actor.name',
        ];
        $filter = [];
        if (is_array($request['filter'])) {
            foreach ($request['filter'] as $item) {
                if (is_string($item)) {
                    $decodedItem = json_decode($item, true);
                    if ($decodedItem) {
                        $item = $decodedItem;
                    }
                }
                if (isset($request['user_name']) && !empty($request['user_name'])) {
                    $item['actor.name'] = $request['user_name'];
                }
                if (isset($request['events']) && !empty($request['events'])) {
                    $item['entity.group'] = $request['events'];
                    foreach ($item['entity.group'] as &$value) {
                        $value = strtolower($value);
                    }
                }
                foreach ($item as $key => $val) {
                    if (empty($val) && !is_array($val)) {
                        unset($item[$key]);
                    }
                }
                $item = json_encode($item);
                $item = str_replace('auditable_type', 'entity.name', $item);
                $item = str_replace('field_name', 'changes.name', $item);
                $item = str_replace('auditable_id', 'entity.id', $item);
                $filter[] = $item;
            }
        } else {
            $item = [];
            if (isset($request['user_name']) && !empty($request['user_name'])) {
                $item['actor.name'] = $request['user_name'];
            }
            if (isset($request['events']) && !empty($request['events'])) {
                $item['entity.group'] = $request['events'];
                foreach ($item['entity.group'] as &$value) {
                    $value = strtolower($value);
                }
            }
            $item = json_encode($item);
            $filter[] = $item;
        }

        if (is_array($request['sortBy'])) {
            $request['sortBy'] = $request['sortBy'][0];
        }
        if (is_array($request['sortDesc'])) {
            $request['sortDesc'] = $request['sortDesc'][0];
        }

        $result = [
            'limit'  => (int)$request['rowsPerPage'],
            'offset' => ((int)$request['rowsPerPage'] * (int)$request['page'] - (int)$request['rowsPerPage']) > 0
                ? (int)$request['rowsPerPage'] * (int)$request['page'] - (int)$request['rowsPerPage']
                : 0,
            'sort'   => $allowSortBy[$request['sortBy']] ?: '',
            'order'  => $request['sortDesc'] == 'true' ? 'DESC' : 'ASC',
        ];
        if (!empty($request['date_from'])) {
            $result['dateFrom'] = $request['date_from'];
        }
        if (!empty($request['date_to'])) {
            $result['dateTo'] = $request['date_to'];
        }
        if (is_array($filter) && !empty($filter)) {
            $result['filter'] = implode('||', $filter);
        }
        return $result;
    }

    /**
     * @param $events
     *
     * @return array
     * @throws ReflectionException
     * @throws AException|InvalidArgumentException
     */
    protected function prepareEvents($events)
    {
        $result = [];
        foreach ($events as $event) {
            $result[] = [
                'id' => $event['_id'],
                'user_name' => $event['actor']['name'],
                'alias_name' => '',
                'main_auditable_model' => $event['entity']['name'],
                'main_auditable_id' => $event['entity']['id'],
                'description' => $event['description'],
                'ip' => $event['request']['ip'],
                'event' => $event['entity']['group'],
                'date_added' => date(
                    Registry::language()->get('date_format_long'),
                    strtotime($event['request']['timestamp'])
                ),
            ];
        }
        return $result;
    }

    /**
     * * Method for get Audit log event description from storage (DB, ElasticSearch, etc)
     *
     * @param array $request
     *
     * @return array
     */
    public function getEventDetail(array $request)
    {
        $api = ABC::env('AUDIT_LOG_API');
        $conf = new AuditLogConfig($api['HOST']);
        $client = new AuditLogClient($conf);
        $filter = json_decode($request['filter'], true);
        try {
            $event = $client->getEventById($api['DOMAIN'], $filter['audit_event_id']);
            return [
                'items' => $this->prepareEventDescriptionRows($event['events']),
                'total' => $event['total'],
            ];
        } catch (Exception $exception) {
            $this->log->error($exception->getMessage());
        }
        return [];
    }

    /**
     * @param $events
     *
     * @return array
     */
    protected function prepareEventDescriptionRows($events)
    {
        $result = [];

        foreach ($events as $event) {
            foreach ($event['changes'] as $change) {
                $result[] = [
                    'auditable_model' => $change['groupName'],
                    'field_name' => $change['name'],
                    'old_value' => $change['oldValue'],
                    'new_value' => $change['newValue'],
                ];
            }
        }

        return $result;
    }
}