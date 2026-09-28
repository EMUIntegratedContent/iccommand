<?php

namespace App\Service;

use App\Entity\CrimeLog\CrimeLog;
use App\Entity\CrimeLog\FireLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Imports the Daily Crime Log CSV exported by DPS.
 *
 * The whole file is parsed and validated before anything is written. If any row is invalid,
 * nothing changes. Otherwise the daily log is replaced and the fire log rows are upserted
 * in one transaction, so the public log is never left empty or half-written.
 */
class CrimeLogImporter
{
	public const COLUMNS = [
		'Incident Number', 'Crime', 'Crime Description', 'Att', 'Arson', 'Report Date', 'Report Time',
		'Occur From', 'Occur To', 'Status', 'Closed', 'Last Approval', 'Location', 'Subject',
	];

	/** Crime codes that also go in the fire log. */
	public const FIRE_CODES = ['L5170', '2005', '2009', '2072', '2073', '2099'];

	/**
	 * Accepted Report Date formats, keyed by the exact shape they must match
	 * (createFromFormat alone would read "9/23/26" as the year 26).
	 * "!" resets unparsed fields so the current time doesn't leak in.
	 */
	private const DATE_FORMATS = [
		'#^\d{1,2}/\d{1,2}/\d{4}$#' => '!n/j/Y',
		'#^\d{1,2}/\d{1,2}/\d{2}$#' => '!n/j/y',
		'#^\d{4}-\d{2}-\d{2}$#' => '!Y-m-d',
	];

	private const BATCH_SIZE = 100;

	private EntityManagerInterface $em;

	public function __construct(ManagerRegistry $doctrine, private ValidatorInterface $validator)
	{
		$this->em = $doctrine->getManager('dps');
	}

	/**
	 * Parse and validate the CSV. Nothing is written.
	 *
	 * @return array{crimeLogs: list<CrimeLog>, fireLogs: list<FireLog>, errors: list<array{row: int, incidentNumber: ?string, errors: list<string>}>}
	 */
	public function parse(string $path): array
	{
		$lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
		if ($lines) {
			// Strip the UTF-8 byte-order mark Excel prepends, or the first header won't match.
			$lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
		}
		$headers = array_map('trim', str_getcsv((string) array_shift($lines), ',', '"', '\\'));

		$missing = array_diff(self::COLUMNS, $headers);
		if ($missing) {
			return ['crimeLogs' => [], 'fireLogs' => [], 'errors' => [[
				'row' => 1,
				'incidentNumber' => null,
				'errors' => ['Missing column(s): ' . implode(', ', $missing)],
			]]];
		}

		$crimeLogs = [];
		$fireLogs = [];
		$errors = [];

		foreach ($lines as $i => $line) {
			$rowNumber = $i + 2; // 1-based, after the header row
			if (trim($line) === '') {
				continue;
			}
			$cells = str_getcsv($line, ',', '"', '\\');
			if (count($cells) !== count($headers)) {
				$errors[] = ['row' => $rowNumber, 'incidentNumber' => null, 'errors' => [sprintf('Expected %d columns, found %d.', count($headers), count($cells))]];
				continue;
			}
			$data = array_map('trim', array_combine($headers, $cells));

			$rowErrors = [];
			$reportDate = $this->parseDate($data['Report Date']);
			if ($reportDate === null) {
				$rowErrors[] = sprintf('Report Date "%s" is not a valid date (expected M/D/YYYY).', $data['Report Date']);
			}

			$crimeLog = $this->fill(new CrimeLog(), $data, $reportDate ?? '');
			$crimeLog->setArson($data['Arson']);
			foreach ($this->validator->validate($crimeLog) as $violation) {
				$rowErrors[] = $violation->getMessage();
			}

			if ($rowErrors) {
				$errors[] = ['row' => $rowNumber, 'incidentNumber' => $data['Incident Number'] ?: null, 'errors' => $rowErrors];
				continue;
			}

			$crimeLogs[] = $crimeLog;
			if (in_array($data['Crime'], self::FIRE_CODES, true)) {
				// Keyed by incident number, so a repeated incident keeps its last row only.
				$fireLogs[$data['Incident Number']] = $this->fill(new FireLog(), $data, $reportDate);
			}
		}

		return ['crimeLogs' => $crimeLogs, 'fireLogs' => array_values($fireLogs), 'errors' => $errors];
	}

	/**
	 * Replace the daily log and upsert the fire logs in one transaction.
	 *
	 * @param list<CrimeLog> $crimeLogs
	 * @param list<FireLog>  $fireLogs
	 */
	public function replace(array $crimeLogs, array $fireLogs, ?callable $afterBatch = null): void
	{
		$this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($crimeLogs, $fireLogs, $afterBatch) {
			// DELETE (not TRUNCATE) so it rolls back if anything below fails.
			$em->createQuery('DELETE FROM ' . CrimeLog::class . ' c')->execute();

			$incidentNumbers = array_map(fn (FireLog $f) => $f->getIncidentNumber(), $fireLogs);
			foreach (array_chunk($incidentNumbers, 500) as $chunk) {
				$em->createQuery('DELETE FROM ' . FireLog::class . ' f WHERE f.crnnumber IN (:ids)')
					->setParameter('ids', $chunk)
					->execute();
			}

			$pending = 0;
			foreach ([...$crimeLogs, ...$fireLogs] as $log) {
				$em->persist($log);
				if (++$pending >= self::BATCH_SIZE) {
					$em->flush();
					$em->clear();
					$pending = 0;
					$afterBatch && $afterBatch();
				}
			}
			$em->flush();
			$em->clear();
		});
	}

	/**
	 * @return string|null the date as Y-m-d, or null if blank or invalid
	 */
	public function parseDate(string $value): ?string
	{
		if ($value === '') {
			return null;
		}
		foreach (self::DATE_FORMATS as $pattern => $format) {
			if (!preg_match($pattern, $value)) {
				continue;
			}
			$date = \DateTimeImmutable::createFromFormat($format, $value);
			// Reject overflow like 2/30/2026, which PHP would otherwise roll into March.
			if ($date !== false && \DateTimeImmutable::getLastErrors() === false) {
				return $date->format('Y-m-d');
			}
		}

		return null;
	}

	private function fill(CrimeLog|FireLog $log, array $data, string $reportDate): CrimeLog|FireLog
	{
		return $log
			->setIncidentNumber($data['Incident Number'])
			->setCrime($data['Crime'])
			->setCrimeDescription($data['Crime Description'])
			->setAtt($data['Att'])
			->setReportDate($reportDate)
			->setReportTime($data['Report Time'])
			->setOccurFrom($data['Occur From'])
			->setOccurTo($data['Occur To'])
			->setStatus($data['Status'])
			->setClosed($data['Closed'])
			->setLastApproval($data['Last Approval'])
			->setLocation($data['Location'])
			->setSubject($data['Subject']);
	}
}
