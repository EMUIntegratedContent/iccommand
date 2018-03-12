<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImageRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"document" = "Document", "image" = "Image", "mapitemimage" = "App\Entity\Map\MapitemImage"})
*/
 abstract class Document
 {
     /**
      * @var integer
      *
      * @ORM\Column(name="id", type="integer")
      * @ORM\Id
      * @ORM\GeneratedValue(strategy="AUTO")
      */
     private $id;

     /**
      * @var string
      *
      * @ORM\Column(name="name", type="string", length=255)
      * @Assert\NotBlank
      */
     private $name;

     /**
      * @var string
      *
      * @ORM\Column(name="path", type="string", length=255, nullable=true)
      */
     private $path;

     /**
      * @Assert\File(maxSize = "8M")
      */
     private $file;

     /**
      * @var string
      *
      * @ORM\Column(name="subdirectory", type="string", length=255)
      */
     private $subdir;

     private $temp;

     /**
      * @Gedmo\Timestampable(on="create")
      * @ORM\Column(type="datetime")
     */
     private $created;

     /**
      * @Gedmo\Timestampable(on="update")
      * @ORM\Column(type="datetime")
     */
     private $updated;

     /**
      * @ORM\Column(type="datetime", nullable=true)
      * @Gedmo\Timestampable(on="change", field={"title", "body"})
      */
     private $contentChanged;

     /**
      * Get id
      *
      * @return integer
      */
     public function getId()
     {
         return $this->id;
     }

     /**
      * Set name
      *
      * @param string $name
      *
      * @return Document
      */
     public function setName($name)
     {
         if($name !== null){
             $this->name = $name;
         }

         return $this;
     }

     /**
      * Get name
      *
      * @return string
      */
     public function getName()
     {
         return $this->name;
     }

     /**
      * Set path
      *
      * @param string $path
      *
      * @return Document
      */
     public function setPath($path)
     {
         $this->path = $path;

         return $this;
     }

     /**
      * Get path
      *
      * @return string
      */
     public function getPath()
     {
         return $this->path;
     }

     /**
      * Sets file.
      *
      * @param UploadedFile $file
      */
     public function setFile(UploadedFile $file = null)
     {
         $this->file = $file;
         // check if we have an old image path
         if (isset($this->path)) {
             // store the old name to delete after the update
             $this->temp = $this->path;
             $this->path = null;
         } else {
             $this->path = 'initial';
         }
     }

     /**
      * Get file.
      *
      * @return UploadedFile
      */
     public function getFile()
     {
         return $this->file;
     }

     /**
      * Set sub directory
      *
      * @param string $subdir
      *
      * @return Document
      */
     public function setSubDir($subdir)
     {
         if($subdir !== null){
             $this->subdir = $subdir;
         }
         return $this;
     }

     /**
      * Get sub directory
      *
      * @return string
      */
     public function getSubDir()
     {
         return $this->subdir;
     }

     public function getAbsolutePath()
     {
         return null === $this->path
             ? null
             : $this->getUploadRootDir().'/'.$this->path;
     }

     public function getWebPath()
     {
         return null === $this->path
             ? null
             : $this->getUploadDir().'/'.$this->path;
     }

     protected function getUploadRootDir()
     {
         // the absolute directory path where uploaded
         // documents should be saved
         return __DIR__.'/../../public/'.$this->getUploadDir();
     }

     protected function getUploadDir()
     {
         // get rid of the __DIR__ so it doesn't screw up
         // when displaying uploaded doc/image in the view.
         if(null === $this->getSubDir()){
             return 'uploads';
         } else {
             return $this->getSubDir();
         }
     }

     /**
      * The PreUpdate and PostUpdate callbacks are only triggered if there is a change in one of the entity's fields that are persisted.
      * This means that, by default, if you modify only the $file property, these events will not be triggered,
      * as the property itself is not directly persisted via Doctrine.
      * One solution would be to use an updated field that's persisted to Doctrine, and to modify it manually when changing the file.
      */

     /**
      * NOTE: PrePersist() and PreUpdate() are part of the Lifecycle Callbacks declared before the class
      *
      * @ORM\PrePersist()
      * @ORM\PreUpdate()
      */
     public function preUpload()
     {
         if (null !== $this->getFile()) {
             // do whatever you want to generate a unique name
             //$filename = sha1(uniqid(mt_rand(), true));
             //$filename = preg_replace('/[^A-Za-z0-9 _ .-]/', '', time(). '_' .$this->getFile()->getClientOriginalName());
             $filename = preg_replace('/[^A-Za-z0-9 _ .-]/', '', $this->getFile()->getClientOriginalName());
             $this->path = $filename;
         }
     }

     /**
      * NOTE: PostPersist() and PostUpdate() are part of the Lifecycle Callbacks declared before the class
      *
      * @ORM\PostPersist()
      * @ORM\PostUpdate()
      */
     public function upload()
     {
         if (null === $this->getFile()) {
             return;
         }

         // if there is an error when moving the file, an exception will
         // be automatically thrown by move(). This will properly prevent
         // the entity from being persisted to the database on error
         $this->getFile()->move($this->getUploadRootDir(), $this->path);

         // check if we have an old image
         if (isset($this->temp)) {
             // delete the old image
             unlink($this->getUploadRootDir().'/'.$this->temp);
             // clear the temp image path
             $this->temp = null;
         }
         $this->file = null;
     }

     /**
      * NOTE: PostRemove() is part of the Lifecycle Callbacks declared before the class
      *
      * @ORM\PostRemove()
      */
     public function removeUpload()
     {
         $file = $this->getAbsolutePath();
         if ($file) {
             unlink($file);
         }
     }

     public function __toString(){
         return $this->getName();
     }

     /** GEDMO FIELDS **/

     public function getCreated(){
         return $this->created;
     }

     public function getUpdated(){
         return $this->updated;
     }

     public function getContentChanged(){
         return $this->contentChanged;
     }
 }
