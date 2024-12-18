<?php
use ScssPhp\ScssPhp\Compiler;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class SCSSController {
  protected $dir;

  function __construct($dir) {
    $this->dir = $dir;
  }

  function __invoke(Request $Req, Response $Resp, array $args) {
    $filename = preg_replace('/[^\w\.]/', '', $args['filename']);

    $files = [
      sprintf('%s/%s', $this->dir, $filename),
      sprintf('%s/%s', $this->dir, str_replace('.css', '.scss', $filename)),
    ];

    $source = null;
    foreach ($files as $file) {
      if (file_exists($file)) {
        $source = file_get_contents($file);
        break;
      }
    }
    if (! $source) {
      throw new Exception("Missing CSS file: $filename");
    }

    $SCSS = new Compiler();
    $SCSS->setImportPaths($this->dir);
    $SCSS->setFormatter('ScssPhp\ScssPhp\Formatter\Expanded');
    $result = $SCSS->compile($source);

    $Resp->getBody()->write($result);
    return $Resp
      ->withHeader('Content-Type', 'text/css')
      ->withoutHeader('Pragma');
  }
}