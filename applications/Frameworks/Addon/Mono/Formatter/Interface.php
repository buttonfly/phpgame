<?php
interface Mono_Formatter_Interface
{
    /**
     * Formats a log record.
     *
     * @param array $record A record to format
     * @return mixed The formatted record
     */
    function format(array $record);
}
