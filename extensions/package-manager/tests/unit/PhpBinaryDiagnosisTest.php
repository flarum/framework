<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Host\Capability;
use Flarum\ExtensionManager\Host\PhpBinary;
use Flarum\ExtensionManager\Host\PhpOverride;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The host tab must say WHICH of three faults stops Composer running, because
 * each has a different fix — and a real Plesk customer was stuck for want of
 * the difference: open_basedir hid /opt/plesk/php/8.5/bin/php from the website,
 * his SSH test of proc_open passed, and the Extension Manager said only that it could not
 * run.
 *
 * The host is simulated: open_basedir, the candidate list, PHP_BINARY and the
 * process runner are all injected, so a Plesk box can be asserted on a Mac.
 */
class PhpBinaryDiagnosisTest extends TestCase
{
    private const PLESK_FPM = '/opt/plesk/php/8.5/sbin/php-fpm';
    private const PLESK_CLI = '/opt/plesk/php/8.5/bin/php';
    private const PLESK_BASEDIR = '/var/www/vhosts/example.com/:/tmp/';

    /** @param array<string, array{code:int, out:string, err:string}> $answers */
    private function host(array $answers, ?string $override = null, string $basedir = self::PLESK_BASEDIR, string $disabled = '', ?array $candidates = null): PhpBinary
    {
        return new PhpBinary(
            $override,
            $candidates ?? [self::PLESK_CLI, '/usr/bin/php8.5', '/usr/bin/php'],
            $basedir,
            fn (string $bin) => $answers[$bin] ?? ['code' => 127, 'out' => '', 'err' => ''],
            self::PLESK_FPM,
            'fpm-fcgi',
            $disabled,
        );
    }

    private function phpRow(PhpBinary $php): ?array
    {
        foreach ((new Capability(sys_get_temp_dir(), $php))->report()['checks'] as $check) {
            if ($check['id'] === 'php') {
                return $check;
            }
        }

        return null;
    }

    public function test_a_path_open_basedir_hides_is_still_tried_by_running_it(): void
    {
        // is_file() on a hidden path is false whether or not it exists, so a
        // hidden candidate must be run, not looked at.
        $php = $this->host([self::PLESK_CLI => ['code' => 0, 'out' => 'cli 8.5.1', 'err' => '']]);

        $this->assertFalse($php->visible(self::PLESK_CLI));
        $this->assertSame(self::PLESK_CLI, $php->path());

        $row = $this->phpRow($php);
        $this->assertTrue($row['ok']);
        $this->assertSame('host.php_found', $row['whatKey']);
        $this->assertSame('8.5.1', $row['whatParams']['version']);
    }

    public function test_when_nothing_answers_it_names_open_basedir_the_path_and_the_plesk_fix(): void
    {
        $php = $this->host([]);

        $this->assertNull($php->path());
        $this->assertSame(PhpBinary::PANEL_PLESK, $php->panel());

        $row = $this->phpRow($php);
        // A warning, not a blocker: Composer runs in-process without it.
        $this->assertTrue($row['warn']);
        $this->assertSame('host.php_hidden', $row['whatKey']);
        $this->assertSame(self::PLESK_CLI, $row['whatParams']['path']);

        $keys = array_column($row['whyKeys'], 'key');
        $this->assertContains('host.php_hidden_fix_plesk', $keys);
        $this->assertContains('host.ssh_differs', $keys, 'it must say the SSH PHP is configured separately');
        $this->assertContains('host.php_override_hint', $keys);

        $fix = $row['whyKeys'][array_search('host.php_hidden_fix_plesk', $keys, true)];
        $this->assertSame('/opt/plesk/php/8.5/bin/', $fix['params']['dir']);
    }

    public function test_cpanel_gets_the_multiphp_fix(): void
    {
        $cli = '/opt/cpanel/ea-php85/root/usr/bin/php';
        $php = new PhpBinary(null, [$cli], '/home/me/:/tmp/', fn () => ['code' => 127, 'out' => '', 'err' => ''], '/opt/cpanel/ea-php85/root/usr/sbin/php-fpm', 'fpm-fcgi', '');

        $keys = array_column($this->phpRow($php)['whyKeys'], 'key');
        $this->assertContains('host.php_hidden_fix_cpanel', $keys);
    }

    public function test_a_cli_php_that_fails_to_run_shows_its_error(): void
    {
        // Inside open_basedir, so it is checked with is_file: use a real file.
        $bin = tempnam(sys_get_temp_dir(), 'php');
        chmod($bin, 0755);

        try {
            $php = new PhpBinary(null, [$bin], null, fn () => ['code' => 255, 'out' => '', 'err' => "PHP Fatal error: Unable to load dynamic library 'ioncube'\nmore"], self::PLESK_FPM, 'fpm-fcgi', '');

            $row = $this->phpRow($php);
            $this->assertSame('host.php_fails', $row['whatKey']);
            $this->assertSame($bin, $row['whatParams']['path']);
            $this->assertStringContainsString('ioncube', $row['whyKeys'][0]['params']['error']);
            $this->assertStringNotContainsString('more', $row['whyKeys'][0]['params']['error'], 'first line only');
        } finally {
            @unlink($bin);
        }
    }

