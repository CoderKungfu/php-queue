<?php
namespace PHPQueue\Interfaces;

/**
 * Implemented by backends that provide "at least once" consumption
 */
interface AtomicReadBuffer
{
    /**
     * Pop and process data in an atomic way, so that the message will not be
     * consumed in case of failure.
     *
     * @param callable $callback A processing function with the signature,
     *     void function( $message ) throws Exception
     *         This function accepts an array $message, the next message popped
     *     from your buffer.  In normal operation, the message is popped after
     *     the function returns successfully, which gives us the guarantee that
     *     each message is consumed successfully "at least once".  The
     *     processor callback might either handle a message and consume it,
     *     reject and divert to a dead-letter queue, or raise an exception. The
     *     contract is that a clean function return means that the callback has
     *     completed its purpose and the message can be dropped without further
     *     processing.
     *         Throwing an exception from callback means that we were unable or
     *     chose not to handle the message at all, and it should be considered
     *     unconsumed.  In this case it is not popped from the queue when
     *     popAtomic returns.
     *         If there are no messages in the queue, the callback is not run.
     *
     * @return array|null popAtomic returns the currently popped record as a
     *     courtesy.  Note that any atomic processing should happen within
     *     $callback.  I'm not sure when it's valid to do anything with the
     *     return value.
     *     Or, returns null if the queue is empty over the backend poll interval.
     *
     * @throws \Exception When the processor dies in an unexpected way.  Most
     *     callbacks should handle rejected messages internally and not throw
     *     an error.  A message which causes the processor to error repeatedly
     *     causes "queue jam", something we alert about loudly and should
     *     eventually shunt these messages into a reject stream.
     */
    public function popAtomic($callback);
}
