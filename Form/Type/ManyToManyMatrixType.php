<?php

namespace Yokai\ManyToManyMatrixBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
 * @author Yann Eugoné <yann.eugone@gmail.com>
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

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                function (FormEvent $event) use ($options) {
                    $entities = $event->getData();
                    $form = $event->getForm();

                    $class = $options['class'];
                    $association = $options['association'];
                    $queryBuilder = $options['query_builder'];
                    $targetClass = $this->getAssociationTargetClass($class, $association);

                    foreach ($entities as $idx => $entity) {
                        $form
                            ->add(
                                $idx,
                                EntityType::class,
                                [
                                    'property_path' => sprintf('[%d].%s', $idx, $association),
                                    'class' => $targetClass,
                                    'multiple' => true,
                                    'expanded' => true,
                                    'required' => false,
                                    'label' => (string) $entity,
                                    'query_builder' => function (EntityRepository $repository) use ($queryBuilder, $entity) {
                                        if (!is_callable($queryBuilder)) {
                                            return $repository->createQueryBuilder('e');
                                        }

                                        return call_user_func($queryBuilder, $repository, $entity);
                                    }
                                ]
                            )
                        ;
                    }
                }
            )
        ;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
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
                    //if no manager found nor no association on the entity, an exception will be thrown
                    $this->getAssociationTargetClass($options['class'], $association);

                    return $association;
                }
            )
            ->setDefault('query_builder', null)
            ->setAllowedTypes('query_builder', ['null', 'callable'])
        ;
    }

    /**
     * @inheritDoc
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $matrix = [];
        foreach ($view->children as $abscissa => $choice) {
            foreach ($choice->children as $ordinate => $checkbox) {
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
     * @param string $class A persistent object class name
     *
     * @return EntityManager The entity manager
     *
     * @throws InvalidArgumentException If no manager found for this object class name
     */
    private function getManager($class)
    {
        $manager = $this->doctrine->getManagerForClass($class);
        if (null === $manager) {
            throw new InvalidArgumentException(
                sprintf('There is no Doctrine manager for class "%s".', $class)
            );
        }

        return $manager;
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param string $class     A persistent object class name
     * @param string $assocName A persistent object association name
     *
     * @return string The target class name of the given association
     *
     * @throws InvalidArgumentException If no manager found for this object class name
     * @throws InvalidArgumentException If no association found for this object class name
     * @throws InvalidArgumentException If the association found for this object class name is not Many-To-Many
     */
    private function getAssociationTargetClass($class, $assocName)
    {
        $manager = $this->getManager($class);
        $metadata = $manager->getClassMetadata($class);

        if (!in_array($assocName, $metadata->getAssociationNames())) {
            throw new InvalidArgumentException(
                sprintf('The association "%s" on entity "%s" does not exists.', $assocName, $class)
            );
        }

        $mapping = $metadata->getAssociationMapping($assocName);

        if (ClassMetadataInfo::MANY_TO_MANY !== $mapping['type']) {
            throw new InvalidArgumentException(
                sprintf('The association "%s" on entity "%s" is not a many-to-many relation.', $assocName, $class)
            );
        }

        return $mapping['targetEntity'];
    }
}
