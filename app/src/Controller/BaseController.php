<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Serializer\Serializer;
use App\Repository\BaseRepository;

class BaseController
{
  private BaseRepository $repository;
  private Serializer $serializer;

  /**
   * Constructs a new instance of the class, injecting its dependencies.
   * @param BaseRepository $repository Instance of BaseRepository or any of its subclasses. 
   * This object is used to interact with the database or other data source.
   * @param Serializer $serializer.
   * 
   * @return void
   */
  public function __construct(BaseRepository $repository, Serializer $serializer)
  {
    $this->repository = $repository;
    $this->serializer = $serializer;
  }

  /**
   * getter for respository
   * @return BaseRepository
   */
  public function getRepository(): BaseRepository {
    return $this->repository;
  }

  /**
   * getter for serializer
   * @return Serializer
   */
  public function getSerializer() {
    return $this->serializer;
  }
}