<?php
/*
 * Copyright 2016, Google Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Google\Cloud\Tests\Unit\Core;

use Google\Cloud\Tests\GrpcTestTrait;
use Google\Cloud\Core\OperationResponse;
use Google\Cloud\Core\LongRunning\OperationsClient;
use google\longrunning\Operation;
use Google\Protobuf\Any;
use Google\Rpc\Status;
use PHPUnit_Framework_TestCase;

class OperationResponseTest extends PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $opName = 'operations/opname';
        $opClient = self::createOperationsClient();
        $op = new OperationResponse($opName, $opClient);

        $this->assertSame($opName, $op->getName());
        $this->assertSame($opClient, $op->getOperationsClient());
    }

    public function testWithoutResponse()
    {
        $opName = 'operations/opname';
        $opClient = self::createOperationsClient();
        $op = new OperationResponse($opName, $opClient);

        $this->assertNull($op->getLastProtoResponse());
        $this->assertFalse($op->isDone());
        $this->assertNull($op->getResult());
        $this->assertNull($op->getError());
        $this->assertNull($op->getMetadata());
        $this->assertFalse($op->operationSucceeded());
        $this->assertFalse($op->operationFailed());
        $this->assertEquals([
            'operationReturnType' => null,
            'metadataReturnType' => null,
        ], $op->getReturnTypeOptions());
    }

    public function testWithResponse()
    {
        $opName = 'operations/opname';
        $opClient = self::createOperationsClient();
        $protoResponse = new Operation();
        $op = new OperationResponse($opName, $opClient, [
            'lastProtoResponse' => $protoResponse,
        ]);

        $this->assertSame($protoResponse, $op->getLastProtoResponse());
        $this->assertFalse($op->isDone());
        $this->assertNull($op->getResult());
        $this->assertNull($op->getError());
        $this->assertNull($op->getMetadata());
        $this->assertFalse($op->operationSucceeded());
        $this->assertFalse($op->operationFailed());
        $this->assertEquals([
            'operationReturnType' => null,
            'metadataReturnType' => null,
        ], $op->getReturnTypeOptions());

        $response = GrpcTestTrait::createAny(GrpcTestTrait::createStatus(0, "response"));
        $error = GrpcTestTrait::createStatus(2, "error");
        $metadata = GrpcTestTrait::createAny(GrpcTestTrait::createStatus(0, "metadata"));

        $protoResponse->setDone(true);
        $protoResponse->setResponse($response);
        $protoResponse->setMetadata($metadata);
        $this->assertTrue($op->isDone());
        $this->assertSame($response, $op->getResult());
        $this->assertSame($metadata, $op->getMetadata());
        $this->assertTrue($op->operationSucceeded());
        $this->assertFalse($op->operationFailed());

        $protoResponse->setError($error);
        $this->assertNull($op->getResult());
        $this->assertSame($error, $op->getError());
        $this->assertFalse($op->operationSucceeded());
        $this->assertTrue($op->operationFailed());
    }

    public function testWithOptions()
    {
        $opName = 'operations/opname';
        $opClient = self::createOperationsClient();
        $protoResponse = new Operation();
        $op = new OperationResponse($opName, $opClient, [
            'operationReturnType' => '\Google\Rpc\Status',
            'metadataReturnType' => '\Google\Protobuf\Any',
            'lastProtoResponse' => $protoResponse,
        ]);

        $this->assertSame($protoResponse, $op->getLastProtoResponse());
        $this->assertFalse($op->isDone());
        $this->assertNull($op->getResult());
        $this->assertNull($op->getError());
        $this->assertNull($op->getMetadata());
        $this->assertEquals([
            'operationReturnType' => '\Google\Rpc\Status',
            'metadataReturnType' => '\Google\Protobuf\Any',
        ], $op->getReturnTypeOptions());

        $innerResponse = GrpcTestTrait::createStatus(0, "response");
        $innerMetadata = new Any();
        $innerMetadata->setValue("metadata");

        $response = GrpcTestTrait::createAny($innerResponse);
        $metadata = GrpcTestTrait::createAny($innerMetadata);

        $protoResponse->setDone(true);
        $protoResponse->setResponse($response);
        $protoResponse->setMetadata($metadata);
        $this->assertTrue($op->isDone());
        $this->assertEquals($innerResponse, $op->getResult());
        $this->assertEquals($innerMetadata, $op->getMetadata());
    }

    public static function createOperationsClient($transport = null)
    {
        $client = new OperationsClient([
            'createTransportFunction' => function ($hostname, $opts) use ($transport) {
                return $transport;
            },
            'serviceAddress' => '',
            'scopes' => [],
        ]);
        return $client;
    }
}