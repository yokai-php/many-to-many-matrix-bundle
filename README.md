YokaiManyToManyMatrixBundle
===========================

This bundle aims to render Doctrine ManyToMany relations as a matrix in a Symfony form.


Installation
------------

### Add the bundle as dependency with Composer

``` bash
composer require yokai/many-to-many-matrix-bundle
```

### Enable the bundle

``` php
<?php
// config/bundles.php

return [
    // ...
    Yokai\ManyToManyMatrixBundle\YokaiManyToManyMatrixBundle::class => ['all' => true],
];
```


Usage
-----

Let's take an example : our application is handling Symfony's security with 2 Doctrine entity : `User` and `Role`.
There is a ManyToMany between `Role` and `User`.

```php
<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true)]
    private string $email;

    /**
     * @var Collection<Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    private Collection $roles;

    public function __toString(): string
    {
        return $this->email;
    }

    //...
}
```

```php
<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role')]
class Role
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true)]
    private string $role;

    /**
     * @var Collection<User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'roles')]
    private Collection $users;

    public function __toString(): string
    {
        return $this->role;
    }

    //...
}
```

I want to create a form matrix that will display the relation of these entities.

```php
<?php

namespace App\Controller;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Yokai\ManyToManyMatrixBundle\Form\Type\ManyToManyMatrixType;

class MatrixController extends AbstractController
{
    #[Route(path: '/role-matrix', name: 'role-matrix')]
    public function roleMatrixAction(Request $request, EntityManagerInterface $manager): Response
    {
        $roles = $manager->getRepository(Role::class)->findAll();

        $form = $this->createForm(
            ManyToManyMatrixType::class,
            $roles,
            [
                'class' => Role::class,
                'association' => 'users',
            ]
        );

        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('role-matrix.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        foreach ($roles as $role) {
            $manager->persist($role);
        }

        $manager->flush($roles);

        return $this->redirectToRoute('role-matrix');
    }
}
```

```twig
{% extends 'base.html.twig' %}

{% block body %}
    {{ form_start(form) }}
        {{ form_widget(form) }}
        <button type="submit" class="btn btn-primary">update matrix</button>
    {{ form_end(form) }}
{% endblock %}
```

![Screenshot](Resources/screenshot.png)


Important Notes
---------------

In the example above, we must note several **IMPORTANT** things.

### Owning side

You **MUST** work with the [owning side](http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html)
  of your association (i.e. the entity that has the `ìnversedBy` attribute on its ManyToMany property)

### __toString

The two entities **MUST** have a `__toString` method to render the label


MIT License
-----------

License can be found [here](https://github.com/yann-eugone/many-to-many-matrix-bundle/blob/main/LICENSE).


Authors
-------

The bundle was originally created by [Yann Eugoné](https://github.com/yann-eugone).
See the list of [contributors](https://github.com/yann-eugone/many-to-many-matrix-bundle/contributors).
