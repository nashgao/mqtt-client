<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\History;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\History\MessageHistory;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for MessageHistory.
 *
 * Tests message history buffer operations including retrieval and search.
 *
 * @internal
 */
#[CoversNothing]
class MessageHistoryTest extends AbstractTestCase
{
    private MessageHistory $history;

    protected function setUp(): void
    {
        parent::setUp();
        $this->history = new MessageHistory(100);
    }

    private function createMessage(string $topic, string $payload): Message
    {
        return new Message(
            type: 'publish',
            payload: ['topic' => $topic, 'message' => $payload],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['topic' => $topic]
        );
    }

    /**
     * SPECIFICATION: getLatest() should return the most recent message.
     */
    public function testGetLatestReturnsLastMessage(): void
    {
        $msg1 = $this->createMessage('topic/1', 'first');
        $msg2 = $this->createMessage('topic/2', 'second');
        $msg3 = $this->createMessage('topic/3', 'third');

        $this->history->add($msg1);
        $this->history->add($msg2);
        $this->history->add($msg3);

        $latest = $this->history->getLatest();

        $this->assertNotNull($latest);
        $this->assertEquals('third', $latest->payload['message']);
        $this->assertEquals('topic/3', $latest->payload['topic']);
    }

    /**
     * SPECIFICATION: getLatest() should return null when history is empty.
     */
    public function testGetLatestReturnsNullWhenEmpty(): void
    {
        $this->assertNull($this->history->getLatest());
    }

    /**
     * SPECIFICATION: getLatestId() should return the index of the most recent message.
     */
    public function testGetLatestIdReturnsCorrectIndex(): void
    {
        $this->history->add($this->createMessage('topic/1', 'first'));
        $this->history->add($this->createMessage('topic/2', 'second'));

        $latestId = $this->history->getLatestId();

        $this->assertNotNull($latestId);
        $this->assertEquals(1, $latestId);
    }

    /**
     * SPECIFICATION: getLatestId() should return null when history is empty.
     */
    public function testGetLatestIdReturnsNullWhenEmpty(): void
    {
        $this->assertNull($this->history->getLatestId());
    }

    /**
     * SPECIFICATION: get() should return message by its ID.
     */
    public function testGetByIdReturnsCorrectMessage(): void
    {
        $msg1 = $this->createMessage('topic/1', 'first');
        $msg2 = $this->createMessage('topic/2', 'second');

        $this->history->add($msg1);
        $this->history->add($msg2);

        $retrieved = $this->history->get(0);

        $this->assertNotNull($retrieved);
        $this->assertEquals('first', $retrieved->payload['message']);
    }

    /**
     * SPECIFICATION: get() should return null for invalid ID.
     */
    public function testGetByIdReturnsNullForInvalidId(): void
    {
        $this->history->add($this->createMessage('topic/1', 'first'));

        $this->assertNull($this->history->get(999));
        $this->assertNull($this->history->get(-1));
    }

    /**
     * SPECIFICATION: count() should return number of messages in history.
     */
    public function testCountReturnsCorrectNumber(): void
    {
        $this->assertEquals(0, $this->history->count());

        $this->history->add($this->createMessage('topic/1', 'first'));
        $this->assertEquals(1, $this->history->count());

        $this->history->add($this->createMessage('topic/2', 'second'));
        $this->assertEquals(2, $this->history->count());
    }

    /**
     * SPECIFICATION: History should respect max message limit (circular buffer).
     */
    public function testCircularBufferEvictsOldMessages(): void
    {
        $smallHistory = new MessageHistory(3);

        $smallHistory->add($this->createMessage('topic/1', 'first'));
        $smallHistory->add($this->createMessage('topic/2', 'second'));
        $smallHistory->add($this->createMessage('topic/3', 'third'));
        $smallHistory->add($this->createMessage('topic/4', 'fourth'));

        $this->assertEquals(3, $smallHistory->count());

        // First message should be evicted
        $latest = $smallHistory->getLatest();
        $this->assertNotNull($latest);
        $this->assertEquals('fourth', $latest->payload['message']);
    }

    /**
     * SPECIFICATION: getLast() should return the N most recent messages.
     */
    public function testGetLastReturnsRecentMessages(): void
    {
        $this->history->add($this->createMessage('topic/1', 'first'));
        $this->history->add($this->createMessage('topic/2', 'second'));
        $this->history->add($this->createMessage('topic/3', 'third'));

        $last2 = $this->history->getLast(2);

        $this->assertCount(2, $last2);
        $this->assertEquals('second', $last2[0]->payload['message']);
        $this->assertEquals('third', $last2[1]->payload['message']);
    }

    /**
     * SPECIFICATION: clear() should remove all messages from history.
     */
    public function testClearRemovesAllMessages(): void
    {
        $this->history->add($this->createMessage('topic/1', 'first'));
        $this->history->add($this->createMessage('topic/2', 'second'));

        $this->history->clear();

        $this->assertEquals(0, $this->history->count());
        $this->assertNull($this->history->getLatest());
    }
}
