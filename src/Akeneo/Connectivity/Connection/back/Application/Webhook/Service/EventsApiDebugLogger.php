<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Application\Webhook\Service;

use Akeneo\Connectivity\Connection\Domain\Clock;
use Akeneo\Connectivity\Connection\Domain\Webhook\EventFormatter\EventFormatter;
use Akeneo\Connectivity\Connection\Domain\Webhook\Persistence\Repository\EventsApiDebugRepository;
use Akeneo\Platform\Component\EventQueue\EventInterface;

/**
 * @copyright 2021 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class EventsApiDebugLogger
{
    const LEVEL_NOTICE = 'notice';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    private int $bufferSize;

    /**
     * @var array<array{
     *  timestamp: int,
     *  level: self::LEVEL_*,
     *  message: string,
     *  connection_code: ?string,
     *  context: array
     * }>
     */
    private array $buffer;

    private Clock $clock;

    private EventsApiDebugRepository $repository;

    /**
     * @var iterable<EventFormatter>
     */
    private iterable $eventFormatters;

    public function __construct(
        EventsApiDebugRepository $repository,
        Clock $clock,
        iterable $eventFormatters,
        int $bufferSize = 100
    ) {
        $this->repository = $repository;
        $this->clock = $clock;
        $this->eventFormatters = $eventFormatters;
        $this->bufferSize = $bufferSize;
        $this->buffer = [];
    }

    /**
     * @param EventInterface[] $events
     */
    public function logEventSubscriptionSkippedOwnEvents(
        string $connectionCode,
        array $events
    ): void {
        $this->addLog([
            'timestamp' => $this->clock->now()->getTimestamp(),
            'level' => self::LEVEL_NOTICE,
            'message' => 'The event was not sent because it was raised by the same connection',
            'connection_code' => $connectionCode,
            'context' => [
                'events' => $this->formatEvents($events)
            ]
        ]);
    }

    public function logLimitOfEventsApiRequestsReached(): void
    {
        $this->addLog([
            'timestamp' => $this->clock->now()->getTimestamp(),
            'level' => self::LEVEL_WARNING,
            'message' => 'The maximum number of events sent per hour has been reached.',
            'connection_code' => null,
            'context' => [],
        ]);
    }

    public function flushLogs(): void
    {
        if (0 === count($this->buffer)) {
            return;
        }

        $this->repository->bulkInsert($this->buffer);
        $this->buffer = [];
    }

    /**
     * @param array{
     *  timestamp: int,
     *  level: self::LEVEL_*,
     *  message: string,
     *  connection_code: ?string,
     *  context: array
     * } $log
     */
    private function addLog(array $log): void
    {
        $this->buffer[] = $log;

        if (count($this->buffer) >= $this->bufferSize) {
            $this->flushLogs();
        }
    }

    /**
     * @param EventInterface[] $events
     */
    private function formatEvents(array $events)
    {
        return \array_map(function (EventInterface $event) {
            foreach ($this->eventFormatters as $formatter) {
                if (true === $formatter->supports($event)) {
                    return $formatter->format($event);
                }

                throw new \RuntimeException(
                    sprintf('No event formatter declared for %s', get_class($event))
                );
            }
        }, $events);
    }
}
