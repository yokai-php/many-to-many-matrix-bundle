<?php

declare(strict_types=1);

namespace Yokai\ManyToManyMatrixBundle\Tests\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Yokai\ManyToManyMatrixBundle\Form\Type\ManyToManyMatrixType;
use PHPUnit\Framework\TestCase;
use Yokai\ManyToManyMatrixBundle\Tests\ContainerTestAccessor;
use Yokai\ManyToManyMatrixBundle\Tests\App\Entity\Role;
use Yokai\ManyToManyMatrixBundle\Tests\App\Entity\User;

class ManyToManyMatrixTypeTest extends TypeTestCase
{
    private ContainerInterface $container;
    private ManagerRegistry $doctrine;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->container = ContainerTestAccessor::container();
        $this->doctrine = $this->container->get(ManagerRegistry::class);
        $this->entityManager = $this->container->get(EntityManagerInterface::class);
        $this->loadFixtures($this->entityManager);

        parent::setUp();
    }

    public function testSubmit(): void
    {
        $roles = $this->entityManager->getRepository(Role::class)->findAll();
        /** @var array<string, Role> $indexedRoles */
        $indexedRoles = array_combine(
            array_map(fn(Role $role) => $role->getRole(), $roles),
            $roles
        );

        $users = $this->entityManager->getRepository(User::class)->findAll();
        /** @var array<string, User> $indexedUsers */
        $indexedUsers = array_combine(
            array_map(fn(User $user) => $user->getEmail(), $users),
            $users
        );

        $form = $this->factory->create(
            ManyToManyMatrixType::class,
            $roles,
            [
                'class' => Role::class,
                'association' => 'users',
            ]
        );

        $form->submit([
            [
                $indexedUsers['john@doe.us']->getId(),
                // removed marie@doe.us
            ],
            [
                // removed marie@doe.us
                // added john@doe.us
                $indexedUsers['john@doe.us']->getId(),
            ],
            [
                // added marie@doe.us
                $indexedUsers['marie@doe.us']->getId(),
            ],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame([$indexedUsers['john@doe.us']], $indexedRoles['VIEW_ADMIN']->getUsers()->toArray());
        self::assertSame([$indexedUsers['john@doe.us']], $indexedRoles['WRITE_ADMIN']->getUsers()->toArray());
        self::assertSame([$indexedUsers['marie@doe.us']], $indexedRoles['DELETE_ADMIN']->getUsers()->toArray());
    }

    public function testView(): void
    {
        $roles = $this->entityManager->getRepository(Role::class)->findAll();
        $form = $this->factory->create(
            ManyToManyMatrixType::class,
            $roles,
            [
                'class' => Role::class,
                'association' => 'users',
            ]
        );
        $view = $form->createView();

        dump($view->vars);

        self::assertArrayHasKey('matrix', $view->vars);
        dump(array_map(
            fn (array $entry) => array_merge(
                $entry,
                ['checkboxes' => array_map(fn(FormView $view) => $view->vars['label'], $entry['checkboxes'])]
            ),
            $view->vars['matrix']
        ));
        self::assertArrayHasKey('block_prefixes', $view->vars);
        self::assertContains('many_to_many_matrix', $view->vars['block_prefixes']);
    }

    protected function getTypes()
    {
        return [
            new EntityType($this->doctrine),
            new ManyToManyMatrixType($this->doctrine),
        ];
    }

    private function loadFixtures(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();

        $database = $connection->getParams()['path'];
        if (file_exists($database)) {
            unlink($database);
        }

        (new SchemaTool($entityManager))
            ->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $entityManager->persist($john = new User('john@doe.us'));
        $entityManager->persist($marie = new User('marie@doe.us'));

        $entityManager->persist($viewAdmin = new Role('VIEW_ADMIN'));
        $viewAdmin->addUser($john);
        $viewAdmin->addUser($marie);

        $entityManager->persist($writeAdmin = new Role('WRITE_ADMIN'));
        $writeAdmin->addUser($marie);

        $entityManager->persist($deleteAdmin = new Role('DELETE_ADMIN'));

        $entityManager->flush();
    }
}
