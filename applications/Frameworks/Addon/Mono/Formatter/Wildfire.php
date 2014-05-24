<?php
class Mono_Formatter_Wildfire extends Mono_Formatter_Line {
    /**
     * Similar to LineFormatter::SIMPLE_FORMAT, except without the "[%datetime%]"
     */
    const SIMPLE_FORMAT = "%message% %context%";

    public function __construct($format = null, $dateFormat = null)
    {
        $this->format = $format ? $format : self::SIMPLE_FORMAT;
        $this->dateFormat = $dateFormat ? $dateFormat : self::SIMPLE_DATE;
    }

    /**
     * Translates Monolog log levels to Wildfire levels.
     */
    private $logLevels = array(
            Mono_Logger::DEBUG    => 'LOG',
            Mono_Logger::INFO     => 'INFO',
            Mono_Logger::WARNING  => 'WARN',
            Mono_Logger::ERROR    => 'ERROR',
            Mono_Logger::CRITICAL => 'ERROR',
            Mono_Logger::ALERT    => 'ERROR',
            Mono_Logger::DATA     => 'INFO',
    );

    /**
     * {@inheritdoc}
    */
    public function format(array $record)
    {
        // Retrieve the line and file if set and remove them from the formatted extra
        $file = $line = '';
        if (isset($record['context']['file'])) {
            $file = $record['context']['file'];
            unset($record['context']['file']);
        }
        if (isset($record['context']['line'])) {
            $line = $record['context']['line'];
            unset($record['context']['line']);
        }

        // Format record according with LineFormatter
        $message = parent::format($record);

        // Create JSON object describing the appearance of the message in the console
        $json = json_encode(array(
                array(
                        'Type'  => $this->logLevels[$record['level']],
                        'File'  => $file,
                        'Line'  => $line,
                        'Label' => $record['channel'],
                ),
                $message,
        ));

        // The message itself is a serialization of the above JSON object + it's length
        return sprintf(
                '%s|%s|',
                strlen($json),
                $json
        );
    }
}
