<?php
class Mono_Formatter_RawMessage implements Mono_Formatter_Interface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        return $record['message'];
    }
}
