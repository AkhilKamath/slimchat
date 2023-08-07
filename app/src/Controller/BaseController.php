<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Serializer\Serializer;
use App\Repository\BaseRepository;

class BaseController
{
  private BaseRepository $repository;
  private Serializer $serializer;

  public function __construct(BaseRepository $repository, Serializer $serializer)
  {
    $this->repository = $repository;
    $this->serializer = $serializer;
  }

  public function getRepository() {
    return $this->repository;
  }

  public function getSerializer() {
    return $this->serializer;
  }

}