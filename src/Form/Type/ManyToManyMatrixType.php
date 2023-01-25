<?php

declare(strict_types=1);

namespace Yokai\ManyToManyMatrixBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class ManyToManyMatrixType extends AbstractType
{
    /**
     * The doctrine manager registry
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $doctrine The doctrine manager registry
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                function (FormEvent $event) use ($options) {
                    /** @var array<int, object> $entities */
                    $entities = $event->getData();
                    $form = $event->getForm();

                    /** @var class-string $class */
                    $class = $options['class'];
                    /** @var string $association */
                    $association = $options['association'];
                    /** @var callable|null $queryBuilder */
                    $queryBuilder = $options['query_builder'];

                    $targetClass = $this->getAssociationTargetClass($class, $association);

                    $choices = null;
                    if (!is_callable($queryBuilder)) {
                        $choices = $this->doctrine->getRepository($targetClass)->findAll();
                    }

                    foreach ($entities as $idx => $entity) {
                        $formOptions = [
                            'property_path' => sprintf('[%d].%s', $idx, $association),
                            'class' => $targetClass,
                            'multiple' => true,
                            'expanded' => true,
                            'required' => false,
                            'label' => (string) $entity,
                        ];
                        if ($choices !== null) {
                            $formOptions['choices'] = $choices;
                        } else {
                            $formOptions['query_builder'] = function (EntityRepository $repository) use ($queryBuilder, $entity) {
                                return call_user_func($queryBuilder, $repository, $entity);
                            };
                        }

                        $form->add((string) $idx, EntityType::class, $formOptions);
                    }
                }
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['class', 'association'])
            ->setNormalizer(
                'class',
                function (Options $options, $class) {
                    //if no manager found, an exception will be thrown
                    $this->getManager($class);

                    return $class;
                }
            )
            ->setNormalizer(
                'association',
                function (Options $options, $association) {
                    /** @var class-string $class */
                    $class = $options['class'];

                    //if no manager found nor no association on the entity, an exception will be thrown
                    $this->getAssociationTargetClass($class, $association);

                    return $association;
                }
            )
            ->setDefault('query_builder', null)
            ->setAllowedTypes('query_builder', ['null', 'callable'])
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $matrix = [];
        foreach ($view->children as $abscissa => $choice) {
            dump(['abscissa' => $choice->vars['label']]);
            foreach ($choice->children as $ordinate => $checkbox) {
                dump(['checkbox' => $checkbox->vars['label']]);
                if (!isset($matrix[$ordinate])) {
                    $matrix[$ordinate] = [
                        'label' => $checkbox->vars['label'],
                        'checkboxes' => [],
                    ];
                }

                $matrix[$ordinate]['checkboxes'][$abscissa] = $checkbox;
            }
        }

        $view->vars['matrix'] = $matrix;
    }

    /**
     * Gets the object manager associated with a given class
     *
     * @param class-string $class A persistent object class name
     *
     * @return EntityManagerInterface The entity manager
     *
     * @throws InvalidArgumentException If no manager found for this object class name
     */
    private function getManager(string $class): EntityManagerInterface
    {
        $manager = $this->doctrine->getManagerForClass($class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException(
                sprintf('There is no Doctrine manager for class "%s".', $class)
            );
        }

        return $manager;
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param class-string $class     A persistent object class name
     * @param string       $assocName A persistent object association name
     *
     * @return class-string The target class name of the given association
     *
     * @throws InvalidArgumentException If no manager found for this object class name
     * @throws InvalidArgumentException If no association found for this object class name
     * @throws InvalidArgumentException If the association found for this object class name is not Many-To-Many
     */
    private function getAssociationTargetClass(string $class, string $assocName): string
    {
        $manager = $this->getManager($class);
        $metadata = $manager->getClassMetadata($class);

        if (!in_array($assocName, $metadata->getAssociationNames(), true)) {
            throw new InvalidArgumentException(
                sprintf('The association "%s" on entity "%s" does not exists.', $assocName, $class)
            );
        }

        $mapping = $metadata->getAssociationMapping($assocName);

        if ($mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
            throw new InvalidArgumentException(
                sprintf('The association "%s" on entity "%s" is not a many-to-many relation.', $assocName, $class)
            );
        }

        return $mapping['targetEntity'];
    }
}
