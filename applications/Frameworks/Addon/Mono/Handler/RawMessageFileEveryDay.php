<?php
class Mono_Handler_RawMessageFileEveryDay extends Mono_Handler_Abstract {
    public $basePath = null;
    protected $stream;

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter() {
        return new Mono_Formatter_RawMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record) {
        if (null === $this->stream) {
            if ( ! $this->basePath )
                $this->basePath = Mono_Logger::GetDefaultLogsRootPath();
            if( ! $this->basePath )
                throw new LogicException('Missing stream base path, the stream can not be opened. This may be caused by a premature call to close().');

            //using time and channel to split the fold
            $dir = $this->basePath.'/'.$record['channel'].'/';
            if(!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }

            $filename = date('Ymd');
            $this->stream = @fopen($dir . $filename . ".log", 'a');

            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new UnexpectedValueException(sprintf('The stream or file "%s" could not be opened; it may be invalid or not writable.', $this->basePath));
            }
        }
        fwrite($this->stream, (string) $record['formatted']);
    }
}
