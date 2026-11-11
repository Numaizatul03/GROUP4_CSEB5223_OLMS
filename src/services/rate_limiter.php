<?php

declare(strict_types=1);

/**
 * Class Name:  RateLimiter
 * Purpose:     Count attempts per key and block a key that tries too often.
 * Description: Used to stop password guessing on the login form and to stop
 *              one visitor flooding the API. Counts are kept in the
 *              rate_limits table, so they survive page reloads and new sessions.
 *              Each key has a time window; when the window ends, the count starts again.
 */
class RateLimiter
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: tooManyAttempts
     * Purpose:      Check whether a key has used up its attempts.
     * Parameters:   string $limiterKey - e.g. "login:siti@olms.test|127.0.0.1"
     *               int $maxAttempts - attempts allowed in one window
     *               int $windowSeconds - length of the window
     * Return Value: bool - true when the key must wait
     * Description:  An expired window counts as zero attempts.
     */
    public function tooManyAttempts(string $limiterKey, int $maxAttempts, int $windowSeconds): bool
    {
        return $this->countAttempts($limiterKey, $windowSeconds) >= $maxAttempts;
    }

    /**
     * Function/Method Name: hit
     * Purpose:      Record one attempt for a key.
     * Parameters:   string $limiterKey, int $windowSeconds
     * Return Value: int - number of attempts in the current window, including this one
     * Description:  Starts a new window when there is none or the old one has ended.
     */
    public function hit(string $limiterKey, int $windowSeconds): int
    {
        $currentAttempts = $this->countAttempts($limiterKey, $windowSeconds);
        $currentTime = date('Y-m-d H:i:s');

        if ($currentAttempts === 0) {
            $this->clear($limiterKey);
            $statement = $this->connection->prepare(
                'INSERT INTO rate_limits (limiter_key, attempts, window_started_at) VALUES (:limiterKey, 1, :startedAt)'
            );
            $statement->execute(['limiterKey' => $this->shortenKey($limiterKey), 'startedAt' => $currentTime]);

            return 1;
        }

        $statement = $this->connection->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE limiter_key = :limiterKey');
        $statement->execute(['limiterKey' => $this->shortenKey($limiterKey)]);

        return $currentAttempts + 1;
    }

    /**
     * Function/Method Name: secondsUntilReset
     * Purpose:      Tell how long a blocked key must wait.
     * Parameters:   string $limiterKey, int $windowSeconds
     * Return Value: int - seconds until the window ends (0 when not blocked)
     * Description:  Shown to the user ("try again in 14 minutes") and sent as Retry-After.
     */
    public function secondsUntilReset(string $limiterKey, int $windowSeconds): int
    {
        $windowStartedAt = $this->findWindowStart($limiterKey);
        if ($windowStartedAt === null) {
            return 0;
        }

        return max(0, $windowStartedAt + $windowSeconds - time());
    }

    public function clear(string $limiterKey): void
    {
        $statement = $this->connection->prepare('DELETE FROM rate_limits WHERE limiter_key = :limiterKey');
        $statement->execute(['limiterKey' => $this->shortenKey($limiterKey)]);
    }

    private function countAttempts(string $limiterKey, int $windowSeconds): int
    {
        $statement = $this->connection->prepare('SELECT attempts, window_started_at FROM rate_limits WHERE limiter_key = :limiterKey');
        $statement->execute(['limiterKey' => $this->shortenKey($limiterKey)]);
        $limitRow = $statement->fetch();

        if ($limitRow === false || strtotime((string) $limitRow['window_started_at']) + $windowSeconds <= time()) {
            return 0;
        }

        return (int) $limitRow['attempts'];
    }

    private function findWindowStart(string $limiterKey): ?int
    {
        $statement = $this->connection->prepare('SELECT window_started_at FROM rate_limits WHERE limiter_key = :limiterKey');
        $statement->execute(['limiterKey' => $this->shortenKey($limiterKey)]);
        $windowStartedAt = $statement->fetchColumn();

        return $windowStartedAt === false ? null : (int) strtotime((string) $windowStartedAt);
    }

    /**
     * Function/Method Name: shortenKey
     * Purpose:      Keep keys within the column length.
     * Parameters:   string $limiterKey - may contain a long email address
     * Return Value: string - the key itself, or "hash:" + SHA-256 for long keys
     * Description:  A very long email typed on the login form cannot break the table.
     */
    private function shortenKey(string $limiterKey): string
    {
        return strlen($limiterKey) <= 150 ? $limiterKey : 'hash:' . hash('sha256', $limiterKey);
    }
}
