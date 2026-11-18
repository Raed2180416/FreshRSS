<?php
declare(strict_types=1);

final class EntryDAOTest extends \PHPUnit\Framework\TestCase {
	private Minz_PdoSqlite $pdo;

	private FreshRSS_EntryDAOSQLite $entryDAO;

	#[\Override]
	protected function setUp(): void {
		$this->pdo = new Minz_PdoSqlite('sqlite::memory:');
		$this->pdo->exec('CREATE TABLE feed (id INTEGER PRIMARY KEY, cache_nbUnreads INTEGER DEFAULT 0)');
		$this->pdo->exec('CREATE TABLE entry (id INTEGER PRIMARY KEY, id_feed INTEGER, is_read INTEGER DEFAULT 0,
			lastUserModified INTEGER DEFAULT 0)');
		$this->pdo->exec('INSERT INTO feed (id, cache_nbUnreads) VALUES (1, 0)');
		$this->pdo->exec('INSERT INTO entry (id, id_feed, is_read) VALUES (1, 1, 1), (2, 1, 1)');
		$this->entryDAO = new FreshRSS_EntryDAOSQLite(currentPdo: $this->pdo);
	}

	public function test_markReadFeed_decrementsTheUnreadCache(): void {
		$this->pdo->exec('UPDATE entry SET is_read=0');
		$this->pdo->exec('UPDATE feed SET cache_nbUnreads=2');

		self::assertSame(2, $this->entryDAO->markReadFeed(1, '999999999'));
		self::assertSame(0, $this->unreadEntries());
		self::assertSame(0, $this->cachedUnreads());
	}

	public function test_markReadFeed_incrementsTheUnreadCacheWhenMarkingUnread(): void {
		self::assertSame(2, $this->entryDAO->markReadFeed(1, '999999999', is_read: false));
		self::assertSame(2, $this->unreadEntries());
		self::assertSame(2, $this->cachedUnreads());
	}

	private function cachedUnreads(): int {
		return $this->entryDAO->fetchInt('SELECT `cache_nbUnreads` FROM `_feed` WHERE id=1') ?? -1;
	}

	private function unreadEntries(): int {
		return $this->entryDAO->fetchInt('SELECT COUNT(*) AS nbUnreads FROM `_entry` WHERE is_read=0') ?? -1;
	}
}
