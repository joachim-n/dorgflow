<?php

namespace Dorgflow\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ContainerAware trait.
 *
 * Taken from Symfony 6.
 *
 * TODO: Remove this, use DI as Symfony suggests.
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @return void
     */
    public function setContainer(?ContainerInterface $container = null)
    {
        if (1 > \func_num_args()) {
            trigger_deprecation('symfony/dependency-injection', '6.2', 'Calling "%s::%s()" without any arguments is deprecated, pass null explicitly instead.', __CLASS__, __FUNCTION__);
        }

        $this->container = $container;
    }
}
