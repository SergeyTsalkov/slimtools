<?php
use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface;

// Pimple doesn't implement PSR-11, which Slim 4 requires for its container
// Pimple's PSR-11 wrapper doesn't extend the class but wraps it, which means
// any instantiation function it runs will get passed the non-PSR11 Pimple
class SlimContainer extends PimpleContainer implements ContainerInterface {
  function get(string $id) {
    return $this[$id];
  }

  function has(string $id): bool {
    return isset($this[$id]);
  }
}
