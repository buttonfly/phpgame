<?php
interface Mono_Handler_Interface
{
    /**
     * Checks whether the given record will be handled by this handler.
     *
     * This is mostly done for performance reasons, to avoid calling processors for nothing.
     *
     * @return Boolean
     */
    function isHandling(array $record);

    /**
     * Handles a record.
     *
     * The return value of this function controls the bubbling process of the handler stack.
     *
     * @param array $record The record to handle
     * @return Boolean True means that this handler handled the record, and that bubbling is not permitted.
     *                 False means the record was either not processed or that this handler allows bubbling.
    */
    function handle(array $record);

    /**
     * Handles a set of records at once.
     *
     * @param array $records The records to handle (an array of record arrays)
    */
    function handleBatch(array $records);

    /**
     * Adds a processor in the stack.
     *
     * @param callable $callback
    */
    function pushProcessor($callback);

    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @return callable
    */
    function popProcessor();

    /**
     * Sets the formatter.
     *
     * @param FormatterInterface $formatter
    */
    function setFormatter(Mono_Formatter_Interface $formatter);

    /**
     * Gets the formatter.
     *
     * @return FormatterInterface
    */
    function getFormatter();
}
