<?php

namespace App\Entity\CrimeLog;

use App\Repository\CrimeLog\CrimeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A crimelog entry link.
 */

#[ORM\Entity(repositoryClass: CrimeLogRepository::class)]
#[ORM\Table(name: 'dailylog', schema: 'dps')]

class CrimeLog
{
	/* *************************** Member Variables *************************** */

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;
	/**
	 * The incident number.
	 */
	#[ORM\Column(type: "string", length: 20)]
	#[SerializedName("crnnumber")]
	#[Groups("crimelog")]
	#[Assert\NotBlank(message: "Incident number is required")]
	#[Assert\Length(max: 20, maxMessage: "Incident number cannot be longer than {{ limit }} characters")]
	private $crnnumber;

	/**
	 * The type of this crime.
	 */
	#[ORM\Column(type: "string", length: 10)]
	#[SerializedName("crime")]
	#[Groups("crimelog")]
	#[Assert\NotBlank(message: "Crime type is required")]
	#[Assert\Length(max: 10, maxMessage: "Crime type cannot be longer than {{ limit }} characters")]
	private $crime;

	/**
	 * The description of the crime.
	 */
	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("crimedesc")]
	#[Groups("crimelog")]
	#[Assert\NotBlank(message: "Crime description is required")]
	#[Assert\Length(max: 255, maxMessage: "Crime description cannot be longer than {{ limit }} characters")]
	private $crimedesc;

	/**
	 * The attention to for crime.
	 */
	#[ORM\Column(type: "string", length: 4)]
	#[SerializedName("att")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 4, maxMessage: "Att cannot be longer than {{ limit }} characters")]
	private $att;

	/**
	 * If the crime is arson.
	 */
	#[ORM\Column(type: "string", length: 5)]
	#[SerializedName("arson")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 5, maxMessage: "Arson field cannot be longer than {{ limit }} characters")]
	private $arson;

	/**
	 * The report date of the crime.
	 */
	#[ORM\Column(type: "string", length: 12)]
	#[SerializedName("reptdate")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 12, maxMessage: "Report date cannot be longer than {{ limit }} characters")]
	private $reptdate;

	/**
	 * The report time of the crime.
	 */
	#[ORM\Column(type: "string", length: 12)]
	#[SerializedName("repttime")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 12, maxMessage: "Report time cannot be longer than {{ limit }} characters")]
	private $repttime;

	/**
	 * The occur date of the crime.
	 */
	#[ORM\Column(type: "string", length: 20)]
	#[SerializedName("occurdate1")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 20, maxMessage: "Occur from date cannot be longer than {{ limit }} characters")]
	private $occurdate1;

	/**
	 * The occur end date of the crime.
	 */
	#[ORM\Column(type: "string", length: 20)]
	#[SerializedName("occurdate2")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 20, maxMessage: "Occur to date cannot be longer than {{ limit }} characters")]
	private $occurdate2;

	/**
	 * The status of the crime.
	 */
	#[ORM\Column(type: "string", length: 10)]
	#[SerializedName("status")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 10, maxMessage: "Status cannot be longer than {{ limit }} characters")]
	private $status;

	/**
	 * If crime is closed.
	 */
	#[ORM\Column(type: "string", length: 20)]
	#[SerializedName("closed")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 20, maxMessage: "Closed field cannot be longer than {{ limit }} characters")]
	private $closed;

	/**
	 * When last approved.
	 */
	#[ORM\Column(type: "string", length: 20)]
	#[SerializedName("lastupdate")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 20, maxMessage: "Last approval cannot be longer than {{ limit }} characters")]
	private $lastupdate;

	/**
	 * The location of the crime.
	 */
	#[ORM\Column(type: "string", length: 80)]
	#[SerializedName("location")]
	#[Groups("crimelog")]
	#[Assert\NotBlank(message: "Location is required")]
	#[Assert\Length(max: 80, maxMessage: "Location cannot be longer than {{ limit }} characters")]
	private $location;

	/**
	 * The subject of the crime.
	 */
	#[ORM\Column(type: "string", length: 255)]
	#[SerializedName("subject")]
	#[Groups("crimelog")]
	#[Assert\Length(max: 255, maxMessage: "Subject cannot be longer than {{ limit }} characters")]
	private $subject;

	/**
	 * The constructor of a crimelog.
	 */
	public function __construct()
	{
		// Constructor logic
	}

	/* ************************** Getters and Setters ************************* */


	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getIncidentNumber(): ?string
	{
		return $this->crnnumber;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getCrime(): ?string
	{
		return $this->crime;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getCrimeDescription(): ?string
	{
		return $this->crimedesc;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getAtt(): ?string
	{
		return $this->att;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getArson(): ?string
	{
		return $this->arson;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getReportDate(): ?string
	{
		return $this->reptdate;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getReportTime(): ?string
	{
		return $this->repttime;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getOccurFrom(): ?string
	{
		return $this->occurdate1;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getOccurTo(): ?string
	{
		return $this->occurdate2;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getStatus(): ?string
	{
		return $this->status;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getClosed(): ?string
	{
		return $this->closed;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getLastApproval(): ?string
	{
		return $this->lastupdate;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getLocation(): ?string
	{
		return $this->location;
	}

	/**
	 * Obtains the item type of this crimelog.
	 * @return string|null The item type of this crimelog.
	 */
	public function getSubject(): ?string
	{
		return $this->subject;
	}

	/**
	 * Changes the incident number of this crimelog.
	 * @param string $incidentNumber.
	 * @return CrimeLog This crimelog.
	 */
	public function setIncidentNumber(string $incidentNumber): self
	{
		$this->crnnumber = $incidentNumber;
		return $this;
	}

	/**
	 * Changes the crime designation of this crimelog.
	 * @param string $crime.
	 * @return CrimeLog This crimelog.
	 */
	public function setCrime(string $crime): self
	{
		$this->crime = $crime;
		return $this;
	}

	/**
	 * Changes the crime description of this crime.
	 * @param string $crimeDescription.
	 * @return CrimeLog This crime.
	 */
	public function setCrimeDescription(string $crimeDescription): self
	{
		$this->crimedesc = $crimeDescription;
		return $this;
	}

	/**
	 * Changes the attention of this crime.
	 * @param string $att.
	 * @return CrimeLog This crime.
	 */
	public function setAtt(string $att): self
	{
		$this->att = $att;
		return $this;
	}

	/**
	 * Changes the arson of this crime.
	 * @param string $arson.
	 * @return CrimeLog This crime.
	 */
	public function setArson(string $arson): self
	{
		$this->arson = $arson;
		return $this;
	}

	/**
	 * Changes the report date of this crime.
	 * @param string $reportDate.
	 * @return CrimeLog This crime.
	 */
	public function setReportDate(string $reportDate): self
	{
		$this->reptdate = $reportDate;
		return $this;
	}

	/**
	 * Changes the report time of this crime.
	 * @param string $reportTime.
	 * @return CrimeLog This crime.
	 */
	public function setReportTime(string $reportTime): self
	{
		$this->repttime = $reportTime;
		return $this;
	}

	/**
	 * Changes the occur from of this crime.
	 * @param string|null $occurFrom .
	 * @return CrimeLog This crime.
	 */
	public function setOccurFrom(string|null $occurFrom): self
	{
		$this->occurdate1 = $occurFrom;
		return $this;
	}

	/**
	 * Changes the occur to of this crime.
	 * @param string|null $occurTo .
	 * @return CrimeLog This crime.
	 */
	public function setOccurTo(string|null $occurTo): self
	{
		$this->occurdate2 = $occurTo;
		return $this;
	}

	/**
	 * Changes the status of this crime.
	 * @param string $status.
	 * @return CrimeLog This crime.
	 */
	public function setStatus(string $status): self
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Changes the closed designation of this crime.
	 * @param string $closed.
	 * @return CrimeLog This crime.
	 */
	public function setClosed(string $closed): self
	{
		$this->closed = $closed;
		return $this;
	}

	/**
	 * Changes the last approval of this crime.
	 * @param string $lastApproval.
	 * @return CrimeLog This crime.
	 */
	public function setLastApproval(string $lastApproval): self
	{
		$this->lastupdate = $lastApproval;
		return $this;
	}

	/**
	 * Changes the location of this crime.
	 * @param string $location.
	 * @return CrimeLog This crime.
	 */
	public function setLocation(string $location): self
	{
		$this->location = $location;
		return $this;
	}

	/**
	 * Changes the subject of this crime.
	 * @param string $subject.
	 * @return CrimeLog This crime.
	 */
	public function setSubject(string $subject): self
	{
		$this->subject = $subject;
		return $this;
	}
}