    public function test_the_fpm_binary_is_not_mistaken_for_the_cli(): void
    {
        $php = $this->host([self::PLESK_CLI => ['code' => 0, 'out' => 'fpm-fcgi 8.5.1', 'err' => '']]);

        $this->assertNull($php->path());
        $this->assertSame('host.php_fails', $this->phpRow($php)['whatKey']);
    }

    public function test_proc_open_disabled_is_its_own_fault_and_names_the_ini(): void
    {
        $php = $this->host([], null, self::PLESK_BASEDIR, 'exec, proc_open,system');

        $report = (new Capability(sys_get_temp_dir(), $php))->report();
        $rows = array_column($report['checks'], null, 'id');

        // No longer a blocker: Composer runs inside the request instead, and
        // the row says so, with the fix that would move it back out.
        $this->assertTrue($rows['subprocess']['ok']);
        $this->assertTrue($rows['subprocess']['warn']);
        $this->assertSame('host.in_process', $rows['subprocess']['whatKey']);
        $keys = array_column($rows['subprocess']['whyKeys'], 'key');
        $this->assertNotEmpty(array_intersect(['host.in_process_fix_ini', 'host.in_process_fix'], $keys), 'it names the fix');
        $this->assertContains('host.ssh_differs', $keys);
        $this->assertArrayNotHasKey('php', $rows, 'the CLI PHP row would only repeat the same blocker');
    }

    public function test_no_open_basedir_and_no_php_says_not_installed(): void
    {
        $php = $this->host([], null, '', '', ['/nonexistent/php-for-test']);

        $this->assertSame('host.php_missing', $this->phpRow($php)['whatKey']);
        $this->assertSame(
            'host.summary_in_process',
            (new Capability(sys_get_temp_dir(), $php, null, ['memory' => true, 'time' => true, 'timeLimit' => 30]))->report()['summaryKey']
        );
    }

    public function test_open_basedir_is_a_prefix_match_like_php_itself(): void
    {
        $php = $this->host([], null, '/var/www/vhosts/:/tmp/');

        $this->assertTrue($php->visible('/var/www/vhosts/example.com/httpdocs/flarum'));
        $this->assertFalse($php->visible('/opt/plesk/php/8.5/bin/php'));
        $this->assertTrue($this->host([], null, '')->visible('/anything'));
    }

    public function test_a_broken_override_is_reported_and_a_working_detection_is_offered(): void
    {
        $php = $this->host([self::PLESK_CLI => ['code' => 0, 'out' => 'cli 8.5.1', 'err' => '']], '/opt/plesk/php/8.3/bin/php');

        $row = $this->phpRow($php);
        $this->assertTrue($row['warn']);
        $keys = array_column($row['whyKeys'], 'key');
        $this->assertContains('host.php_override_broken', $keys);
        $this->assertContains('host.php_override_detected', $keys);
    }

    public function test_an_override_is_refused_unless_it_runs_as_the_cli(): void
    {
        $override = new PhpOverride(sys_get_temp_dir().'/mw-php-'.uniqid());

        $this->assertSame('php_path_not_absolute', $override->refusal('php', $this->host([]))[0]);
        $this->assertSame('php_path_not_cli', $override->refusal(self::PLESK_CLI, $this->host([self::PLESK_CLI => ['code' => 0, 'out' => 'fpm-fcgi 8.5.1', 'err' => '']]))[0]);
        $this->assertSame('php_path_not_cli', $override->refusal('/opt/plesk/php/8.5/sbin/php-fpm', $this->host(['/opt/plesk/php/8.5/sbin/php-fpm' => ['code' => 64, 'out' => 'Usage: php-fpm [-n] [-e] [-h]', 'err' => '']]))[0], 'php-fpm has no -r and prints its usage');
        $this->assertSame('php_path_hidden_and_silent', $override->refusal('/opt/plesk/php/9.9/bin/php', $this->host([]))[0]);
        $this->assertNull($override->refusal(self::PLESK_CLI, $this->host([self::PLESK_CLI => ['code' => 0, 'out' => 'cli 8.5.1', 'err' => '']])));
        $this->assertSame('php_path_missing', $override->refusal('/nonexistent/php', $this->host([], null, ''))[0]);
    }

    public function test_an_override_survives_a_round_trip_and_clears(): void
    {
        $dir = sys_get_temp_dir().'/mw-php-'.uniqid();
        $override = new PhpOverride($dir);

        $override->set(self::PLESK_CLI);
        $this->assertSame(self::PLESK_CLI, $override->get());
        $this->assertSame(self::PLESK_CLI, PhpBinary::forStorage($dir)->path());

        $override->set(null);
        $this->assertNull($override->get());
    }

    public function test_every_key_the_host_tab_can_send_is_translated(): void
    {
        $locale = Yaml::parseFile(__DIR__.'/../../locale/en.yml')['flarum-extension-manager']['admin'];
        $source = file_get_contents(__DIR__.'/../../src/Host/Capability.php').file_get_contents(__DIR__.'/../../src/Host/PhpOverride.php');

        preg_match_all("/'(host\.[a-z_]+|php_path_[a-z_]+)'/", $source, $m);
        $this->assertNotEmpty($m[1]);

        foreach (array_unique($m[1]) as $key) {
            $node = $locale;
            foreach (explode('.', $key) as $part) {
                $this->assertIsArray($node, $key);
                $this->assertArrayHasKey($part, $node, "missing translation: $key");
                $node = $node[$part];
            }
        }
    }
}
