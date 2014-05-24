<?php
class Mono_Handler_MongoDB extends Mono_Handler_Abstract {
    public $dsn;
    public $dbname;
    public $collectionName = 'logs';
    protected $mongo;

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if($this->mongo) {
            $this->mongo->close();
        }
        $this->dsn = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (null === $this->mongo) {
            if ( ! $this->dsn ) {
                throw new LogicException('Missing mongo dsn, the mongodb can not be opened. This may be caused by a premature call to close().');
            }
            $this->mongo = new Mongo($this->dsn);
        }
        $coll_name = isset($record['context']['collection']) ? $record['context']['collection'] : $this->collectionName;
        $mongodb = $this->mongo->selectDB($this->dbname);
        $coll = $mongodb->selectCollection($coll_name);
        $coll->insert($record);
    }
}
