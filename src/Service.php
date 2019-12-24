<?php
namespace think\generate;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        $this->commands([
            'generate' => command\Generate::class,
            'ast'      => command\AST::class,
        ]);
    }
}
