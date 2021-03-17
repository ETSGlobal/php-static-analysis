<?php

// This namespace is allowed to use doctrine classes
namespace App\Repository;

use Doctrine\Common\Persistence\ManagerRegistry as LegacyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class Foo
{
    private LegacyRegistry $legacyRegistry;
    private ManagerRegistry $registry;
    private ObjectRepository $repository;
    private EntityManagerInterface $em;
    public EntityManager $manager;

    private $dm;

    public function __construct(DocumentManager $manager)
    {
        $this->dm =$manager;
    }
}
