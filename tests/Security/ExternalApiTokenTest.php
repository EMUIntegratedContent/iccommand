<?php

namespace App\Tests\Security;

use App\Security\ExternalApiToken;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * The shared secret that guards the emich.edu server-to-server write routes (#142, #159).
 * Pure unit test: no kernel, no DB.
 */
class ExternalApiTokenTest extends TestCase
{
    private const SECRET = 's3cret-token';

    private array $logs = [];

    public function testValidTokenIsAllowedInBothModesWithoutLogging(): void
    {
        foreach (['log', 'enforce'] as $mode) {
            $token = $this->token(self::SECRET, $mode);

            $this->assertTrue($token->isValid($this->request(self::SECRET)));
            $this->assertTrue($token->allows($this->request(self::SECRET), true));
            $this->assertTrue($token->allows($this->request(self::SECRET), false));
        }
        $this->assertSame([], $this->logs);
    }

    public function testLogModeAcceptsMissingOrWrongTokenAndWarns(): void
    {
        $token = $this->token(self::SECRET, 'log');

        $this->assertTrue($token->allows($this->request(null), true));
        $this->assertTrue($token->allows($this->request('wrong'), true));

        $this->assertCount(2, $this->logs);
        $this->assertSame('warning', $this->logs[0]['level']);
        $this->assertStringContainsString('accepted without a valid API token', $this->logs[0]['message']);
        $this->assertFalse($this->logs[0]['context']['header_present']);
        $this->assertTrue($this->logs[1]['context']['header_present']);
    }

    public function testLogModeStillRejectsRequestsThatNeededTheToken(): void
    {
        $token = $this->token(self::SECRET, 'log');

        $this->assertFalse($token->allows($this->request(null), false));
        $this->assertFalse($token->allows($this->request('wrong'), false));
    }

    public function testEnforceModeRejectsMissingOrWrongTokenAndWarns(): void
    {
        $token = $this->token(self::SECRET, 'enforce');

        $this->assertFalse($token->allows($this->request(null), true));
        $this->assertFalse($token->allows($this->request('wrong'), true));

        $this->assertCount(2, $this->logs);
        $this->assertStringContainsString('rejected', $this->logs[0]['message']);
        $this->assertSame('/api/redirects/external/redirect', $this->logs[0]['context']['path']);
    }

    public function testEmptyConfiguredTokenNeverMatches(): void
    {
        $token = $this->token('', 'enforce');

        $this->assertFalse($token->isValid($this->request('')));
        $this->assertFalse($token->allows($this->request(''), true));
    }

    public function testModeIsCaseAndWhitespaceInsensitive(): void
    {
        $this->assertTrue($this->token(self::SECRET, ' Enforce ')->isEnforced());
        $this->assertFalse($this->token(self::SECRET, 'log')->isEnforced());
        $this->assertFalse($this->token(self::SECRET, '')->isEnforced());
    }

    private function token(string $secret, string $mode): ExternalApiToken
    {
        $logs = &$this->logs;
        $logger = new class($logs) extends AbstractLogger {
            public function __construct(private array &$logs)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        return new ExternalApiToken($secret, $mode, $logger);
    }

    private function request(?string $apiKey): Request
    {
        $request = Request::create('/api/redirects/external/redirect', 'PUT');
        if ($apiKey !== null) {
            $request->headers->set(ExternalApiToken::HEADER, $apiKey);
        }

        return $request;
    }
}
