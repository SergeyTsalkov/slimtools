<?php

class SlimErrorHandler extends Slim\Handlers\ErrorHandler {
  protected function logError(string $error): void {
    if ($this->exception instanceof Slim\Exception\HttpNotFoundException) {
      return;
    }

    parent::logError($error);
  }
}
