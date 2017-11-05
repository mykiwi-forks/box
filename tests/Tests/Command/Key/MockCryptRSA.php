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

namespace KevinGH\Box\Tests\Command\Key;

use phpseclib\Crypt\RSA;

class MockCryptRSA extends RSA
{
    public function getPublicKey($type = self::PUBLIC_FORMAT_PKCS1)
    {
        return false;
    }
}