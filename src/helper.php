<?php


// 兼容TP5
if (version_compare(\think\App::VERSION, '5.1.0', '>=') && version_compare(\think\App::VERSION, '6.0.0', '<')) {
    \think\Console::addDefaultCommands([
        "think\\generator\\command\\Generate",
        "think\\generator\\command\\AST",
    ]);
}