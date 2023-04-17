<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Response;

class AutoDocControllerTest extends TestCase
{
    protected $documentation;
    protected $localDriverFilePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->localDriverFilePath = __DIR__ . '/../storage/documentation.json';
        $this->documentation = $this->getJsonFixture('tmp_data');

        file_put_contents($this->localDriverFilePath, json_encode($this->documentation));

        config(['auto-doc.drivers.local.production_path' => $this->localDriverFilePath]);
    }

    public function tearDown(): void
    {
        putenv('SWAGGER_GLOBAL_PREFIX=');

        parent::tearDown();
    }

    public function testGetJSONDocumentation()
    {
        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson($this->documentation);
    }

    public function testGetJSONDocumentationWithAdditionalPaths()
    {
        config([
            'auto-doc.additional_paths' => ['tests/fixtures/AutoDocControllerTest/tmp_data_with_additional_paths.json']
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsJsonFixture('tmp_data_with_additional_paths', $response->json());
    }

    public function getJSONDocumentationInvalidAdditionalDoc(): array
    {
        return [
            [
                'additionalDocPath' => 'invalid_path/non_existent_file.json'
            ],
            [
                'additionalDocPath' => 'tests/fixtures/AutoDocControllerTest/documentation__non_json.txt'
            ],
            [
                'additionalDocPath' => 'tests/fixtures/AutoDocControllerTest/documentation__invalid_format__missing_field__paths.json'
            ]
        ];
    }

    /**
     * @dataProvider getJSONDocumentationInvalidAdditionalDoc
     *
     * @param string $additionalDocPath
     */
    public function testGetJSONDocumentationInvalidAdditionalDoc(string $additionalDocPath)
    {
        config([
            'auto-doc.additional_paths' => [$additionalDocPath]
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson($this->documentation);
    }

    public function testGetJSONDocumentationWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        $response = $this->json('get', '/global/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson($this->documentation);
    }

    public function testGetViewDocumentation()
    {
        config(['auto-doc.display_environments' => ['testing']]);

        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_documentation.html', $response->getContent());
    }

    public function testGetViewDocumentationEnvironmentDisable()
    {
        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response->assertSeeText('Forbidden.');
    }

    public function testGetViewDocumentationWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        config(['auto-doc.display_environments' => ['testing']]);

        $response = $this->get('/global');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_documentation_with_global_path.html', $response->getContent());
    }

    public function testGetSwaggerAssetFile()
    {
        $response = $this->get('/auto-doc/swagger-ui.js');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($response->getContent(), file_get_contents(resource_path('/assets/swagger/swagger-ui.js')));

        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testGetFileNotExists()
    {
        $response = $this->get('/auto-doc/non-existent-file.js');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testGetSwaggerAssetFileWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        $response = $this->get('/global/auto-doc/swagger-ui.js');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($response->getContent(), file_get_contents(resource_path('/assets/swagger/swagger-ui.js')));
    }

    public function testGetSwaggerAssetFileNotExists()
    {
        $response = $this->get('/global/auto-doc/invalid');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
