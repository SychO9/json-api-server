<?php

/*
 * This file is part of tobyz/json-api-server.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobyz\Tests\JsonApiServer\specification;

use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\ConflictException;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;
use Tobyz\JsonApiServer\JsonApi;
use Tobyz\JsonApiServer\Schema\Type;
use Tobyz\Tests\JsonApiServer\AbstractTestCase;
use Tobyz\Tests\JsonApiServer\MockAdapter;

/**
 * @see https://jsonapi.org/format/1.1/#crud-updating
 */
class UpdatingResourcesTest extends AbstractTestCase
{
    /**
     * @var JsonApi
     */
    private $api;

    public function setUp(): void
    {
        $this->api = new JsonApi('http://example.com');

        $adapter = new MockAdapter([
            '1' => (object) ['id' => '1', 'name' => 'initial'],
        ]);

        $this->api->resourceType('users', $adapter, function (Type $type) {
            $type->updatable();
            $type->attribute('name')->writable();
            $type->hasOne('pet')->writable();
        });
    }

    public function test_bad_request_error_if_body_does_not_contain_data_type_and_id()
    {
        $this->expectException(BadRequestException::class);

        $this->api->handle(
            $this->buildRequest('PATCH', '/users/1')
                ->withParsedBody([
                    'data' => [],
                ])
        );
    }

    public function test_bad_request_error_if_relationship_does_not_contain_data()
    {
        $this->expectException(BadRequestException::class);

        $this->api->handle(
            $this->buildRequest('PATCH', '/users/1')
                ->withParsedBody([
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'relationships' => [
                            'pet' => [],
                        ],
                    ],
                ])
        );
    }

    public function test_ok_response_with_updated_data_if_resource_successfully_updated()
    {
        $response = $this->api->handle(
            $this->buildRequest('PATCH', '/users/1')
                ->withParsedBody([
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'attributes' => [
                            'name' => 'updated'
                        ],
                    ],
                ])
        );

        $document = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('updated', $document['data']['attributes']['name'] ?? null);
    }

    public function test_not_found_error_if_resource_does_not_exist()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->api->handle(
            $this->buildRequest('PATCH', '/users/404')
                ->withParsedBody([
                    'data' => [
                        'type' => 'users',
                        'id' => '404',
                        'attributes' => [
                            'name' => 'bob',
                        ],
                    ],
                ])
        );
    }

    public function test_not_found_error_if_references_resource_that_does_not_exist()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->api->handle(
            $this->buildRequest('PATCH', '/users/1')
                ->withParsedBody([
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'relationships' => [
                            'pet' => [
                                'data' => ['type' => 'pets', 'id' => '1'],
                            ],
                        ],
                    ],
                ])
        );
    }

    public function test_conflict_error_if_type_and_id_does_not_match_endpoint()
    {
        $this->expectException(ConflictException::class);

        $this->api->handle(
            $this->buildRequest('PATCH', '/users/1')
                ->withParsedBody([
                    'data' => [
                        'type' => 'pets',
                        'id' => '1',
                    ],
                ])
        );
    }
}
