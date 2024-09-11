<?php

namespace App\Entity;

use App\Entity\Map\MapitemImage;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\ImageRepository")]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap(
	['document' => Document::class, 'image' => Image::class, "mapiteminage" => MapitemImage::class, "userimage" => UserImage::class]
)]
abstract class Document
{
	#[ORM\Id]
	#[ORM\Column(name: "id", type: "integer")]
	#[ORM\GeneratedValue(strategy: "AUTO")]
	#[Groups("bldgs")]
	private $id;

	#[ORM\Column(name: "name", type: "string", length: 255)]
	#[Assert\NotBlank]
	#[Groups("bldgs")]
	private $name;

	#[ORM\Column(name: "path", type: "string", length: 255, nullable: true)]
	#[Groups("bldgs")]
	private $path;

	#[Assert\File(maxSize: "8M")]
	#[Groups("bldgs")]
	private $file;

	#[ORM\Column(name: "subdirectory", type: "string", length: 255)]
	#[Groups("bldgs")]
	private $subdir;

	private $temp;

	#[Gedmo\Timestampable(on: "create")]
	#[ORM\Column(type: "datetime")]
	private $created;

	#[Gedmo\Timestampable(on: "update")]
	#[ORM\Column(type: "datetime")]
	private $updated;

	#[ORM\Column(type: "datetime", nullable: true)]
	#[Gedmo\Timestampable(on: "change", field: ["title", "body"])]
	private $contentChanged;

	public function getId()
	{
		return $this->id;
	}

	public function setName($name)
	{
		if ($name !== null) {
			$this->name = $name;
		}

		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setPath($path)
	{
		$path = $path;

		return $this;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function setFile(UploadedFile $file = null)
	{
		$this->file = $file;
		if (isset($this->path)) {
			$this->temp = $this->path;
			$this->path = null;
		} else {
			$this->path = 'initial';
		}
	}

	public function getFile()
	{
		return $this->file;
	}

	public function setSubDir($subdir)
	{
		if ($subdir !== null) {
			$this->subdir = $subdir;
		}
		return $this;
	}

	public function getSubDir()
	{
		return $this->subdir;
	}

	public function getAbsolutePath()
	{
		return null === $this->path
			? null
			: $this->getUploadRootDir() . '/' . $this->path;
	}

	public function getWebPath()
	{
		return null === $this->path
			? null
			: $this->getUploadDir() . '/' . $this->path;
	}

	protected function getUploadRootDir()
	{
		return __DIR__ . '/../../public/' . $this->getUploadDir();
	}

	protected function getUploadDir()
	{
		if (null === $this->getSubDir()) {
			return 'uploads';
		} else {
			return $this->getSubDir();
		}
	}

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
	public function preUpload()
	{
		if (null !== $this->getFile()) {
			$filename = preg_replace('/[^A-Za-z0-9 _ .-]/', '', $this->getFile()->getClientOriginalName());
			$this->path = $filename;
		}
	}

	#[ORM\PostPersist]
	#[ORM\PostUpdate]
	public function upload()
	{
		if (null === $this->getFile()) {
			return;
		}

		$this->getFile()->move($this->getUploadRootDir(), $this->path);

		if (isset($this->temp)) {
			unlink($this->getUploadRootDir() . '/' . $this->temp);
			$this->temp = null;
		}
		$this->file = null;
	}

	#[ORM\PostRemove]
	public function removeUpload()
	{
		$file = $this->getAbsolutePath();
		if ($file) {
			unlink($file);
		}
	}

	public function __toString()
	{
		return $this->getName();
	}

	public function getCreated()
	{
		return $this->created;
	}

	public function getUpdated()
	{
		return $this->updated;
	}

	public function getContentChanged()
	{
		return $this->contentChanged;
	}
}
