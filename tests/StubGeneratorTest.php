<?php

declare(strict_types=1);

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace KevinGH\Box;

use Herrera\Annotations\Tokenizer;
use KevinGH\Box\Compactor\Php;
use Phar;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class StubGeneratorTest extends TestCase
{
    /**
     * @var StubGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new StubGenerator();
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf(
            StubGenerator::class,
            StubGenerator::create()
        );
    }

    public function testGenerate(): void
    {
        $code = $this->getExtractCode();
        $code['constants'] = implode("\n", $code['constants']);
        $code['class'] = implode("\n", $code['class']);

        $this->generator
             ->alias('test.phar')
             ->extract(true)
             ->index('index.php')
             ->intercept(true)
             ->mimetypes(['phtml' => Phar::PHPS])
             ->mung(['REQUEST_URI'])
             ->notFound('not_found.php')
             ->rewrite('rewrite')
             ->web(true);

        $phps = Phar::PHPS;

        $this->assertSame(
            <<<STUB
#!/usr/bin/env php
<?php
/**
 * Generated by Box.
 *
 * @link https://github.com/herrera-io/php-box/
 */
{$code['constants']}
if (class_exists('Phar')) {
Phar::webPhar('test.phar', "index.php", "not_found.php", array (
  'phtml' => $phps,
), 'rewrite');
Phar::interceptFileFuncs();
Phar::mungServer(array (
  0 => 'REQUEST_URI',
));
} else {
\$extract = new Extract(__FILE__, Extract::findStubLength(__FILE__));
\$dir = \$extract->go();
set_include_path(\$dir . PATH_SEPARATOR . get_include_path());
require "\$dir/index.php";
}
{$code['class']}
__HALT_COMPILER();
STUB
            ,
            $this->generator->generate()
        );
    }

    public function testGenerateExtractForced(): void
    {
        $code = $this->getExtractCode();
        $code['constants'] = implode("\n", $code['constants']);
        $code['class'] = implode("\n", $code['class']);

        $this->generator
             ->alias('test.phar')
             ->extract(true, true)
             ->index('index.php')
             ->intercept(true)
             ->mimetypes(['phtml' => Phar::PHPS])
             ->mung(['REQUEST_URI'])
             ->notFound('not_found.php')
             ->rewrite('rewrite');

        $this->assertSame(
            <<<STUB
#!/usr/bin/env php
<?php
/**
 * Generated by Box.
 *
 * @link https://github.com/herrera-io/php-box/
 */
{$code['constants']}
\$extract = new Extract(__FILE__, Extract::findStubLength(__FILE__));
\$dir = \$extract->go();
set_include_path(\$dir . PATH_SEPARATOR . get_include_path());
if (class_exists('Phar')) {
Phar::mapPhar('test.phar');
Phar::interceptFileFuncs();
Phar::mungServer(array (
  0 => 'REQUEST_URI',
));
}
require "\$dir/index.php";
{$code['class']}
__HALT_COMPILER();
STUB
            ,
            $this->generator->generate()
        );
    }

    /**
     * @depends testGenerate
     */
    public function testGenerateMap(): void
    {
        $this->generator->alias('test.phar');

        $this->assertSame(
            <<<'STUB'
#!/usr/bin/env php
<?php
/**
 * Generated by Box.
 *
 * @link https://github.com/herrera-io/php-box/
 */
if (class_exists('Phar')) {
Phar::mapPhar('test.phar');
}
__HALT_COMPILER();
STUB
            ,
            $this->generator->generate()
        );
    }

    /**
     * @depends testGenerate
     */
    public function testGenerateNoShebang(): void
    {
        $this
            ->generator
            ->alias('test.phar')
            ->shebang('');

        $this->assertSame(
            <<<'STUB'
<?php
/**
 * Generated by Box.
 *
 * @link https://github.com/herrera-io/php-box/
 */
if (class_exists('Phar')) {
Phar::mapPhar('test.phar');
}
__HALT_COMPILER();
STUB
            ,
            $this->generator->generate()
        );
    }

    public function testMungInvalid(): void
    {
        $this->expectException(\KevinGH\Box\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $_SERVER variable "test" is not allowed.');

        $this->generator->mung(['test']);
    }

    private function getExtractCode()
    {
        $extractCode = [
            'constants' => [],
            'class' => [],
        ];

        $compactor = new Php(new Tokenizer());
        $code = file_get_contents(__DIR__.'/../src/Extract.php');
        $code = $compactor->compact($code);
        $code = preg_replace('/\n+/', "\n", $code);
        $code = explode("\n", $code);
        $code = array_slice($code, 2);

        foreach ($code as $i => $line) {
            if ((0 === strpos($line, 'use'))
                && (false === strpos($line, '\\'))
            ) {
                unset($code[$i]);
            } elseif (0 === strpos($line, 'define')) {
                $extractCode['constants'][] = $line;
            } else {
                $extractCode['class'][] = $line;
            }
        }

        return $extractCode;
    }
}
